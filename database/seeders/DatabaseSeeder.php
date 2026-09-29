<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Card;
use App\Models\Member;
use App\Models\MemberCertification;
use App\Models\MemberEducation;
use App\Models\MemberEmployment;
use App\Models\MemberNuTraining;
use App\Models\MembershipStatusHistory;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data MWC (Kecamatan di Kota Surabaya)
        $mwcData = [
            ['code' => 'MWC-01', 'name' => 'MWC NU Wonokromo'],
            ['code' => 'MWC-02', 'name' => 'MWC NU Gubeng'],
            ['code' => 'MWC-03', 'name' => 'MWC NU Tegalsari'],
            ['code' => 'MWC-04', 'name' => 'MWC NU Sukolilo'],
            ['code' => 'MWC-05', 'name' => 'MWC NU Rungkut'],
            ['code' => 'MWC-06', 'name' => 'MWC NU Jambangan'],
            ['code' => 'MWC-07', 'name' => 'MWC NU Sawahan'],
            ['code' => 'MWC-08', 'name' => 'MWC NU Genteng'],
            ['code' => 'MWC-09', 'name' => 'MWC NU Tambaksari'],
            ['code' => 'MWC-10', 'name' => 'MWC NU Wonocolo'],
        ];

        $mwcModels = [];
        foreach ($mwcData as $data) {
            $mwc = Mwc::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'city' => 'Kota Surabaya',
            ]);
            $mwcModels[$data['code']] = $mwc;

            // Generate PAC sampel
            Pac::create([
                'mwc_id' => $mwc->id,
                'code' => 'PAC-'.$mwc->id.'-1',
                'name' => 'PAC ISNU '.str_replace('MWC NU ', '', $data['name']).' Barat',
            ]);
            Pac::create([
                'mwc_id' => $mwc->id,
                'code' => 'PAC-'.$mwc->id.'-2',
                'name' => 'PAC ISNU '.str_replace('MWC NU ', '', $data['name']).' Timur',
            ]);
        }

        // 2. Default Accounts
        $superAdmin = User::create([
            'name' => 'Super Administrator ISNU',
            'email' => 'superadmin@isnu-surabaya.or.id',
            'phone' => '081234567890',
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $adminKota = User::create([
            'name' => 'Admin PC ISNU Surabaya',
            'email' => 'admin@isnu-surabaya.or.id',
            'phone' => '081234567891',
            'role' => 'admin_kota',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // 3. User & Member Sample 1: PENGURUS (Dr. H. Ahmad Bashri, M.Pd.)
        $userPengurus = User::create([
            'name' => 'Dr. H. Ahmad Bashri, M.Pd.',
            'email' => 'ahmad.bashri@isnu-surabaya.or.id',
            'phone' => '081298765432',
            'role' => 'member',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $mwcWonokromo = $mwcModels['MWC-01'];
        $pacWonokromo = $mwcWonokromo->pacs->first();

        $memberPengurus = Member::create([
            'user_id' => $userPengurus->id,
            'member_number' => 'ISNU-SBY-26-000001',
            'nik' => '3578011205800001',
            'full_name' => 'Dr. H. Ahmad Bashri, M.Pd.',
            'birth_place' => 'Surabaya',
            'birth_date' => '1980-05-12',
            'gender' => 'L',
            'address' => 'Jl. Raya Darmo No. 45',
            'kelurahan' => 'Darmo',
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'phone' => '081298765432',
            'email' => 'ahmad.bashri@isnu-surabaya.or.id',
            'occupation' => 'Dosen / Akademisi',
            'photo' => null,
            'membership_status' => 'pengurus',
            'mwc_id' => $mwcWonokromo->id,
            'pac_id' => $pacWonokromo->id,
            'verified_at' => now()->subDays(30),
            'verified_by' => $adminKota->id,
        ]);

        // Riwayat Pendidikan Pengurus
        MemberEducation::create([
            'member_id' => $memberPengurus->id,
            'level' => 'S3',
            'institution_name' => 'Universitas Negeri Surabaya',
            'major' => 'Manajemen Pendidikan',
            'start_year' => 2015,
            'end_year' => 2019,
            'degree' => 'Dr.',
        ]);
        MemberEducation::create([
            'member_id' => $memberPengurus->id,
            'level' => 'S2',
            'institution_name' => 'UIN Sunan Ampel Surabaya',
            'major' => 'Pendidikan Agama Islam',
            'start_year' => 2008,
            'end_year' => 2011,
            'degree' => 'M.Pd.',
        ]);

        // Riwayat Pekerjaan Pengurus
        MemberEmployment::create([
            'member_id' => $memberPengurus->id,
            'company_name' => 'UIN Sunan Ampel Surabaya',
            'position' => 'Dosen Tetap',
            'field' => 'Pendidikan',
            'start_year' => 2012,
            'employment_status' => 'ASN',
            'description' => 'Mengajar mata kuliah Manajemen Organisasi Pendidikan',
        ]);

        // Riwayat Kaderisasi NU
        MemberNuTraining::create([
            'member_id' => $memberPengurus->id,
            'training_type' => 'PKN',
            'organizer' => 'PP ISNU',
            'year' => 2022,
            'location' => 'Jakarta',
            'certificate_number' => 'PKN-ISNU-2022-089',
        ]);

        // Sertifikasi
        MemberCertification::create([
            'member_id' => $memberPengurus->id,
            'certification_name' => 'Sertifikat Pendidik Dosen',
            'issuing_organization' => 'Kementerian Agama RI',
            'issue_year' => 2015,
            'field' => 'Pendidikan',
        ]);

        // Jabatan Pengurus
        Position::create([
            'member_id' => $memberPengurus->id,
            'position_title' => 'Ketua Umum',
            'level' => 'Kota',
            'period' => '2026-2030',
            'period_start' => '2026-01-01',
            'period_end' => '2030-12-31',
            'sk_number' => 'SK-PW-ISNU-JATIM/2026/012',
            'is_active' => true,
        ]);

        // Kartu Pengurus Digital
        $qrTokenPengurus = '019af7c2-8b3a-7d91-a1b2-c3d4e5f67890';
        Card::create([
            'member_id' => $memberPengurus->id,
            'card_number' => 'ISNU-SBY-26-000001',
            'card_type' => 'OFFICER',
            'qr_token' => $qrTokenPengurus,
            'issued_at' => now()->subDays(30),
            'is_active' => true,
        ]);

        // History Status
        MembershipStatusHistory::create([
            'member_id' => $memberPengurus->id,
            'status_from' => 'menunggu_verifikasi',
            'status_to' => 'terverifikasi',
            'changed_by' => $adminKota->id,
            'notes' => 'Pendaftaran diverifikasi lengkap',
            'created_at' => now()->subDays(30),
        ]);
        MembershipStatusHistory::create([
            'member_id' => $memberPengurus->id,
            'status_from' => 'terverifikasi',
            'status_to' => 'pengurus',
            'changed_by' => $adminKota->id,
            'notes' => 'Ditetapkan sebagai Ketua Umum PC ISNU Kota Surabaya',
            'created_at' => now()->subDays(25),
        ]);

        // 4. User & Member Sample 2: ANGGOTA TERVERIFIKASI (Ahmad Husein, S.Kom., M.T.)
        $userAnggota = User::create([
            'name' => 'Ahmad Husein, S.Kom., M.T.',
            'email' => 'ahmad.husein@example.com',
            'phone' => '081345678901',
            'role' => 'member',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $mwcGubeng = $mwcModels['MWC-02'];
        $pacGubeng = $mwcGubeng->pacs->first();

        $memberAnggota = Member::create([
            'user_id' => $userAnggota->id,
            'member_number' => 'ISNU-SBY-26-000002',
            'nik' => '3578021508880002',
            'full_name' => 'Ahmad Husein, S.Kom., M.T.',
            'birth_place' => 'Surabaya',
            'birth_date' => '1988-08-15',
            'gender' => 'L',
            'address' => 'Jl. Gubeng Kertajaya V No. 12',
            'kelurahan' => 'Kertajaya',
            'kecamatan' => 'Gubeng',
            'city' => 'Kota Surabaya',
            'phone' => '081345678901',
            'email' => 'ahmad.husein@example.com',
            'occupation' => 'Software Engineer / Konsultan IT',
            'photo' => null,
            'membership_status' => 'terverifikasi',
            'mwc_id' => $mwcGubeng->id,
            'pac_id' => $pacGubeng->id,
            'verified_at' => now()->subDays(15),
            'verified_by' => $adminKota->id,
        ]);

        MemberEducation::create([
            'member_id' => $memberAnggota->id,
            'level' => 'S2',
            'institution_name' => 'Institut Teknologi Sepuluh Nopember (ITS)',
            'major' => 'Teknik Informatika',
            'start_year' => 2012,
            'end_year' => 2014,
            'degree' => 'M.T.',
        ]);

        MemberEmployment::create([
            'member_id' => $memberAnggota->id,
            'company_name' => 'PT Teknologi Nusantara',
            'position' => 'Senior Lead Software Architect',
            'field' => 'IT',
            'start_year' => 2016,
            'employment_status' => 'Swasta',
            'description' => 'Pengembangan Sistem Informasi Enterprise',
        ]);

        MemberNuTraining::create([
            'member_id' => $memberAnggota->id,
            'training_type' => 'PKD',
            'organizer' => 'PC Ansor Surabaya',
            'year' => 2018,
            'location' => 'Surabaya',
        ]);

        MemberCertification::create([
            'member_id' => $memberAnggota->id,
            'certification_name' => 'AWS Certified Solutions Architect',
            'issuing_organization' => 'Amazon Web Services',
            'issue_year' => 2023,
            'field' => 'IT',
        ]);

        $qrTokenAnggota = '8F72A91X-4b3a-7d91-a1b2-c3d4e5f67891';
        Card::create([
            'member_id' => $memberAnggota->id,
            'card_number' => 'ISNU-SBY-26-000002',
            'card_type' => 'MEMBER',
            'qr_token' => $qrTokenAnggota,
            'issued_at' => now()->subDays(15),
            'is_active' => true,
        ]);

        // 5. User & Member Sample 3: MENUNGGU VERIFIKASI (Dr. Fatimah Az-Zahra, Sp.PD)
        $userPending = User::create([
            'name' => 'Dr. Fatimah Az-Zahra, Sp.PD',
            'email' => 'fatimah.azzahra@example.com',
            'phone' => '081567890123',
            'role' => 'member',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $mwcSukolilo = $mwcModels['MWC-04'];

        $memberPending = Member::create([
            'user_id' => $userPending->id,
            'member_number' => null,
            'nik' => '3578032001920003',
            'full_name' => 'Dr. Fatimah Az-Zahra, Sp.PD',
            'birth_place' => 'Surabaya',
            'birth_date' => '1992-01-20',
            'gender' => 'P',
            'address' => 'Jl. Keputih Timur No. 88',
            'kelurahan' => 'Keputih',
            'kecamatan' => 'Sukolilo',
            'city' => 'Kota Surabaya',
            'phone' => '081567890123',
            'email' => 'fatimah.azzahra@example.com',
            'occupation' => 'Dokter Spesialis Penyakit Dalam',
            'photo' => null,
            'membership_status' => 'menunggu_verifikasi',
            'mwc_id' => $mwcSukolilo->id,
        ]);

        MemberEducation::create([
            'member_id' => $memberPending->id,
            'level' => 'S2',
            'institution_name' => 'Universitas Airlangga',
            'major' => 'Spesialis Penyakit Dalam',
            'start_year' => 2017,
            'end_year' => 2021,
            'degree' => 'Sp.PD',
        ]);

        MemberEmployment::create([
            'member_id' => $memberPending->id,
            'company_name' => 'RSUD Dr. Soetomo Surabaya',
            'position' => 'Dokter Spesialis',
            'field' => 'Kesehatan',
            'start_year' => 2021,
            'employment_status' => 'Tenaga Kesehatan',
        ]);

        MemberCertification::create([
            'member_id' => $memberPending->id,
            'certification_name' => 'SIP (Surat Izin Praktik Dokter Spesialis)',
            'issuing_organization' => 'IDI Surabaya',
            'issue_year' => 2021,
            'field' => 'Kesehatan',
        ]);

        // Audit Log Sample
        AuditLog::record($adminKota->id, 'Verifikasi Anggota', 'Memverifikasi data anggota ISNU-SBY-26-000001 (Dr. H. Ahmad Bashri, M.Pd.)');
        AuditLog::record($adminKota->id, 'Penetapan Pengurus', 'Mengubah status anggota ISNU-SBY-26-000001 menjadi PENGURUS (Ketua Umum)');

        // Sample Member 4: Jurnalis (Siti Rahma, S.I.Kom)
        $userJurnalis = User::create([
            'name' => 'Siti Rahma, S.I.Kom.',
            'email' => 'siti.rahma@example.com',
            'phone' => '081789012345',
            'role' => 'member',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $mwcRungkut = $mwcModels['MWC-01'] ?? $mwcWonokromo;
        $pacRungkut = $mwcRungkut->pacs->first();

        $memberJurnalis = Member::create([
            'user_id' => $userJurnalis->id,
            'member_number' => 'ISNU-SBY-26-000004',
            'nik' => '3578035204920004',
            'full_name' => 'Siti Rahma, S.I.Kom.',
            'birth_place' => 'Surabaya',
            'birth_date' => '1992-04-12',
            'gender' => 'P',
            'address' => 'Jl. Medokan Ayu No. 88',
            'kelurahan' => 'Medokan Ayu',
            'kecamatan' => 'Rungkut',
            'city' => 'Kota Surabaya',
            'phone' => '081789012345',
            'email' => 'siti.rahma@example.com',
            'occupation' => 'Jurnalis',
            'photo' => null,
            'membership_status' => 'terverifikasi',
            'mwc_id' => $mwcRungkut->id,
            'pac_id' => $pacRungkut->id,
            'verified_at' => now()->subDays(5),
            'verified_by' => $adminKota->id,
        ]);

        MemberEducation::create([
            'member_id' => $memberJurnalis->id,
            'level' => 'S1',
            'institution_name' => 'Universitas Airlangga',
            'major' => 'Ilmu Komunikasi',
            'start_year' => 2010,
            'end_year' => 2014,
            'degree' => 'S.I.Kom.',
        ]);

        MemberEmployment::create([
            'member_id' => $memberJurnalis->id,
            'company_name' => 'Media Cetak & Online Surabaya Post',
            'position' => 'Jurnalis Senior / Editor',
            'field' => 'Jurnalistik / Media',
            'start_year' => 2015,
            'employment_status' => 'Tetap',
        ]);
    }
}
