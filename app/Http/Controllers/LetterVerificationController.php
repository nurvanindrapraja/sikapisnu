<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LetterVerificationController extends Controller
{
    /**
     * Halaman verifikasi publik keaslian surat dan e-TTD.
     */
    public function verify(string $code): View
    {
        $letter = Letter::where('letter_code', $code)->firstOrFail();

        return view('public.letter_verification', compact('letter'));
    }

    /**
     * Unduh salinan PDF resmi dari halaman verifikasi publik.
     */
    public function downloadPdf(string $code, LetterService $letterService): BinaryFileResponse
    {
        $letter = Letter::where('letter_code', $code)->firstOrFail();

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
