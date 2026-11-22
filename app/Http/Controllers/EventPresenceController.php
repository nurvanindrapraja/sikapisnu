<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPresence;
use App\Models\EventRsvp;
use App\Models\Member;
use App\Models\Pac;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventPresenceController extends Controller
{
    /**
     * Resolve member record and default institution/PAC label for the authenticated user.
     *
     * @return array{0: ?Member, 1: string}
     */
    protected function resolveAuthenticatedMemberContext(?User $user): array
    {
        $member = null;
        $defaultInstitution = '';

        if (! $user) {
            return [$member, $defaultInstitution];
        }

        $member = Member::where('user_id', $user->id)
            ->with(['pac', 'mwc', 'activePosition.pac', 'activePosition.section'])
            ->first();

        if ($member) {
            $pos = $member->activePosition;
            if ($pos) {
                if (in_array($pos->level, ['PC ISNU', 'Kota', 'PC'])) {
                    $defaultInstitution = 'PC ISNU Kota Surabaya';
                    if ($pos->section) {
                        $defaultInstitution .= ' - '.$pos->section->name;
                    }
                } elseif (in_array($pos->level, ['PAC ISNU', 'PAC'])) {
                    $pacName = $pos->pac ? $pos->pac->name : ($member->pac ? $member->pac->name : $member->kecamatan);
                    $defaultInstitution = 'PAC ISNU '.$pacName;
                    if ($pos->section) {
                        $defaultInstitution .= ' - '.$pos->section->name;
                    }
                } else {
                    $defaultInstitution = 'PC ISNU Kota Surabaya';
                }
            } else {
                if ($member->pac) {
                    $defaultInstitution = 'PAC ISNU '.$member->pac->name;
                } elseif ($member->kecamatan) {
                    $pac = Pac::where('kecamatan', 'like', "%{$member->kecamatan}%")
                        ->orWhere('name', 'like', "%{$member->kecamatan}%")
                        ->first();
                    $defaultInstitution = 'PAC ISNU '.($pac ? $pac->name : ucfirst(strtolower($member->kecamatan)));
                } elseif ($member->mwc) {
                    $defaultInstitution = 'MWC NU '.$member->mwc->name;
                } else {
                    $defaultInstitution = 'PC ISNU Kota Surabaya';
                }
            }
        } else {
            $defaultInstitution = 'PC ISNU Kota Surabaya';
        }

        return [$member, $defaultInstitution];
    }

    public function showPresenceForm($code)
    {
        $event = Event::where('unique_code', $code)->firstOrFail();

        $status = $event->presenceStatus();
        $message = $event->presence_message;

        $user = Auth::user();
        [$member, $defaultInstitution] = $this->resolveAuthenticatedMemberContext($user);

        $alreadyAttended = false;
        if ($user && $member) {
            $alreadyAttended = EventPresence::where('event_id', $event->id)
                ->where(function ($q) use ($user, $member) {
                    $q->where('member_id', $member->id)
                        ->orWhere('user_id', $user->id);
                })
                ->exists();
        }

        return view('events.presence', compact('event', 'status', 'message', 'user', 'member', 'alreadyAttended', 'defaultInstitution'));
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

    public function showRsvpForm(string $code)
    {
        $event = Event::where('unique_code', $code)->firstOrFail();

        $rsvpStatus = $event->rsvpStatus();
        $rsvpMessage = $event->rsvp_message;

        $user = Auth::user();
        [$member, $defaultInstitution] = $this->resolveAuthenticatedMemberContext($user);

        $existingRsvp = null;
        if ($user) {
            $existingRsvp = EventRsvp::where('event_id', $event->id)
                ->where(function ($q) use ($user, $member) {
                    if ($member) {
                        $q->where('member_id', $member->id)
                            ->orWhere('user_id', $user->id);
                    } else {
                        $q->where('user_id', $user->id);
                    }
                })
                ->first();
        }

        $attendingCount = $event->attendingRsvps()->count();

        return view('events.rsvp', compact(
            'event',
            'rsvpStatus',
            'rsvpMessage',
            'user',
            'member',
            'existingRsvp',
            'defaultInstitution',
            'attendingCount'
        ));
    }

    public function submitRsvp(Request $request, string $code)
    {
        $event = Event::where('unique_code', $code)->firstOrFail();

        if (! $event->isRsvpOpen()) {
            return back()->with('error', $event->rsvp_message);
        }

        $user = Auth::user();
        $member = $user ? $user->member : null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attendance_status' => 'required|in:hadir,ragu,tidak_hadir',
            'nik_or_member_number' => 'nullable|string|max:100',
            'phone' => 'required|string|max:30',
            'institution_or_pac' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'attendance_status.required' => 'Pilihan konfirmasi kehadiran wajib dipilih.',
            'attendance_status.in' => 'Status konfirmasi kehadiran tidak valid.',
            'phone.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'institution_or_pac.required' => 'Asal utusan / PAC ISNU / instansi wajib diisi.',
        ]);

        $existing = null;
        if ($member) {
            $existing = EventRsvp::where('event_id', $event->id)
                ->where(function ($q) use ($member, $user) {
                    $q->where('member_id', $member->id)
                        ->orWhere('user_id', $user->id);
                })
                ->first();
        } elseif ($user) {
            $existing = EventRsvp::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $existing = EventRsvp::where('event_id', $event->id)
                ->where(function ($q) use ($validated) {
                    $q->where('phone', $validated['phone']);
                    if (! empty($validated['nik_or_member_number'])) {
                        $q->orWhere('nik_or_member_number', $validated['nik_or_member_number']);
                    }
                })
                ->first();
        }

        $payload = [
            'member_id' => $member?->id,
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'nik_or_member_number' => $validated['nik_or_member_number'] ?? $member?->member_number,
            'phone' => $validated['phone'] ?? $member?->phone ?? $user?->phone,
            'institution_or_pac' => $validated['institution_or_pac'],
            'attendance_status' => $validated['attendance_status'],
            'notes' => $validated['notes'] ?? null,
            'confirmed_at' => now(),
            'ip_address' => $request->ip(),
        ];

        if ($existing) {
            $existing->update($payload);

            return redirect()->route('event.rsvp.show', $code)
                ->with('success', 'Konfirmasi kehadiran Anda berhasil diperbarui menjadi: '.$existing->status_label.'.');
        }

        $rsvp = EventRsvp::create(array_merge(['event_id' => $event->id], $payload));

        return redirect()->route('event.rsvp.show', $code)
            ->with('success', 'Konfirmasi kehadiran Anda ('.$rsvp->status_label.') telah berhasil disimpan! Terima kasih.');
    }
}
