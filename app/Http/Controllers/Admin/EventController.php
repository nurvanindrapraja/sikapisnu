<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\EventRsvp;
use App\Models\Member;
use App\Models\Pac;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Models\Workgroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Mengambil daftar Seksi dan Pokja (beserta program aktifnya) yang boleh dipilih user pada form Tambah/Edit Kegiatan:
     * - Admin Sistem: semua Seksi & Pokja.
     * - Pengurus PC: hanya Seksi PC ISNU + Pokja PC ISNU (sesuai lingkup jabatan).
     * - Pengurus PAC: hanya Seksi PAC-nya + Pokja PAC-nya (sesuai lingkup jabatan).
     *
     * @return array{0: Collection<int, Section>, 1: Collection<int, Workgroup>}
     */
    protected function resolveFormOwners(User $user): array
    {
        $activeProgramsRelation = ['programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'), 'pac'];

        if ($user->isSuperAdmin() || $user->isAdminKota() || $user->role === 'admin') {
            return [
                Section::with($activeProgramsRelation)->orderBy('level')->orderBy('name')->get(),
                Workgroup::with($activeProgramsRelation)->where('is_active', true)->orderBy('level')->orderBy('name')->get(),
            ];
        }

        if ($user->isOfficerPac()) {
            $pacId = $user->getManagedPacId();
            if (! $pacId) {
                return [collect(), collect()];
            }

            if ($user->isKetuaOrSekretarisPac()) {
                return [
                    Section::with($activeProgramsRelation)
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->orderBy('name')
                        ->get(),
                    Workgroup::with($activeProgramsRelation)
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(),
                ];
            }

            if ($user->isSectionOfficer()) {
                $mySectionId = $user->getOfficerSectionId();
                $memberId = $user->member?->id;

                $sections = $mySectionId
                    ? Section::with($activeProgramsRelation)->where('id', $mySectionId)->where('pac_id', $pacId)->get()
                    : collect();

                $workgroups = $memberId
                    ? Workgroup::with($activeProgramsRelation)
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->where('is_active', true)
                        ->whereHas('members', fn ($q) => $q->where('members.id', $memberId))
                        ->orderBy('name')
                        ->get()
                    : collect();

                return [$sections, $workgroups];
            }

            return [collect(), collect()];
        }

        if ($user->isKetuaOrSekretarisPc()) {
            return [
                Section::with($activeProgramsRelation)
                    ->where('level', 'PC ISNU')
                    ->orderBy('name')
                    ->get(),
                Workgroup::with($activeProgramsRelation)
                    ->where('level', 'PC ISNU')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),
            ];
        }

        if ($user->isSectionOfficer()) {
            $mySectionId = $user->getOfficerSectionId();
            $memberId = $user->member?->id;

            $sections = $mySectionId
                ? Section::with($activeProgramsRelation)->where('id', $mySectionId)->where('level', 'PC ISNU')->get()
                : collect();

            $workgroups = $memberId
                ? Workgroup::with($activeProgramsRelation)
                    ->where('level', 'PC ISNU')
                    ->where('is_active', true)
                    ->whereHas('members', fn ($q) => $q->where('members.id', $memberId))
                    ->orderBy('name')
                    ->get()
                : collect();

            return [$sections, $workgroups];
        }

        return [collect(), collect()];
    }

    /**
     * Memetakan input `owner_key` (`section:{id}` atau `workgroup:{id}`) ke `section_id` dan `workgroup_id`.
     */
    protected function normalizeOwnerInput(Request $request): void
    {
        if (! $request->has('owner_key')) {
            return;
        }

        $ownerKey = trim((string) $request->input('owner_key'));
        if ($ownerKey === '') {
            $request->merge([
                'section_id' => null,
                'workgroup_id' => null,
            ]);
        } elseif (str_starts_with($ownerKey, 'section:')) {
            $request->merge([
                'section_id' => (int) substr($ownerKey, strlen('section:')),
                'workgroup_id' => null,
            ]);
        } elseif (str_starts_with($ownerKey, 'workgroup:')) {
            $request->merge([
                'section_id' => null,
                'workgroup_id' => (int) substr($ownerKey, strlen('workgroup:')),
            ]);
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isPacScoped = $user->isOfficerPac() && ! ($user->isSuperAdmin() || $user->isAdminKota() || $user->role === 'admin');
        $managedPacId = $isPacScoped ? $user->getManagedPacId() : null;

        $query = Event::with(['section.pac', 'workgroup.pac', 'program'])->withCount(['presences', 'attendingRsvps']);

        if ($isPacScoped) {
            $query->where(function ($q) use ($managedPacId) {
                $q->whereHas('section', fn ($sq) => $sq->where('pac_id', $managedPacId))
                    ->orWhereHas('workgroup', fn ($wq) => $wq->where('pac_id', $managedPacId));
            });
        } else {
            if ($request->filled('owner_key')) {
                $ownerKey = trim((string) $request->owner_key);
                if (str_starts_with($ownerKey, 'section:')) {
                    $query->where('section_id', (int) substr($ownerKey, strlen('section:')));
                } elseif (str_starts_with($ownerKey, 'workgroup:')) {
                    $query->where('workgroup_id', (int) substr($ownerKey, strlen('workgroup:')));
                }
            } elseif ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            } elseif ($request->filled('workgroup_id')) {
                $query->where('workgroup_id', $request->workgroup_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->latest('event_date')->paginate(10)->withQueryString();
        $sections = $isPacScoped
            ? Section::with(['programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'), 'pac'])
                ->where('pac_id', $managedPacId)->orderBy('name')->get()
            : Section::with(['programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'), 'pac'])
                ->orderBy('level')->orderBy('name')->get();
        $workgroups = $isPacScoped
            ? Workgroup::with(['programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'), 'pac'])
                ->where('pac_id', $managedPacId)->where('is_active', true)->orderBy('name')->get()
            : Workgroup::with(['programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'), 'pac'])
                ->where('is_active', true)->orderBy('level')->orderBy('name')->get();

        [$formSections, $formWorkgroups] = $this->resolveFormOwners($user);

        if ($request->ajax()) {
            return view('admin.events.partials.event_list', compact('events', 'sections', 'workgroups'));
        }

        return view('admin.events.index', compact('events', 'sections', 'workgroups', 'formSections', 'formWorkgroups'));
    }

    /**
     * Terapkan batasan lingkup kader khusus wilayah PAC bagi akun pengurus/admin PAC.
     */
    protected function applyPacMemberScope($query, ?User $user = null): void
    {
        $user = $user ?? auth()->user();
        if (! $user || ! $user->isScopedToPac()) {
            return;
        }

        $pacId = $user->getManagedPacId();
        $pacKecamatan = $user->getManagedPacKecamatan();

        if (! $pacId) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($pacId, $pacKecamatan) {
            $q->where('pac_id', $pacId)
                ->orWhereHas('activePosition', function ($p) use ($pacId) {
                    $p->whereIn('level', ['PAC', 'PAC ISNU', 'Kecamatan'])
                        ->where('pac_id', $pacId);
                });

            if ($pacKecamatan) {
                $q->orWhere(function ($sub) use ($pacKecamatan) {
                    $sub->whereNull('pac_id')
                        ->where('kecamatan', 'like', "%{$pacKecamatan}%");
                });
            }
        });
    }

    public function rekapPresensi(Request $request)
    {
        $user = auth()->user();
        $isPacScoped = $user->isScopedToPac();
        $scopedPac = $isPacScoped ? $user->getManagedPac() : null;

        $query = Member::withCount('presences')
            ->with(['pac', 'mwc', 'activePosition.pac', 'activePosition.section', 'presences' => function ($q) {
                $q->with('event')->latest('attended_at');
            }]);

        $this->applyPacMemberScope($query, $user);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'anggota' || $status === 'terverifikasi') {
                $query->whereIn('membership_status', ['terverifikasi', 'anggota']);
            } elseif ($status === 'pengurus') {
                $query->where('membership_status', 'pengurus');
            } elseif (str_contains($status, 'calon') || $status === 'menunggu_verifikasi') {
                $query->whereIn('membership_status', ['calon', 'menunggu_verifikasi']);
            } else {
                $query->where('membership_status', $request->status);
            }
        }

        if (! $isPacScoped && $request->filled('pac_id')) {
            $query->where('pac_id', $request->pac_id);
        }

        $sort = $request->get('sort', 'most_active');
        if ($sort === 'most_active') {
            $query->orderByDesc('presences_count')->latest();
        } elseif ($sort === 'least_active') {
            $query->orderBy('presences_count', 'asc')->latest();
        } elseif ($sort === 'name_asc') {
            $query->orderBy('full_name', 'asc');
        } else {
            $query->latest();
        }

        $members = $query->paginate(15)->withQueryString();
        $pacs = $isPacScoped
            ? ($scopedPac ? collect([$scopedPac]) : collect())
            : Pac::orderBy('name')->get();

        $scopedMemberBase = function () use ($user) {
            $q = Member::query();
            $this->applyPacMemberScope($q, $user);

            return $q;
        };

        $totalKaderCount = $scopedMemberBase()->has('presences')->count();
        $totalPresensiCount = $isPacScoped
            ? EventPresence::whereHas('member', fn ($q) => $this->applyPacMemberScope($q, $user))->count()
            : EventPresence::count();
        $topParticipant = $scopedMemberBase()->withCount('presences')->orderByDesc('presences_count')->first();

        if ($request->ajax()) {
            return view('admin.presensi.partials.rekap_list', compact('members'));
        }

        return view('admin.presensi.rekap', compact(
            'members',
            'pacs',
            'totalKaderCount',
            'totalPresensiCount',
            'topParticipant',
            'isPacScoped',
            'scopedPac'
        ));
    }

    public function detailPresensiKader($member_id)
    {
        $member = Member::with([
            'pac',
            'mwc',
            'activePosition.pac',
            'activePosition.section',
            'presences' => function ($q) {
                $q->with('event')->latest('attended_at');
            },
        ])->findOrFail($member_id);

        if (! auth()->user()->canManageMember($member) && ! (auth()->user()->isScopedToPac() && auth()->user()->canViewMember($member))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki wewenang untuk melihat detail kader di luar wilayah PAC Anda.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name ?? $member->name,
                'nik' => $member->nik ?? $member->member_number,
                'photo_url' => $member->photo_url,
                'membership_status' => ucfirst($member->membership_status ?? 'Anggota'),
                'position_title' => $member->activePosition ? ($member->activePosition->position_title ?? 'Pengurus') : null,
                'pac_name' => $member->pac ? $member->pac->display_name : ($member->activePosition && $member->activePosition->pac ? $member->activePosition->pac->display_name : '-'),
                'total_presences' => $member->presences->count(),
            ],
            'presences' => $member->presences->map(function ($p) {
                return [
                    'id' => $p->id,
                    'event_title' => $p->event->title ?? 'Kegiatan ISNU',
                    'presence_date' => $p->attended_at ? $p->attended_at->format('d/m/Y') : ($p->created_at ? $p->created_at->format('d/m/Y') : '-'),
                    'presence_time' => $p->attended_at ? $p->attended_at->format('H:i').' WIB' : ($p->created_at ? $p->created_at->format('H:i').' WIB' : '-'),
                    'method' => ucfirst($p->event->method ?? 'luring'),
                    'location' => $p->event->location ?? '-',
                    'institution_or_pac' => $p->institution_or_pac,
                    'notes' => $p->notes,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeOwnerInput($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section_id' => 'nullable|exists:sections,id',
            'workgroup_id' => 'nullable|exists:workgroups,id',
            'program_id' => 'nullable|exists:programs,id',
            'budget' => 'nullable|numeric|min:0',
            'location' => 'required|string|max:255',
            'method' => 'required|in:daring,luring',
            'meeting_link' => 'nullable|required_if:method,daring|url|max:500',
            'event_date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'presence_start_at' => 'required|date',
            'presence_end_at' => 'required|date|after_or_equal:presence_start_at',
            'rsvp_enabled' => 'nullable|boolean',
            'rsvp_deadline_at' => 'nullable|date',
            'status' => 'required|in:planned,completed,cancelled',
        ]);

        $validated['rsvp_enabled'] = $request->has('rsvp_enabled') ? $request->boolean('rsvp_enabled') : true;

        if (! empty($validated['program_id']) && empty($validated['section_id']) && empty($validated['workgroup_id'])) {
            $prog = Program::find($validated['program_id']);
            $validated['section_id'] = $prog?->section_id;
            $validated['workgroup_id'] = $prog?->workgroup_id;
        }

        if (! empty($validated['workgroup_id'])) {
            $validated['section_id'] = null;
            $authorized = auth()->user()->canManageWorkgroupProgramOrEvent((int) $validated['workgroup_id']);
        } else {
            $validated['workgroup_id'] = null;
            $sectionId = ! empty($validated['section_id']) ? (int) $validated['section_id'] : null;
            $authorized = auth()->user()->canManageSectionProgramOrEvent($sectionId);
        }

        if (! $authorized) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menambahkan kegiatan pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan kegiatan pada seksi / pokja ini.');
        }

        $validated['unique_code'] = 'evt-'.Str::slug(substr($validated['title'], 0, 30)).'-'.Str::random(6);
        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);

        AuditLog::record(auth()->id(), 'Tambah Kegiatan', "Menambahkan Kegiatan: {$event->title}");

        $rsvpUrl = $event->rsvp_enabled ? route('event.rsvp.show', $event->unique_code) : null;
        $presenceUrl = route('event.presence.show', $event->unique_code);
        $successMessage = $event->rsvp_enabled
            ? "Kegiatan '{$event->title}' berhasil dibuat. Link konfirmasi kehadiran & link presensi siap dibagikan."
            : "Kegiatan '{$event->title}' berhasil dibuat. Link presensi siap diakses.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'event_title' => $event->title,
                'rsvp_enabled' => (bool) $event->rsvp_enabled,
                'rsvp_url' => $rsvpUrl,
                'presence_url' => $presenceUrl,
                'event_show_url' => route('admin.events.show', $event->id),
            ]);
        }

        return back()->with('success', $successMessage);
    }

    public function show($id)
    {
        $event = Event::with(['presences.member', 'presences.user', 'creator', 'section.pac', 'workgroup.pac', 'program'])
            ->withCount(['presences', 'rsvps', 'attendingRsvps'])
            ->findOrFail($id);

        $user = auth()->user();
        $isPacScoped = $user->isOfficerPac() && ! ($user->isSuperAdmin() || $user->isAdminKota() || $user->role === 'admin');
        if ($isPacScoped) {
            $pacId = (int) $user->getManagedPacId();
            $eventPacId = $event->section?->pac_id ?? $event->workgroup?->pac_id;
            if ($eventPacId !== null && (int) $eventPacId !== $pacId) {
                abort(403, 'Anda hanya dapat melihat kegiatan di wilayah PAC Anda.');
            }
        }

        $presences = $event->presences()->latest('attended_at')->paginate(20)->withQueryString();
        $rsvps = $event->rsvps()->with(['member', 'user'])->latest('confirmed_at')->paginate(20, ['*'], 'rsvp_page')->withQueryString();

        $rsvpCounts = [
            'hadir' => $event->rsvps()->where('attendance_status', EventRsvp::STATUS_HADIR)->count(),
            'ragu' => $event->rsvps()->where('attendance_status', EventRsvp::STATUS_RAGU)->count(),
            'tidak_hadir' => $event->rsvps()->where('attendance_status', EventRsvp::STATUS_TIDAK_HADIR)->count(),
        ];
        $rsvpCounts['total'] = $rsvpCounts['hadir'] + $rsvpCounts['ragu'] + $rsvpCounts['tidak_hadir'];

        [$formSections, $formWorkgroups] = $this->resolveFormOwners($user);
        $sections = $formSections;

        return view('admin.events.show', compact('event', 'presences', 'rsvps', 'rsvpCounts', 'sections', 'formSections', 'formWorkgroups'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $this->normalizeOwnerInput($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section_id' => 'nullable|exists:sections,id',
            'workgroup_id' => 'nullable|exists:workgroups,id',
            'program_id' => 'nullable|exists:programs,id',
            'budget' => 'nullable|numeric|min:0',
            'location' => 'required|string|max:255',
            'method' => 'required|in:daring,luring',
            'meeting_link' => 'nullable|required_if:method,daring|url|max:500',
            'event_date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'presence_start_at' => 'required|date',
            'presence_end_at' => 'required|date|after_or_equal:presence_start_at',
            'rsvp_enabled' => 'nullable|boolean',
            'rsvp_deadline_at' => 'nullable|date',
            'status' => 'required|in:planned,completed,cancelled',
        ]);

        if ($request->has('rsvp_enabled')) {
            $validated['rsvp_enabled'] = $request->boolean('rsvp_enabled');
        } else {
            unset($validated['rsvp_enabled']);
        }

        if (! empty($validated['program_id']) && empty($validated['section_id']) && empty($validated['workgroup_id'])) {
            $prog = Program::find($validated['program_id']);
            $validated['section_id'] = $prog?->section_id;
            $validated['workgroup_id'] = $prog?->workgroup_id;
        }

        if (! empty($validated['workgroup_id'])) {
            $validated['section_id'] = null;
            $canManageNewOwner = auth()->user()->canManageWorkgroupProgramOrEvent((int) $validated['workgroup_id']);
        } else {
            $validated['workgroup_id'] = null;
            $newSectionId = ! empty($validated['section_id']) ? (int) $validated['section_id'] : null;
            $canManageNewOwner = auth()->user()->canManageSectionProgramOrEvent($newSectionId);
        }

        if (! auth()->user()->canManageEvent($event) || ! $canManageNewOwner) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk memperbarui kegiatan pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui kegiatan pada seksi / pokja ini.');
        }

        $event->update($validated);

        AuditLog::record(auth()->id(), 'Edit Kegiatan', "Memperbarui Kegiatan: {$event->title}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data kegiatan '{$event->title}' berhasil diperbarui.",
            ]);
        }

        return back()->with('success', "Data kegiatan '{$event->title}' berhasil diperbarui.");
    }

    public function uploadReport(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (! auth()->user()->canManageEvent($event)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah dokumen kegiatan pada seksi / pokja ini.');
        }

        $request->validate([
            'lpj_file' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB PDF
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5048',
        ]);

        // Upload LPJ PDF jika ada
        if ($request->hasFile('lpj_file')) {
            if ($event->lpj_file && Storage::disk('public')->exists($event->lpj_file)) {
                Storage::disk('public')->delete($event->lpj_file);
            }
            $event->lpj_file = $request->file('lpj_file')->store('event_lpj', 'public');
        }

        // Upload Photos jika ada
        if ($request->hasFile('photos')) {
            $existingPhotos = $event->documentation_photos ?? [];
            foreach ($request->file('photos') as $photoFile) {
                $path = $photoFile->store('event_photos', 'public');
                $existingPhotos[] = $path;
            }
            $event->documentation_photos = $existingPhotos;
        }

        // Tandai status kegiatan sebagai terlaksana jika belum
        if ($event->status !== 'completed') {
            $event->status = 'completed';
        }

        $event->save();

        AuditLog::record(auth()->id(), 'Upload Dokumen Kegiatan', "Upload LPJ/Foto untuk kegiatan: {$event->title}");

        return back()->with('success', 'Laporan Pertanggungjawaban (LPJ) & Foto Dokumentasi berhasil diunggah.');
    }

    public function deletePhoto(Request $request, $id, $index)
    {
        $event = Event::findOrFail($id);

        if (! auth()->user()->canManageEvent($event)) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus foto kegiatan pada seksi / pokja ini.');
        }

        $photos = $event->documentation_photos ?? [];

        if (isset($photos[$index])) {
            $photoPath = $photos[$index];
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            unset($photos[$index]);
            $event->documentation_photos = array_values($photos);
            $event->save();
        }

        return back()->with('success', 'Foto dokumentasi berhasil dihapus.');
    }

    public function addMediaLink(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (! auth()->user()->canManageEvent($event)) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan tautan pemberitaan pada kegiatan ini.');
        }

        $rawItems = $request->input('media_links');
        if (! is_array($rawItems)) {
            $rawItems = [[
                'media_name' => $request->input('media_name'),
                'title' => $request->input('title'),
                'url' => $request->input('url'),
            ]];
        }

        $filteredItems = array_values(array_filter($rawItems, function ($item) {
            return is_array($item) && (
                trim((string) ($item['url'] ?? '')) !== ''
                || trim((string) ($item['media_name'] ?? '')) !== ''
                || trim((string) ($item['title'] ?? '')) !== ''
            );
        }));

        $request->merge(['media_links' => $filteredItems]);

        $validated = $request->validate([
            'media_links' => 'required|array|min:1',
            'media_links.*.media_name' => 'nullable|string|max:150',
            'media_links.*.title' => 'nullable|string|max:255',
            'media_links.*.url' => 'required|url|max:1000',
        ], [
            'media_links.required' => 'Masukkan minimal satu tautan pemberitaan media online.',
            'media_links.*.url.required' => 'Link / URL pemberitaan wajib diisi.',
            'media_links.*.url.url' => 'Format link pemberitaan tidak valid (gunakan awalan https:// atau http://).',
        ]);

        $existingLinks = $event->media_links ?? [];

        foreach ($validated['media_links'] as $item) {
            $url = trim($item['url']);
            $mediaName = trim((string) ($item['media_name'] ?? ''));
            if ($mediaName === '') {
                $host = parse_url($url, PHP_URL_HOST) ?: 'Media Online';
                $mediaName = preg_replace('/^www\./i', '', $host);
            }

            $title = trim((string) ($item['title'] ?? ''));

            $existingLinks[] = [
                'media_name' => $mediaName,
                'title' => $title !== '' ? $title : null,
                'url' => $url,
                'added_at' => now()->toIso8601String(),
            ];
        }

        $event->media_links = $existingLinks;
        $event->save();

        $addedCount = count($validated['media_links']);
        AuditLog::record(auth()->id(), 'Tambah Pemberitaan Media', "Menambahkan {$addedCount} tautan pemberitaan media online untuk kegiatan: {$event->title}");

        return back()->with('success', "{$addedCount} tautan pemberitaan media online berhasil ditambahkan.");
    }

    public function deleteMediaLink(Request $request, $id, $index)
    {
        $event = Event::findOrFail($id);

        if (! auth()->user()->canManageEvent($event)) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus tautan pemberitaan pada kegiatan ini.');
        }

        $mediaLinks = $event->media_links ?? [];

        if (isset($mediaLinks[$index])) {
            unset($mediaLinks[$index]);
            $event->media_links = array_values($mediaLinks);
            $event->save();

            AuditLog::record(auth()->id(), 'Hapus Pemberitaan Media', "Menghapus tautan pemberitaan media online dari kegiatan: {$event->title}");
        }

        return back()->with('success', 'Tautan pemberitaan media online berhasil dihapus.');
    }

    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (! auth()->user()->canManageEvent($event)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menghapus kegiatan pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kegiatan pada seksi / pokja ini.');
        }

        $title = $event->title;

        // Delete LPJ
        if ($event->lpj_file && Storage::disk('public')->exists($event->lpj_file)) {
            Storage::disk('public')->delete($event->lpj_file);
        }

        // Delete Photos
        if ($event->documentation_photos) {
            foreach ($event->documentation_photos as $p) {
                if (Storage::disk('public')->exists($p)) {
                    Storage::disk('public')->delete($p);
                }
            }
        }

        $event->delete();

        AuditLog::record(auth()->id(), 'Hapus Kegiatan', "Menghapus kegiatan: {$title}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kegiatan '{$title}' berhasil dihapus.",
            ]);
        }

        return redirect()->route('admin.events.index')->with('success', "Kegiatan '{$title}' berhasil dihapus.");
    }
}
