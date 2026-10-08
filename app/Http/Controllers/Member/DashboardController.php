<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $member = $user->member()
            ->with([
                'mwc',
                'pac',
                'activeCard',
                'activePosition',
                'educations',
                'organizations',
                'employments',
                'nuTrainings',
                'certifications',
                'statusHistories.changer',
                'cardOrders',
            ])
            ->first();

        if (! $member) {
            return redirect()->route('register')->with('info', 'Silakan lengkapi pendaftaran anggota Anda.');
        }

        return view('member.dashboard', compact('user', 'member'));
    }

    public function downloadCard(Request $request)
    {
        $user = auth()->user();
        $member = $user->member()->with(['activeCard', 'activePosition', 'mwc', 'pac'])->firstOrFail();

        if (! in_array($member->membership_status, ['terverifikasi', 'pengurus']) || ! $member->activeCard) {
            return back()->with('error', 'Kartu Digital belum tersedia karena status Anda belum terverifikasi.');
        }

        $card = $member->activeCard;
        $isOfficer = ($card && $card->card_type === 'OFFICER') || $member->membership_status === 'pengurus';
        $activePosition = $member->activePosition;

        $logoPath = public_path('images/logo_isnu.png');
        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

        $suroboyoPath = public_path('images/suroboyo_icon.png');
        $suroboyoSrc = file_exists($suroboyoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($suroboyoPath)) : '';

        $photoSrc = '';
        if ($member->photo && file_exists(storage_path('app/public/'.$member->photo))) {
            $path = storage_path('app/public/'.$member->photo);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $photoSrc = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($path));
        } elseif ($member->photo_url) {
            try {
                $photoSrc = 'data:image/png;base64,'.base64_encode(file_get_contents($member->photo_url));
            } catch (\Throwable $e) {
                $photoSrc = '';
            }
        }

        $qrToken = $card->qr_token ?? 'preview';
        $verifyUrl = route('verify.card', ['qr_token' => $qrToken]);
        try {
            $qrSvg = QrCode::size(120)->format('svg')->margin(1)->generate($verifyUrl);
            $qrBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrBase64 = '';
        }

        $pdf = Pdf::loadView('pdf.card_digital', compact(
            'member',
            'card',
            'isOfficer',
            'activePosition',
            'logoSrc',
            'suroboyoSrc',
            'photoSrc',
            'qrBase64'
        ))->setPaper([0, 0, 480, 303]);

        $filename = 'Kartu_Digital_ISNU_'.Str::slug($member->full_name).'.pdf';

        return $pdf->download($filename);
    }

    public function downloadCv()
    {
        $user = auth()->user();
        $member = $user->member()
            ->with([
                'mwc',
                'pac',
                'activePosition',
                'educations',
                'organizations',
                'employments',
                'nuTrainings',
                'certifications',
            ])
            ->firstOrFail();

        $downloadTimestamp = now()->translatedFormat('d F Y, H:i:s').' WIB';
        $downloadedBy = $user->name.' ('.$user->email.')';

        if (! in_array($member->membership_status, ['terverifikasi', 'pengurus'])) {
            return back()->with('error', 'CV (PDF) hanya dapat diunduh jika status Anda sudah terverifikasi sebagai anggota.');
        }

        $pdf = Pdf::loadView('pdf.cv_member', compact('member', 'downloadTimestamp', 'downloadedBy'))
            ->setPaper('a4', 'portrait');

        $filename = 'CV_ISNU_'.Str::slug($member->full_name).'.pdf';

        return $pdf->download($filename);
    }
}
