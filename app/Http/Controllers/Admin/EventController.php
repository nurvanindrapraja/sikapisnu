<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\Member;
use App\Models\Pac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::withCount('presences');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->latest('event_date')->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('admin.events.partials.event_list', compact('events'));
        }

        return view('admin.events.index', compact('events'));
    }

    public function rekapPresensi(Request $request)
    {
        $query = Member::withCount('presences')
            ->with(['pac', 'mwc', 'activePosition.pac', 'activePosition.section', 'presences' => function ($q) {
                $q->with('event')->latest('attended_at');
            }]);

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
            $query->where('membership_status', $request->status);
        }

        if ($request->filled('pac_id')) {
            $query->where('pac_id', $request->pac_id);
        }

        $sort = $request->get('sort', 'most_active');
        if ($sort === 'most_active') {
            $query->orderByDesc('presences_count')->latest();
        } else {
            $query->latest();
        }

        $members = $query->paginate(15)->withQueryString();
        $pacs = Pac::orderBy('name')->get();

        $stats = [
            'total_kader_hadir' => Member::has('presences')->count(),
            'total_presences' => EventPresence::count(),
            'top_member' => Member::withCount('presences')->orderByDesc('presences_count')->first(),
        ];

        if ($request->ajax()) {
            return view('admin.presensi.partials.rekap_list', compact('members'));
        }

        return view('admin.presensi.rekap', compact('members', 'pacs', 'stats'));
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

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'member_number' => $member->member_number ?? 'NIK: '.$member->nik,
                'photo_url' => $member->photo_url,
                'membership_status' => $member->membership_status,
                'position_title' => $member->activePosition ? $member->activePosition->position_title : null,
                'pac_name' => $member->pac ? $member->pac->name : ($member->kecamatan ? $member->kecamatan : '-'),
                'presences_count' => $member->presences->count(),
            ],
            'presences' => $member->presences->map(function ($p) {
                return [
                    'id' => $p->id,
                    'event_title' => $p->event->title ?? 'Kegiatan ISNU',
                    'event_date' => $p->event ? $p->event->event_date->translatedFormat('d F Y') : '-',
                    'attended_at' => $p->attended_at ? $p->attended_at->translatedFormat('d F Y H:i') : '-',
                    'method' => $p->event->method ?? 'luring',
                    'location' => $p->event->location ?? '-',
                    'institution_or_pac' => $p->institution_or_pac,
                    'notes' => $p->notes,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'method' => 'required|in:daring,luring',
            'meeting_link' => 'nullable|required_if:method,daring|url|max:500',
            'event_date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'presence_start_at' => 'required|date',
            'presence_end_at' => 'required|date|after_or_equal:presence_start_at',
            'status' => 'required|in:planned,completed,cancelled',
        ]);

        $validated['unique_code'] = 'evt-'.Str::slug(substr($validated['title'], 0, 30)).'-'.Str::random(6);
        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);

        AuditLog::record(auth()->id(), 'Tambah Kegiatan', "Menambahkan Kegiatan: {$event->title}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kegiatan '{$event->title}' berhasil dibuat. Link presensi siap diakses.",
            ]);
        }

        return back()->with('success', "Kegiatan '{$event->title}' berhasil dibuat. Link presensi siap diakses.");
    }

    public function show($id)
    {
        $event = Event::with(['presences.member', 'presences.user', 'creator'])->withCount('presences')->findOrFail($id);
        $presences = $event->presences()->latest('attended_at')->paginate(20)->withQueryString();

        return view('admin.events.show', compact('event', 'presences'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'method' => 'required|in:daring,luring',
            'meeting_link' => 'nullable|required_if:method,daring|url|max:500',
            'event_date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'presence_start_at' => 'required|date',
            'presence_end_at' => 'required|date|after_or_equal:presence_start_at',
            'status' => 'required|in:planned,completed,cancelled',
        ]);

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

    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);
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
