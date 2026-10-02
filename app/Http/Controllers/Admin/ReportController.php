<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.laporan.index');
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'anggota'); // anggota, potensi, pengurus
        $format = $request->get('format', 'csv'); // csv

        $query = Member::with(['mwc', 'pac', 'educations', 'employments', 'nuTrainings', 'certifications', 'activePosition']);

        if (auth()->user()->isAdminPac()) {
            $pacId = auth()->user()->getManagedPacId();
            $kecamatan = auth()->user()->member?->kecamatan;
            $query->where(function ($q) use ($pacId, $kecamatan) {
                if ($pacId) {
                    $q->where('pac_id', $pacId);
                }
                if ($kecamatan) {
                    $q->orWhere('kecamatan', 'like', "%{$kecamatan}%");
                }
            });
        }

        if ($type === 'pengurus') {
            $query->where('membership_status', 'pengurus');
        } elseif ($type === 'terverifikasi') {
            $query->where('membership_status', 'terverifikasi');
        }

        $members = $query->latest()->get();

        AuditLog::record(auth()->id(), 'Export Laporan', "Mengeksport laporan {$type} ke format ".strtoupper($format));

        if ($format === 'csv') {
            $filename = "laporan_{$type}_isnu_surabaya_".date('Ymd_His').'.csv';
            $headers = [
                'Content-type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename={$filename}",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($members, $type) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM
                fwrite($file, "\xEF\xBB\xBF");

                if ($type === 'pengurus') {
                    fputcsv($file, ['No', 'Nomor Anggota', 'Nama Lengkap', 'Jabatan', 'Periode', 'MWC/Kecamatan', 'No HP', 'Email', 'Profesi/Pekerjaan']);
                    foreach ($members as $index => $m) {
                        $pos = $m->activePosition;
                        fputcsv($file, [
                            $index + 1,
                            $m->member_number ?? '-',
                            $m->full_name,
                            $pos ? $pos->position_title : 'Pengurus',
                            $pos ? $pos->period : '-',
                            $m->mwc ? $m->mwc->name : '-',
                            $m->phone,
                            $m->email,
                            $m->occupation,
                        ]);
                    }
                } else {
                    fputcsv($file, ['No', 'Nomor Anggota', 'NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Kecamatan', 'No HP', 'Email', 'Pekerjaan', 'Status Keanggotaan', 'Pendidikan Tertinggi', 'Kaderisasi NU']);
                    foreach ($members as $index => $m) {
                        $lastEdu = $m->educations->last();
                        $lastNu = $m->nuTrainings->last();
                        fputcsv($file, [
                            $index + 1,
                            $m->member_number ?? '-',
                            $m->nik ?? '-',
                            $m->full_name,
                            $m->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                            $m->kecamatan,
                            $m->phone,
                            $m->email,
                            $m->occupation,
                            strtoupper($m->membership_status),
                            $lastEdu ? ($lastEdu->level.' '.$lastEdu->institution_name) : '-',
                            $lastNu ? $lastNu->training_type : '-',
                        ]);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return back()->with('info', 'Format export tidak didukung.');
    }
}
