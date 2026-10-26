<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = Section::all();
        if ($sections->isEmpty()) {
            return;
        }

        foreach ($sections as $section) {
            $nameLower = strtolower($section->name);

            if (str_contains($nameLower, 'kaderisasi') || str_contains($nameLower, 'ideologi')) {
                Program::firstOrCreate([
                    'section_id' => $section->id,
                    'name' => 'Program Penguatan Ideologi ASWAJA dan Kaderisasi Intelektual',
                ], [
                    'code' => 'PRG-KDR-01',
                    'indicator' => 'Terlaksananya Pendidikan Kader dan pembinaan ideologi kader sarjana',
                    'target' => 'Minimal 2 angkatan pelatihan dan 100 kader sarjana tersertifikasi',
                    'description' => 'Program berkesinambungan mencetak kader pemikir dan organisatoris yang militan.',
                    'year' => '2026',
                    'is_active' => true,
                ]);
            } elseif (str_contains($nameLower, 'sains') || str_contains($nameLower, 'teknologi') || str_contains($nameLower, 'riset')) {
                Program::firstOrCreate([
                    'section_id' => $section->id,
                    'name' => 'Program Riset Strategis Perkotaan dan Inovasi Teknologi',
                ], [
                    'code' => 'PRG-STEK-01',
                    'indicator' => 'Publikasi policy brief dan riset terapan kebijakan daerah berbasis data',
                    'target' => '4 Kajian Kebijakan Publik dan 1 Sistem Informasi Digital Aktif',
                    'description' => 'Fasilitasi riset dan adopsi transformasi digital untuk kemaslahatan warga.',
                    'year' => '2026',
                    'is_active' => true,
                ]);
            } elseif (str_contains($nameLower, 'kewirausahaan') || str_contains($nameLower, 'ekonomi')) {
                Program::firstOrCreate([
                    'section_id' => $section->id,
                    'name' => 'Program Inkubasi Bisnis dan Pemberdayaan Ekonomi Kader',
                ], [
                    'code' => 'PRG-EKO-01',
                    'indicator' => 'Jumlah pelaku usaha binaan kader ISNU yang mendapatkan pendampingan',
                    'target' => '25 UMKM Kader ISNU terfasilitasi legalitas dan akses pembiayaan',
                    'description' => 'Peningkatan kapasitas kewirausahaan dan sinergi bisnis antar sarjana NU.',
                    'year' => '2026',
                    'is_active' => true,
                ]);
            } else {
                Program::firstOrCreate([
                    'section_id' => $section->id,
                    'name' => 'Program Kerja Unggulan '.$section->name,
                ], [
                    'code' => 'PRG-SKS-'.$section->id,
                    'indicator' => 'Tingkat ketercapaian agenda kerja seksi',
                    'target' => '100% rencana kerja bidang terselenggara tepat waktu',
                    'description' => 'Program kerja operasional pendukung visi misi kepengurusan.',
                    'year' => '2026',
                    'is_active' => true,
                ]);
            }
        }
    }
}
