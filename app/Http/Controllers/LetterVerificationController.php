<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
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
        $letter = Letter::with(['attachments', 'htmlAttachments'])->where('letter_code', $code)->firstOrFail();

        return view('public.letter_verification', compact('letter'));
    }

    /**
     * Pratinjau berkas lampiran surat langsung di browser (inline).
     */
    public function previewAttachment(string $code, int $attachmentId): BinaryFileResponse
    {
        $letter = Letter::where('letter_code', $code)->firstOrFail();
        $attachment = $letter->attachments()->findOrFail($attachmentId);
        $fullPath = Storage::disk('public')->path($attachment->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan server.');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    /**
     * Unduh berkas lampiran surat dari halaman verifikasi publik.
     */
    public function downloadAttachment(string $code, int $attachmentId): BinaryFileResponse
    {
        $letter = Letter::where('letter_code', $code)->firstOrFail();
        $attachment = $letter->attachments()->findOrFail($attachmentId);
        $fullPath = Storage::disk('public')->path($attachment->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'File lampiran tidak ditemukan di penyimpanan server.');
        }

        return response()->download($fullPath, $attachment->file_name);
    }

    /**
     * Unduh salinan PDF resmi dari halaman verifikasi publik.
     */
    public function downloadPdf(Request $request, string $code, LetterService $letterService): BinaryFileResponse
    {
        $letter = Letter::with([
            'attachments' => fn ($query) => $query->orderBy('id'),
            'htmlAttachments',
        ])
            ->where('letter_code', $code)
            ->firstOrFail();

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
