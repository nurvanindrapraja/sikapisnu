<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPresence;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventPresenceController extends Controller
{
    public function showPresenceForm($code)
    {
        $event = Event::where('unique_code', $code)->firstOrFail();

        $status = $event->presenceStatus();
        $message = $event->presence_message;

        $user = Auth::user();
        $member = $user ? $user->member : null;

        $alreadyAttended = false;
        if ($user && $member) {
            $alreadyAttended = EventPresence::where('event_id', $event->id)
                ->where(function ($q) use ($user, $member) {
                    $q->where('member_id', $member->id)
                        ->orWhere('user_id', $user->id);
                })
                ->exists();
        }

        return view('events.presence', compact('event', 'status', 'message', 'user', 'member', 'alreadyAttended'));
    }

    public function submitPresence(Request $request, $code)
    {
        $event = Event::where('unique_code', $code)->firstOrFail();

        // 1. Verify Active Presence Duration
        if (! $event->isPresenceActive()) {
            return back()->with('error', $event->presence_message);
        }

        $user = Auth::user();
        $member = $user ? $user->member : null;

        // 2. Validate Input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik_or_member_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'institution_or_pac' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // 3. Prevent Double Submissions
        if ($member) {
            $exists = EventPresence::where('event_id', $event->id)->where('member_id', $member->id)->exists();
            if ($exists) {
                return back()->with('info', 'Anda sudah melakukan presensi untuk kegiatan ini sebelumnya.');
            }
        } elseif ($user) {
            $exists = EventPresence::where('event_id', $event->id)->where('user_id', $user->id)->exists();
            if ($exists) {
                return back()->with('info', 'Anda sudah melakukan presensi untuk kegiatan ini sebelumnya.');
            }
        } else {
            // Unauthenticated guest check by phone / member number in same event
            if (! empty($validated['phone']) || ! empty($validated['nik_or_member_number'])) {
                $exists = EventPresence::where('event_id', $event->id)
                    ->where(function ($q) use ($validated) {
                        if (! empty($validated['phone'])) {
                            $q->where('phone', $validated['phone']);
                        }
                        if (! empty($validated['nik_or_member_number'])) {
                            $q->orWhere('nik_or_member_number', $validated['nik_or_member_number']);
                        }
                    })
                    ->exists();

                if ($exists) {
                    return back()->with('info', 'Presensi dengan nomor HP / Anggota tersebut sudah tercatat sebelumnya.');
                }
            }
        }

        // 4. Create Presence Record
        EventPresence::create([
            'event_id' => $event->id,
            'member_id' => $member?->id,
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'nik_or_member_number' => $validated['nik_or_member_number'] ?? $member?->member_number,
            'phone' => $validated['phone'] ?? $member?->phone ?? $user?->phone,
            'institution_or_pac' => $validated['institution_or_pac'] ?? $member?->pac?->name ?? $member?->mwc?->name ?? 'ISNU Kota Surabaya',
            'notes' => $validated['notes'] ?? null,
            'attended_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('event.presence.show', $code)->with('success', 'Presensi kehadiran Anda telah berhasil disimpan!');
    }
}
