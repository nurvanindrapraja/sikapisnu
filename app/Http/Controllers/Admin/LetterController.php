<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\Pac;
use App\Services\LetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): View
    {
        $this->checkAccess();

        $query = Letter::with(['user', 'secretary', 'chairman']);

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

        $statusCounts = [
            'all' => Letter::count(),
            'draft' => Letter::where('status', Letter::STATUS_DRAFT)->count(),
            'menunggu_sekretaris' => Letter::where('status', Letter::STATUS_MENUNGGU_SEKRETARIS)->count(),
            'menunggu_ketua' => Letter::where('status', Letter::STATUS_MENUNGGU_KETUA)->count(),
            'selesai' => Letter::where('status', Letter::STATUS_SELESAI)->count(),
            'ditolak' => Letter::where('status', Letter::STATUS_DITOLAK)->count(),
        ];

        return view('admin.letters.index', compact('letters', 'statusCounts'));
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

        // Hitung nomor urut surat estimasi
        $countThisYear = Letter::where('year', $year)->count() + 1;
        $suggestedNumber = sprintf('%03d/PC-VI/ISNU-SBY/%s/%s', $countThisYear, $romanMonth, $year);

        $pacs = Pac::orderBy('name')->get();
        $user = auth()->user();
        $pos = $user?->getOfficerPosition();
        $defaultPacId = $user?->getManagedPacId();
        $defaultLevel = ($pos && (in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || $pos->pac_id))
            ? Letter::LEVEL_PAC
            : ($user?->isAdminPac() ? Letter::LEVEL_PAC : Letter::LEVEL_PC);

        return view('admin.letters.create', [
            'suggestedNumber' => $suggestedNumber,
            'romanMonth' => $romanMonth,
            'year' => $year,
            'todayDate' => $today->format('Y-m-d'),
            'type' => $type,
            'pacs' => $pacs,
            'defaultLevel' => $defaultLevel,
            'defaultPacId' => $defaultPacId,
        ]);
    }

    public function store(Request $request, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

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

    public function show(int $id): View
    {
        $this->checkAccess();

        $letter = Letter::with(['user', 'secretary', 'chairman'])->findOrFail($id);

        return view('admin.letters.show', compact('letter'));
    }

    public function edit(int $id): View
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        if (! auth()->user()->canEditLetter($letter)) {
            if ($letter->status === Letter::STATUS_SELESAI) {
                abort(403, 'Surat yang telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua tidak dapat disunting kembali oleh siapapun.');
            }

            abort(403, 'Anda tidak memiliki hak akses untuk menyunting konsep surat ini.');
        }

        $pacs = Pac::orderBy('name')->get();

        return view('admin.letters.edit', compact('letter', 'pacs'));
    }

    public function update(Request $request, int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        if (! auth()->user()->canEditLetter($letter)) {
            if ($letter->status === Letter::STATUS_SELESAI) {
                abort(403, 'Surat yang telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua tidak dapat disunting kembali oleh siapapun.');
            }

            abort(403, 'Anda tidak memiliki hak akses untuk menyunting konsep surat ini.');
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

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        if ($letter->status === Letter::STATUS_SELESAI && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Surat yang sudah selesai ditandatangani tidak dapat dihapus.');
        }

        if (! auth()->user()->canEditLetter($letter) && ! auth()->user()->isSuperAdmin()) {
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

        $letterNumber = $letter->letter_number;
        $letter->delete();

        AuditLog::record(auth()->id(), 'delete_letter', "Menghapus konsep surat nomor: {$letterNumber}");

        return redirect()->route('admin.letters.index')->with('success', 'Konsep surat berhasil dihapus.');
    }

    public function submitSecretary(int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

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

    public function signSecretary(int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        if (! auth()->user()->canSignAsSecretary($letter)) {
            abort(403, 'Hanya akun yang menjabat sebagai Sekretaris atau Wakil Sekretaris pada tingkatan surat ini yang berhak menandatangani. Admin tidak berhak menandatangani surat.');
        }

        if (! in_array($letter->status, [Letter::STATUS_MENUNGGU_SEKRETARIS, Letter::STATUS_DRAFT], true)) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Status surat saat ini tidak dalam antrean penandatanganan Sekretaris.');
        }

        $letterService->signAsSecretary($letter, auth()->user());

        AuditLog::record(
            auth()->id(),
            'sign_letter_secretary',
            "Menandatangani surat digital nomor: {$letter->letter_number} sebagai Sekretaris",
            ['letter_id' => $letter->id]
        );

        return redirect()->route('admin.letters.show', $letter->id)->with('success', 'Surat berhasil ditandatangani secara digital oleh Sekretaris dan kini diteruskan ke Ketua.');
    }

    public function signChairman(int $id, LetterService $letterService): RedirectResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        if (! auth()->user()->canSignAsChairman($letter)) {
            abort(403, 'Hanya akun yang menjabat sebagai Ketua atau Wakil Ketua pada tingkatan surat ini yang berhak menandatangani. Admin tidak berhak menandatangani surat.');
        }

        // KETENTUAN: Harus ditandatangani oleh Sekretaris terlebih dahulu!
        if (! $letter->secretary_signed_at) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Surat harus ditandatangani secara digital oleh Sekretaris terlebih dahulu sebelum ditandatangani oleh Ketua.');
        }

        if ($letter->status !== Letter::STATUS_MENUNGGU_KETUA) {
            return redirect()->route('admin.letters.show', $letter->id)->with('error', 'Status surat saat ini tidak dalam antrean penandatanganan Ketua.');
        }

        $letterService->signAsChairman($letter, auth()->user());

        AuditLog::record(
            auth()->id(),
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

    public function downloadPdf(int $id, LetterService $letterService): BinaryFileResponse
    {
        $this->checkAccess();

        $letter = Letter::findOrFail($id);

        // KETENTUAN: File PDF baru dapat diunduh ketika surat sudah ditandatangani secara lengkap
        if ($letter->status !== Letter::STATUS_SELESAI) {
            abort(403, 'File PDF surat resmi baru dapat diunduh ketika surat telah selesai ditandatangani secara lengkap oleh Sekretaris dan Ketua.');
        }

        $letterService->generatePdf($letter);

        $filename = 'Surat_'.Str::slug($letter->letter_number, '_').'.pdf';
        $fullPath = Storage::disk('public')->path($letter->pdf_path);

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
