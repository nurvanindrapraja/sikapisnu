<?php

namespace App\Http\Controllers;

use App\Models\Card;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VerificationController extends Controller
{
    public function verify($qr_token)
    {
        $card = Card::where('qr_token', $qr_token)
            ->where('is_active', true)
            ->with(['member.activePosition', 'member.mwc', 'member.pac'])
            ->first();

        if (! $card) {
            return view('verify', [
                'isValid' => false,
                'card' => null,
                'member' => null,
                'message' => 'Kartu Anggota tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        $member = $card->member;

        if (! in_array($member->membership_status, ['terverifikasi', 'pengurus'])) {
            return view('verify', [
                'isValid' => false,
                'card' => $card,
                'member' => $member,
                'message' => 'Status keanggotaan belum terverifikasi atau tidak aktif.',
            ]);
        }

        return view('verify', [
            'isValid' => true,
            'card' => $card,
            'member' => $member,
            'message' => 'Kartu Anggota ISNU Kota Surabaya Terverifikasi Sah.',
        ]);
    }

    public function qrImage($qr_token)
    {
        $url = route('verify.card', ['qr_token' => $qr_token]);

        // Gunakan SVG format agar tidak membutuhkan ekstensi imagick
        $qrCode = QrCode::size(180)
            ->format('svg')
            ->margin(1)
            ->generate($url);

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }
}
