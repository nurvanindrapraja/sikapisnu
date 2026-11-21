<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Models\Pac;
use App\Services\LetterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LetterController extends Controller
{
    protected function checkAccess(): void
    {
        if (! auth()->user() || ! auth()->user()->canAccessLetterSubmission()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengakses menu pengajuan konsep surat.');
        }
    }

    /**
     * Terapkan batasan lingkup surat khusus PAC apabila user login adalah pengurus/admin PAC.
     *
     * @param  Builder<Letter>  $query
     */
    protected function applyPacScope($query): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isScopedToPacLetters()) {
            return;
        }

        $pacId = $user->getLetterPacId();
        if ($pacId) {
            $query->where('level', Letter::LEVEL_PAC)->where('pac_id', $pacId);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    protected function authorizeLetterAccess(Letter $letter): void
    {
        if (! auth()->user() || ! auth()->user()->canViewLetter($letter)) {
            abort(403, 'Anda hanya dapat mengakses surat milik PAC Anda.');
        }
    }

    /**
     * Terapkan batasan lingkup surat khusus PAC apabila user login adalah pengurus/admin PAC.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Letter>  $query
     */
    protected function applyPacScope($query): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isScopedToPacLetters()) {
            return;
        }

        $pacId = $user->getLetterPacId();
        if ($pacId) {
            $query->where('level', Letter::LEVEL_PAC)->where('pac_id', $pacId);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    protected function authorizeLetterAccess(Letter $letter): void
    {
        if (! auth()->user() || ! auth()->user()->canViewLetter($letter)) {
            abort(403, 'Anda hanya dapat mengakses surat milik PAC Anda.');
        }
    }

    public function index(Request $request): View
    {
        $this->checkAccess();

        $user = auth()->user();
        $isPacScoped = $user->isScopedToPacLetters();
        $scopedPac = $isPacScoped && $user->getLetterPacId()
            ? Pac::find($user->getLetterPacId())
            : null;

        $query = Letter::with(['user', 'secretary', 'chairman', 'pac'])->withCount('attachments');
        $this->applyPacScope($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('letter_type')) {
            $query->where('letter_type', $request->letter_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%")
                    ->orWhere('tujuan_surat', 'like', "%{$search}%")
                    ->orWhere('letter_code', 'like', "%{$search}%")
                    ->orWhere('kegiatan', 'like', "%{$search}%");
            });
        }

        $letters = $query->latest('date_letter')->latest('id')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('admin.letters.partials.letter_list', compact('letters'));
        }

        $countByStatus = function (?string $status = null): int {
            $q = Letter::query();
            $this->applyPacScope($q);
            if ($status !== null) {
                $q->where('status', $status);
            }

            return $q->count();
        };

        $statusCounts = [
            'all' => $countByStatus(),
            'draft' => $countByStatus(Letter::STATUS_DRAFT),
            'menunggu_sekretaris' => $countByStatus(Letter::STATUS_MENUNGGU_SEKRETARIS),
            'menunggu_ketua' => $countByStatus(Letter::STATUS_MENUNGGU_KETUA),
            'selesai' => $countByStatus(Letter::STATUS_SELESAI),
            'ditolak' => $countByStatus(Letter::STATUS_DITOLAK),
        ];

        return view('admin.letters.index', compact('letters', 'statusCounts', 'isPacScoped', 'scopedPac'));
    }

    public function create(Request $request): View
    {
        $this->checkAccess();

        $today = now();
        $romanMonth = Letter::getRomanMonth((int) $today->month);
        $year = (string) $today->year;

        $type = $request->query('type', Letter::TYPE_SURAT_BIASA);
        if (! in_array($type, [Letter::TYPE_SURAT_BIASA, Letter::TYPE_SURAT_MANDAT, Letter::TYPE_SURAT_REKOMENDASI], true)) {
            $type = Letter::TYPE_SURAT_BIASA;
        }

        $user = auth()->user();
        $isPacScoped = $user->isScopedToPacLetters();
        $lockedPacId = $isPacScoped ? $user->getLetterPacId() : null;
        $lockedPac = $lockedPacId ? Pac::find($lockedPacId) : null;

        if ($isPacScoped && ! $lockedPac) {
            abort(403, 'Akun pengurus PAC Anda belum terhubung dengan wilayah PAC tertentu.');
        }

        if ($isPacScoped) {
            $countThisYear = Letter::where('year', $year)
                ->where('level', Letter::LEVEL_PAC)
                ->where('pac_id', $lockedPac->id)
                ->count() + 1;
            $pacSlug = strtoupper(Str::slug($lockedPac->kecamatan ?: preg_replace('/^(PAC\s+ISNU\s+|PAC\s+)/i', '', trim($lockedPac->name)), ''));
            $suggestedNumber = sprintf('%03d/PAC/ISNU-%s/%s/%s', $countThisYear, $pacSlug ?: 'SBY', $romanMonth, $year);
            $pacs = collect([$lockedPac]);
            $defaultLevel = Letter::LEVEL_PAC;
            $defaultPacId = $lockedPac->id;
        } else {
            $countThisYear = Letter::where('year', $year)->count() + 1;
            $suggestedNumber = sprintf('%03d/PC-VI/ISNU-SBY/%s/%s', $countThisYear, $romanMonth, $year);
            $pacs = Pac::orderBy('name')->get();
            $defaultLevel = Letter::LEVEL_PC;
            $defaultPacId = null;
        }

        return view('admin.letters.create', [
            'suggestedNumber' => $suggestedNumber,
            'romanMonth' => $romanMonth,
            'year' => $year,
            'todayDate' => $today->format('Y-m-d'),
            'type' => $type,
            'pacs' => $pacs,
            'defaultLevel' => $defaultLevel,
            'defaultPacId' => $defaultPacId,
            'isPacScoped' => $isPacScoped,
            'lockedPac' => $lockedPac,
        ]);
    }

    public function store(Request $request, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $user = auth()->user();
        if ($user->isScopedToPacLetters()) {
            $lockedPacId = $user->getLetterPacId();
            if (! $lockedPacId) {
                abort(403, 'Akun pengurus PAC Anda belum terhubung dengan wilayah PAC tertentu.');
            }

            $request->merge([
                'level' => Letter::LEVEL_PAC,
                'pac_id' => $lockedPacId,
            ]);
        }

        $validated = $request->validate([
            'letter_type' => 'nullable|in:surat_biasa,surat_mandat,surat_rekomendasi',
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'letter_number' => 'required|string|max:100',
            'roman_month' => 'required|string|max:10',
            'year' => 'required|string|digits:4',
            'date_letter' => 'required|date',
            'lampiran' => 'nullable|string|max:100',
            'perihal' => 'nullable|string|max:255',
            'kegiatan' => 'nullable|string|max:255',
            'tanggal_pelaksanaan' => 'nullable|string|max:150',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'kota_asal' => 'required|string|max:100',
            'tujuan_surat' => 'nullable|string|max:255',
            'kota_tujuan' => 'nullable|string|max:100',
            'isi_surat' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf|max:10240',
            'action' => 'required|in:draft,submit_secretary',
        ]);

        $letterType = $validated['letter_type'] ?? Letter::TYPE_SURAT_BIASA;
        $perihal = $validated['perihal'] ?? null;
        if (empty($perihal)) {
            if ($letterType === Letter::TYPE_SURAT_MANDAT) {
                $perihal = 'Surat Mandat'.(! empty($validated['kegiatan']) ? ': '.$validated['kegiatan'] : '');
            } elseif ($letterType === Letter::TYPE_SURAT_REKOMENDASI) {
                $perihal = 'Surat Rekomendasi';
            } else {
                $perihal = 'Surat Resmi';
            }
        }

        $tujuanSurat = $validated['tujuan_surat'] ?? null;
        if (empty($tujuanSurat)) {
            if ($letterType === Letter::TYPE_SURAT_MANDAT) {
                $tujuanSurat = 'Penerima Mandat';
            } elseif ($letterType === Letter::TYPE_SURAT_REKOMENDASI) {
                $tujuanSurat = 'Penerima Rekomendasi';
            } else {
                $tujuanSurat = '-';
            }
        }

        $letterCode = 'SRT-'.date('Ymd').'-'.strtoupper(Str::random(6));
        $status = $validated['action'] === 'submit_secretary'
            ? Letter::STATUS_MENUNGGU_SEKRETARIS
            : Letter::STATUS_DRAFT;

        $normalizedLetterNumber = str_replace('/PC-ISNU/SBY/', '/PC-VI/ISNU-SBY/', $validated['letter_number']);

        $letter = Letter::create([
            'user_id' => auth()->id(),
            'letter_code' => $letterCode,
            'letter_type' => $letterType,
            'level' => $validated['level'],
            'pac_id' => $validated['level'] === Letter::LEVEL_PAC ? ($validated['pac_id'] ?? null) : null,
            'letter_number' => $normalizedLetterNumber,
            'roman_month' => $validated['roman_month'],
            'year' => $validated['year'],
            'date_letter' => $validated['date_letter'],
            'lampiran' => ($validated['lampiran'] ?? null) ?: '-',
            'perihal' => $perihal,
            'kegiatan' => $validated['kegiatan'] ?? null,
            'tanggal_pelaksanaan' => $validated['tanggal_pelaksanaan'] ?? null,
            'lokasi_pelaksanaan' => $validated['lokasi_pelaksanaan'] ?? null,
            'kota_asal' => $validated['kota_asal'] ?? 'Surabaya',
            'tujuan_surat' => $tujuanSurat,
            'kota_tujuan' => ($validated['kota_tujuan'] ?? null) ?: 'Surabaya',
            'isi_surat' => $validated['isi_surat'],
            'status' => $status,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('letters/attachments', 'public');
                    $letter->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

        // Generate dokumen draft DOCX & PDF
        $letterService->generateDocx($letter);
        $letterService->generatePdf($letter);

        AuditLog::record(
            auth()->id(),
            'create_letter_draft',
            "Membuat konsep surat baru nomor: {$letter->letter_number} (Status: {$letter->status})",
            ['letter_id' => $letter->id, 'letter_code' => $letter->letter_code]
        );

        $message = $status === Letter::STATUS_MENUNGGU_SEKRETARIS
            ? 'Konsep surat berhasil diajukan dan sedang menunggu penandatanganan digital oleh Sekretaris.'
            : 'Konsep surat berhasil disimpan sebagai draft.';

        return redirect()->route('admin.letters.show', $letter->id)->with('success', $message);
    }

    public function previewDraft(Request $request, LetterService $letterService): View|Response
    {
        $this->checkAccess();

        $data = $request->except(['_token', '_method']);

        if ($request->filled('id') && $existing = Letter::with(['secretary', 'chairman', 'pac', 'attachments'])->find($request->id)) {
            $this->authorizeLetterAccess($existing);
            $letter = $existing;
            $letter->fill($data);
        } else {
            $letter = new Letter;
            $letter->fill($data);
            $letter->user_id = auth()->id();
            $letter->letter_code = $letter->letter_code ?: ('DRAFT-'.strtoupper(Str::random(6)));
            $letter->status = Letter::STATUS_DRAFT;
            $letter->setRelation('attachments', collect());
        }

        if (auth()->user()->isScopedToPacLetters()) {
            $lockedPacId = auth()->user()->getLetterPacId();
            $letter->level = Letter::LEVEL_PAC;
            $letter->pac_id = $lockedPacId;
            $letter->setRelation('pac', $lockedPacId ? Pac::find($lockedPacId) : null);
        }

        if ($letter->isi_surat === null) {
            $letter->isi_surat = '';
        }

        if (empty($letter->letter_type)) {
            $letter->letter_type = Letter::TYPE_SURAT_BIASA;
        }

        if (empty($letter->perihal)) {
            if ($letter->letter_type === Letter::TYPE_SURAT_MANDAT) {
                $letter->perihal = 'Surat Mandat'.(! empty($letter->kegiatan) ? ': '.$letter->kegiatan : '');
            } elseif ($letter->letter_type === Letter::TYPE_SURAT_REKOMENDASI) {
                $letter->perihal = 'Surat Rekomendasi';
            } else {
                $letter->perihal = 'Surat Resmi';
            }
        }

        if (empty($letter->tujuan_surat)) {
            if ($letter->letter_type === Letter::TYPE_SURAT_MANDAT) {
                $letter->tujuan_surat = 'Penerima Mandat';
            } elseif ($letter->letter_type === Letter::TYPE_SURAT_REKOMENDASI) {
                $letter->tujuan_surat = 'Penerima Rekomendasi';
            } else {
                $letter->tujuan_surat = '-';
            }
        }

        if (empty($letter->letter_number)) {
            $letter->letter_number = '___/PC-VI/ISNU-SBY/'.($letter->roman_month ?: Letter::getRomanMonth((int) now()->month)).'/'.($letter->year ?: now()->year);
        }

        if (empty($letter->date_letter)) {
            $letter->date_letter = now()->toDateString();
        }

        if (empty($letter->kota_asal)) {
            $letter->kota_asal = 'Surabaya';
        }

        if ($letter->pac_id && ! $letter->relationLoaded('pac')) {
            $letter->setRelation('pac', Pac::find($letter->pac_id));
        }

        if ($request->query('format') === 'pdf' || $request->input('format') === 'pdf' || $request->header('Accept') === 'application/pdf') {
            $pdfContent = $letterService->renderPdfContent($letter);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Pratinjau_Surat_'.$letter->letter_code.'.pdf"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        return view('admin.letters.partials.letter_preview_modal_content', [
            'letter' => $letter,
            'isDraftPreview' => true,
        ]);
    }

    public function preview(int $id): View
    {
        $this->checkAccess();

        $letter = Letter::with(['user', 'secretary', 'chairman', 'pac', 'attachments'])->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        return view('admin.letters.partials.letter_preview_modal_content', compact('letter'));
    }

    public function show(int $id): View
    {
        $this->checkAccess();

        $letter = Letter::with(['user', 'secretary', 'chairman', 'pac', 'attachments'])->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        return view('admin.letters.show', compact('letter'));
    }

    public function edit(int $id): View
    {
        $this->checkAccess();

        $letter = Letter::with('attachments')->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        if (! auth()->user()->canEditLetter($letter)) {
            if ($letter->status === Letter::STATUS_SELESAI) {
                abort(403, 'Surat yang telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua tidak dapat disunting kembali oleh siapapun.');
            }

            abort(403, 'Anda tidak memiliki hak akses untuk menyunting konsep surat ini.');
        }

        $user = auth()->user();
        $isPacScoped = $user->isScopedToPacLetters();
        $lockedPac = $isPacScoped && $user->getLetterPacId()
            ? Pac::find($user->getLetterPacId())
            : null;
        $pacs = $isPacScoped
            ? ($lockedPac ? collect([$lockedPac]) : collect())
            : Pac::orderBy('name')->get();

        return view('admin.letters.edit', compact('letter', 'pacs', 'isPacScoped', 'lockedPac'));
    }

    public function update(Request $request, int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::with('attachments')->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        if (! auth()->user()->canEditLetter($letter)) {
            if ($letter->status === Letter::STATUS_SELESAI) {
                abort(403, 'Surat yang telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua tidak dapat disunting kembali oleh siapapun.');
            }

            abort(403, 'Anda tidak memiliki hak akses untuk menyunting konsep surat ini.');
        }

        $user = auth()->user();
        if ($user->isScopedToPacLetters()) {
            $lockedPacId = $user->getLetterPacId();
            if (! $lockedPacId) {
                abort(403, 'Akun pengurus PAC Anda belum terhubung dengan wilayah PAC tertentu.');
            }

            $request->merge([
                'level' => Letter::LEVEL_PAC,
                'pac_id' => $lockedPacId,
            ]);
        }

        $validated = $request->validate([
            'letter_type' => 'nullable|in:surat_biasa,surat_mandat,surat_rekomendasi',
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'letter_number' => 'required|string|max:100',
            'roman_month' => 'required|string|max:10',
            'year' => 'required|string|digits:4',
            'date_letter' => 'required|date',
            'lampiran' => 'nullable|string|max:100',
            'perihal' => 'nullable|string|max:255',
            'kegiatan' => 'nullable|string|max:255',
            'tanggal_pelaksanaan' => 'nullable|string|max:150',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'kota_asal' => 'required|string|max:100',
            'tujuan_surat' => 'nullable|string|max:255',
            'kota_tujuan' => 'nullable|string|max:100',
            'isi_surat' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf|max:10240',
            'delete_attachment_ids' => 'nullable|array',
            'delete_attachment_ids.*' => 'integer|exists:letter_attachments,id',
            'action' => 'nullable|in:draft,submit_secretary',
        ]);

        $letterType = $validated['letter_type'] ?? $letter->letter_type ?? Letter::TYPE_SURAT_BIASA;
        $perihal = $validated['perihal'] ?? null;
        if (empty($perihal)) {
            if ($letterType === Letter::TYPE_SURAT_MANDAT) {
                $perihal = 'Surat Mandat'.(! empty($validated['kegiatan']) ? ': '.$validated['kegiatan'] : '');
            } elseif ($letterType === Letter::TYPE_SURAT_REKOMENDASI) {
                $perihal = 'Surat Rekomendasi';
            } else {
                $perihal = $letter->perihal ?: 'Surat Resmi';
            }
        }

        $normalizedLetterNumber = str_replace('/PC-ISNU/SBY/', '/PC-VI/ISNU-SBY/', $validated['letter_number']);

        $letter->update([
            'letter_type' => $letterType,
            'level' => $validated['level'],
            'pac_id' => $validated['level'] === Letter::LEVEL_PAC ? ($validated['pac_id'] ?? null) : null,
            'letter_number' => $normalizedLetterNumber,
            'roman_month' => $validated['roman_month'],
            'year' => $validated['year'],
            'date_letter' => $validated['date_letter'],
            'lampiran' => ($validated['lampiran'] ?? null) ?: '-',
            'perihal' => $perihal,
            'kegiatan' => $validated['kegiatan'] ?? null,
            'tanggal_pelaksanaan' => $validated['tanggal_pelaksanaan'] ?? null,
            'lokasi_pelaksanaan' => $validated['lokasi_pelaksanaan'] ?? null,
            'kota_asal' => $validated['kota_asal'] ?? 'Surabaya',
            'tujuan_surat' => ($validated['tujuan_surat'] ?? null) ?: ($letterType === Letter::TYPE_SURAT_MANDAT ? 'Penerima Mandat' : ($letterType === Letter::TYPE_SURAT_REKOMENDASI ? 'Penerima Rekomendasi' : '-')),
            'kota_tujuan' => ($validated['kota_tujuan'] ?? null) ?: 'Surabaya',
            'isi_surat' => $validated['isi_surat'],
        ]);

        // Hapus lampiran yang ditandai untuk dihapus
        if ($request->filled('delete_attachment_ids')) {
            $toDelete = $letter->attachments()->whereIn('id', $request->delete_attachment_ids)->get();
            foreach ($toDelete as $att) {
                Storage::disk('public')->delete($att->file_path);
                $att->delete();
            }
        }

        // Simpan berkas lampiran baru jika ada yang diunggah
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('letters/attachments', 'public');
                    $letter->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

        if (isset($validated['action']) && $validated['action'] === 'submit_secretary') {
            $letter->status = Letter::STATUS_MENUNGGU_SEKRETARIS;
            $letter->rejection_note = null;

            if ($letter->secretary_qr_path) {
                Storage::disk('public')->delete($letter->secretary_qr_path);
            }
            $letter->secretary_user_id = null;
            $letter->secretary_name = null;
            $letter->secretary_registration_number = null;
            $letter->secretary_position = null;
            $letter->secretary_signed_at = null;
            $letter->secretary_qr_path = null;

            if ($letter->chairman_qr_path) {
                Storage::disk('public')->delete($letter->chairman_qr_path);
            }
            $letter->chairman_user_id = null;
            $letter->chairman_name = null;
            $letter->chairman_registration_number = null;
            $letter->chairman_position = null;
            $letter->chairman_signed_at = null;
            $letter->chairman_qr_path = null;

            $letter->save();
        } elseif (isset($validated['action']) && $validated['action'] === 'draft') {
            if ($letter->status === Letter::STATUS_DITOLAK) {
                $letter->status = Letter::STATUS_DRAFT;
            }

            if ($letter->secretary_qr_path) {
                Storage::disk('public')->delete($letter->secretary_qr_path);
            }
            $letter->secretary_user_id = null;
            $letter->secretary_name = null;
            $letter->secretary_registration_number = null;
            $letter->secretary_position = null;
            $letter->secretary_signed_at = null;
            $letter->secretary_qr_path = null;

            if ($letter->chairman_qr_path) {
                Storage::disk('public')->delete($letter->chairman_qr_path);
            }
            $letter->chairman_user_id = null;
            $letter->chairman_name = null;
            $letter->chairman_registration_number = null;
            $letter->chairman_position = null;
            $letter->chairman_signed_at = null;
            $letter->chairman_qr_path = null;

            $letter->save();
        }

        // Regenerate docx & pdf
        $letterService->generateDocx($letter);
        $letterService->generatePdf($letter);

        AuditLog::record(
            auth()->id(),
            'update_letter_draft',
            "Memperbarui konsep surat nomor: {$letter->letter_number}",
            ['letter_id' => $letter->id]
        );

        return redirect()->route('admin.letters.show', $letter->id)->with('success', 'Perubahan konsep surat berhasil disimpan.');
    }

    public function destroyAttachment(int $attachmentId): JsonResponse|RedirectResponse
    {
        $this->checkAccess();

        $attachment = LetterAttachment::with('letter')->findOrFail($attachmentId);
        $letter = $attachment->letter;
        $this->authorizeLetterAccess($letter);

        if (! auth()->user()->canEditLetter($letter)) {
            if ($letter->status === Letter::STATUS_SELESAI) {
                abort(403, 'Surat yang telah selesai tidak dapat diubah lampirannya.');
            }

            abort(403, 'Anda tidak memiliki hak akses untuk menghapus lampiran surat ini.');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lampiran PDF berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Lampiran PDF berhasil dihapus.');
    }

    public function previewAttachment(int $attachmentId): BinaryFileResponse
    {
        $this->checkAccess();

        $attachment = LetterAttachment::with('letter')->findOrFail($attachmentId);
        $this->authorizeLetterAccess($attachment->letter);
        $fullPath = Storage::disk('public')->path($attachment->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan server.');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    public function downloadAttachment(int $attachmentId): BinaryFileResponse
    {
        $this->checkAccess();

        $attachment = LetterAttachment::with('letter')->findOrFail($attachmentId);
        $this->authorizeLetterAccess($attachment->letter);
        $fullPath = Storage::disk('public')->path($attachment->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan server.');
        }

        return response()->download($fullPath, $attachment->file_name);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::with('attachments')->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        if ($letter->status === Letter::STATUS_SELESAI && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Surat yang telah selesai ditandatangani hanya dapat dihapus oleh Super Admin.');
        }

        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->canEditLetter($letter)) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus konsep surat ini.');
        }

        if ($letter->secretary_qr_path) {
            Storage::disk('public')->delete($letter->secretary_qr_path);
        }
        if ($letter->chairman_qr_path) {
            Storage::disk('public')->delete($letter->chairman_qr_path);
        }
        if ($letter->docx_path) {
            Storage::disk('public')->delete($letter->docx_path);
        }
        if ($letter->pdf_path) {
            Storage::disk('public')->delete($letter->pdf_path);
        }

        foreach ($letter->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $letterNumber = $letter->letter_number;
        $statusWas = $letter->status;
        $letter->delete();

        AuditLog::record(auth()->id(), 'delete_letter', "Menghapus surat nomor: {$letterNumber} (Status awal: {$statusWas})");

        $message = $statusWas === Letter::STATUS_SELESAI
            ? "Surat nomor {$letterNumber} yang telah selesai ditandatangani berhasil dihapus oleh Super Admin."
            : 'Konsep surat berhasil dihapus.';

        return redirect()->route('admin.letters.index')->with('success', $message);
    }

    public function submitSecretary(int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);
        $this->authorizeLetterAccess($letter);

        if (! auth()->user()->canEditLetter($letter)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengajukan konsep surat ini.');
        }

        $letter->status = Letter::STATUS_MENUNGGU_SEKRETARIS;
        $letter->rejection_note = null;

        if ($letter->secretary_qr_path) {
            Storage::disk('public')->delete($letter->secretary_qr_path);
        }
        $letter->secretary_user_id = null;
        $letter->secretary_name = null;
        $letter->secretary_registration_number = null;
        $letter->secretary_position = null;
        $letter->secretary_signed_at = null;
        $letter->secretary_qr_path = null;

        if ($letter->chairman_qr_path) {
            Storage::disk('public')->delete($letter->chairman_qr_path);
        }
        $letter->chairman_user_id = null;
        $letter->chairman_name = null;
        $letter->chairman_registration_number = null;
        $letter->chairman_position = null;
        $letter->chairman_signed_at = null;
        $letter->chairman_qr_path = null;

        $letter->save();

        $letterService->generateDocx($letter);
        $letterService->generatePdf($letter);

        AuditLog::record(auth()->id(), 'submit_letter_to_secretary', "Mengajukan konsep surat nomor: {$letter->letter_number} ke Sekretaris");

        return redirect()->route('admin.letters.show', $letter->id)->with('success', 'Konsep surat berhasil diajukan ke Sekretaris untuk penandatanganan digital.');
    }

    public function signSecretary(Request $request, int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);
        $this->authorizeLetterAccess($letter);
        $user = auth()->user();

        if (! $user->canSignAsSecretary($letter)) {
            abort(403, 'Hanya akun yang menjabat sebagai Sekretaris atau Wakil Sekretaris pada tingkatan surat ini yang berhak menandatangani. Admin tidak berhak menandatangani surat.');
        }

        if (! in_array($letter->status, [Letter::STATUS_MENUNGGU_SEKRETARIS, Letter::STATUS_DRAFT], true)) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Status surat saat ini tidak dalam antrean penandatanganan Sekretaris.');
        }

        $request->validate([
            'signature_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:3072',
            'stamp_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:3072',
        ]);

        if (! $request->hasFile('signature_image') && ! $user->hasSignature() && ! app()->environment('testing')) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Wajib mengunggah gambar tanda tangan terlebih dahulu sebelum menandatangani surat.');
        }

        $letterService->signAsSecretary($letter, $user, $request->file('signature_image'), $request->file('stamp_image'));

        AuditLog::record(
            $user->id,
            'sign_letter_secretary',
            "Menandatangani surat digital nomor: {$letter->letter_number} sebagai Sekretaris",
            ['letter_id' => $letter->id]
        );

        return redirect()->route('admin.letters.show', $letter->id)->with('success', 'Surat berhasil ditandatangani secara digital oleh Sekretaris dan kini diteruskan ke Ketua.');
    }

    public function signChairman(Request $request, int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);
        $this->authorizeLetterAccess($letter);
        $user = auth()->user();

        if (! $user->canSignAsChairman($letter)) {
            abort(403, 'Hanya akun yang menjabat sebagai Ketua atau Wakil Ketua pada tingkatan surat ini yang berhak menandatangani. Admin tidak berhak menandatangani surat.');
        }

        // KETENTUAN: Harus ditandatangani oleh Sekretaris terlebih dahulu!
        if (! $letter->secretary_signed_at) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Surat harus ditandatangani secara digital oleh Sekretaris terlebih dahulu sebelum ditandatangani oleh Ketua.');
        }

        if ($letter->status !== Letter::STATUS_MENUNGGU_KETUA) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Status surat saat ini tidak dalam antrean penandatanganan Ketua.');
        }

        $request->validate([
            'signature_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:3072',
        ]);

        if (! $request->hasFile('signature_image') && ! $user->hasSignature() && ! app()->environment('testing')) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Wajib mengunggah gambar tanda tangan terlebih dahulu sebelum menandatangani surat.');
        }

        $letterService->signAsChairman($letter, $user, $request->file('signature_image'));

        AuditLog::record(
            $user->id,
            'sign_letter_chairman',
            "Menandatangani surat digital nomor: {$letter->letter_number} sebagai Ketua (Selesai)",
            ['letter_id' => $letter->id]
        );

        return redirect()->route('admin.letters.show', $letter->id)->with('success', 'Surat berhasil ditandatangani secara digital oleh Ketua. Seluruh proses penandatanganan telah lengkap dan surat resmi siap digunakan!');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);
        $this->authorizeLetterAccess($letter);
        $user = auth()->user();
        if (! $user->canSignAsSecretary($letter) && ! $user->canSignAsChairman($letter)) {
            abort(403, 'Hanya penandatangan yang berwenang (Ketua/Sekretaris) pada tingkatan surat ini yang berhak menolak atau meminta revisi surat.');
        }

        $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $letter->status = Letter::STATUS_DITOLAK;
        $letter->rejection_note = $request->rejection_note;
        $letter->save();

        AuditLog::record(
            auth()->id(),
            'reject_letter',
            "Mengembalikan/menolak konsep surat nomor: {$letter->letter_number}. Catatan: {$request->rejection_note}",
            ['letter_id' => $letter->id]
        );

        return redirect()->route('admin.letters.show', $letter->id)->with('warning', 'Konsep surat telah dikembalikan dengan catatan revisi.');
    }

    public function downloadPdf(Request $request, int $id, LetterService $letterService): BinaryFileResponse
    {
        $this->checkAccess();

        $letter = Letter::with(['attachments' => fn ($query) => $query->orderBy('id')])->findOrFail($id);
        $this->authorizeLetterAccess($letter);

        // KETENTUAN: File PDF baru dapat diunduh ketika surat sudah ditandatangani secara lengkap
        if ($letter->status !== Letter::STATUS_SELESAI) {
            abort(403, 'File PDF surat resmi baru dapat diunduh ketika surat telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua.');
        }

        $slug = Str::slug($letter->letter_number, '_');

        if ($request->boolean('include_attachments') && $letter->attachments->isNotEmpty()) {
            $result = $letterService->generateMergedPdf($letter);
            $filename = $result['has_merged_attachments']
                ? "Surat_{$slug}_dan_Lampiran.pdf"
                : "Surat_{$slug}.pdf";

            $response = response()->download($result['path'], $filename, [
                'Content-Type' => 'application/pdf',
            ]);

            if ($result['is_temporary']) {
                $response->deleteFileAfterSend(true);
            }

            return $response;
        }

        $letterService->generatePdf($letter);

        $filename = "Surat_{$slug}.pdf";
        $fullPath = Storage::disk('public')->path($letter->pdf_path);

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
