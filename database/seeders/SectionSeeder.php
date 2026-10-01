<?php

namespace Database\Seeders;

use App\Models\Pac;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seksi PC ISNU Kota Surabaya
        $pcSections = [
            'Seksi KADERISASI, PENGUATAN IDEOLOGI, DAN KERJASAMA ANTAR LEMBAGA',
            'Seksi KEWIRAUSAHAAN DAN PEMBERDAYAAN UMAT',
            'Seksi SAINS, TEKNOLOGI, DAN RISET',
            'Seksi PENDIDIKAN DAN KEBUDAYAAN',
            'Seksi HUKUM, HAM, DAN ADVOKASI MASYARAKAT',
            'Seksi KESEHATAN DAN KESEJAHTERAAN MASYARAKAT',
            'Seksi MEDIA, INFORMASI, DAN KOMUNIKASI',
        ];

        foreach ($pcSections as $name) {
            Section::firstOrCreate([
                'level' => 'PC ISNU',
                'name' => $name,
            ], [
                'description' => 'Seksi dalam Kepengurusan PC ISNU Kota Surabaya',
            ]);
        }

        // 2. Seksi PAC ISNU (Default untuk PAC Gayungan & PAC Lainnya)
        $pacSections = [
            'Seksi Sains dan Teknologi',
            'Seksi Kemasyarakatan',
            'Seksi Keagamaan dan Kaderisasi',
            'Seksi Ekonomi dan Umat',
        ];

        $pacs = Pac::all();
        foreach ($pacs as $pac) {
            foreach ($pacSections as $name) {
                Section::firstOrCreate([
                    'level' => 'PAC ISNU',
                    'pac_id' => $pac->id,
                    'name' => $name,
                ], [
                    'description' => 'Seksi dalam Kepengurusan PAC ISNU '.$pac->name,
                ]);
            }
        }
    }
}
