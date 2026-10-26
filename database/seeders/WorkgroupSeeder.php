<?php

namespace Database\Seeders;

use App\Models\Pac;
use App\Models\Workgroup;
use Illuminate\Database\Seeder;

class WorkgroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pcWorkgroups = [
            [
                'name' => 'Kelompok Kerja Transformasi Digital & Sistem Informasi',
                'code' => 'PKJ-DIGITAL',
                'description' => 'Pokja lintas seksi penanggung jawab pengembangan infrastruktur digital dan platform SIKAP ISNU.',
            ],
            [
                'name' => 'Kelompok Kerja Inkubasi Bisnis & Ekonomi Kreatif',
                'code' => 'PKJ-EKOKREATIF',
                'description' => 'Pokja khusus penguatan jaringan wirausaha dan inkubasi usaha kader sarjana NU.',
            ],
            [
                'name' => 'Kelompok Kerja Advokasi Kebijakan Publik & Hukum',
                'code' => 'PKJ-ADVOKASI',
                'description' => 'Pokja perumusan rekomendasi strategis dan pendampingan hukum publik warga kota.',
            ],
            [
                'name' => 'Kelompok Kerja Riset Beasiswa & Pengembangan Akademik',
                'code' => 'PKJ-BEASISWA',
                'description' => 'Pokja fasilitasi beasiswa S2/S3, publikasi ilmiah, dan riset strategis perkotaan.',
            ],
        ];

        foreach ($pcWorkgroups as $wg) {
            Workgroup::firstOrCreate([
                'level' => 'PC ISNU',
                'name' => $wg['name'],
            ], [
                'code' => $wg['code'],
                'description' => $wg['description'],
                'is_active' => true,
            ]);
        }

        $pac = Pac::first();
        if ($pac) {
            Workgroup::firstOrCreate([
                'level' => 'PAC ISNU',
                'pac_id' => $pac->id,
                'name' => 'Kelompok Kerja Kaderisasi Akar Rumput & Intelektual Desa',
            ], [
                'code' => 'PKJ-PAC-'.$pac->id,
                'description' => 'Pokja khusus percepatan pemetaan sarjana pada tingkat kecamatan/ranting.',
                'is_active' => true,
            ]);
        }
    }
}
