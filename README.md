# SIKAP ISNU — Sistem Informasi Keanggotaan dan Potensi ISNU Kota Surabaya

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

**SIKAP ISNU** adalah Sistem Informasi Keanggotaan dan Potensi Kader milik **Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya**. Aplikasi berbasis web ini dibangun untuk melakukan pendataan anggota secara terstruktur, terverifikasi, dan berkelanjutan, serta menyediakan layanan **Kartu Anggota Digital** berbasis **QR Code**.

---

## 1. Latar Belakang & Tujuan

Ikatan Sarjana Nahdlatul Ulama (ISNU) Kota Surabaya membutuhkan basis data terintegrasi yang tidak hanya mencakup identitas dasar anggota, tetapi juga rekam jejak pendidikan, pekerjaan, pengalaman organisasi, kaderisasi NU, dan sertifikasi keahlian. 

### Tujuan Utama Aplikasi:
1. **Pendaftaran Online**: Menyediakan sistem pendaftaran mandiri bagi calon anggota ISNU Kota Surabaya.
2. **Database Terstruktur & Terverifikasi**: Membangun basis data potensi sumber daya manusia sarjana NU di Kota Surabaya.
3. **Verifikasi Admin**: Proses pemeriksaan, persetujuan, penolakan, atau pengembalian perbaikan data pendaftar oleh admin.
4. **Kartu Anggota Digital**: Penerbitan kartu identitas digital dengan desain khusus untuk **Anggota** dan **Pengurus**.
5. **Validasi QR Code**: Pemindaian QR Code kartu yang mengarah ke halaman verifikasi publik untuk memeriksa keaslian kartu secara realtime.
6. **Manajemen Organisasi**: Memungkinkan penetapan pengurus MWC (Kecamatan) dan PAC (Kelurahan/Wilayah), pencatatan jabatan, dan histori periode kepengurusan.

---

## 2. Sasaran Pengguna & Hak Akses (Roles)

| Role Pengguna | Deskripsi & Hak Akses |
|:---|:---|
| **Super Admin** | Akses penuh seluruh sistem, pengelolaan user admin, audit log, dan pengaturan konfigurasi. |
| **Admin Kota (PC)** | Memverifikasi pendaftar baru, mengelola data anggota, menetapkan pengurus, menerbitkan kartu, serta mengeksport laporan. |
| **Admin MWC / PAC** | Mengelola dan memantau data anggota di wilayah MWC (Kecamatan) atau PAC terkait. |
| **Pengurus** | Anggota yang ditetapkan dalam kepengurusan PC/MWC/PAC, memiliki Kartu Pengurus Digital khusus. |
| **Anggota / Calon Anggota** | Mendaftar akun, melengkapi biodata & potensi (pendidikan, pekerjaan, organisasi, kaderisasi, sertifikasi), dan mengunduh Kartu Digital/CV. |

---

## 3. Konsep Keanggotaan & Workflow Verifikasi

Alur status keanggotaan dalam sistem SIKAP ISNU:

```
[ Pendaftaran Online ]
         │
         ▼
[ Calon Anggota ] ──► [ Melengkapi Profil & Data Potensi ]
         │
         ▼
[ Menunggu Verifikasi Admin ]
         │
         ├───► [ Ditolak / Perbaikan Data ] ──► [ Verifikasi Ulang ]
         │
         ▼
[ Terverifikasi / ANGGOTA ] ──► [ Menerbitkan Kartu Digital (MEMBER) ]
         │
         ▼ (Ditetapkan Admin)
[ PENGURUS ] ──► [ Menerbitkan Kartu Pengurus (OFFICER) ]
```

---

## 4. Ruang Lingkup Sistem & Fitur Utama

- **Public Website & Direktori**:
  - Halaman Beranda (Landing Page) & Profil ISNU Surabaya.
  - Direktori Kader / Katalog Anggota Terverifikasi.
  - Halaman Verifikasi Publik Kartu Anggota (via scan QR Code).

- **Member Area**:
  - Dashboard Anggota & Status Verifikasi Akun.
  - Form Edit Profil & Upload Pas Foto Formal.
  - Input Riwayat Pendidikan (SD hingga S3).
  - Input Riwayat Organisasi & Pengalaman Kepengurusan.
  - Input Riwayat Pekerjaan & Kategori Bidang Kerja (ASN, TNI/Polri, BUMN, Swasta, Akademisi, Tenaga Pendidik, Dokter/Kesehatan, Jurnalis, Aktivis NGO, Wirausaha, Freelancer).
  - Input Riwayat Kaderisasi NU (MAKESTA, PKD, PKL, PKN, PKMNU).
  - Input Sertifikasi Keahlian & Bidang Kompetensi.
  - Pratinsjau & Unduh Kartu Anggota Digital (PDF/Image) & Curriculum Vitae (CV PDF).

- **Admin Area**:
  - Dashboard Analytics & Rekapitulasi Statistik (Total Anggota, Status Verifikasi, Sebaran MWC/Kecamatan, Distribusi Profesi, Jenjang Pendidikan, dan Kaderisasi NU).
  - Verifikasi Pendaftar Baru (Setujui, Minta Perbaikan + Catatan, atau Tolak).
  - Manajemen Data Anggota & Penetapan Status Pengurus (Jabatan, SK, dan Periode).
  - Manajemen Data Sampah (Soft Deletes & Pemulihan Data).
  - Pemesanan & Tracking Kartu Fisik (Card Orders).
  - Export Laporan Data Anggota & Potensi (CSV / Excel).
  - Audit Log Trail Aktivitas Sistem.

---

## 5. Kartu Anggota Digital & Keamanan QR Code

Kartu Anggota Digital mengikuti proporsi standar kartu ID-1 (ATM/KTP: **85,60 × 53,98 mm**).

- **Kartu Anggota (MEMBER)**: Desain hijau khas ISNU dengan identitas visual anggota, nomor anggota resmi (`ISNU-SBY-YY-XXXXXX`), foto profil, dan QR Code.
- **Kartu Pengurus (OFFICER)**: Desain khusus pengurus yang memuat Jabatan, Periode Kepengurusan, dan aksen emas visual.
- **Keamanan QR Code**: QR Code tidak menyimpan data pribadi secara mentah (seperti NIK/No HP), melainkan menggunakan **UUID / Random Token** terenkripsi yang berfungsi sebagai *pointer* menuju halaman verifikasi publik.

---

## 6. Arsitektur Teknologi

- **Backend Framework**: [Laravel 11](https://laravel.com) (PHP 8.2+)
- **Frontend Engine**: Blade Templates + Vanilla CSS + [Bootstrap 5.3](https://getbootstrap.com) + [Alpine.js 3.x](https://alpinejs.dev)
- **Database**: [MySQL 8.0+](https://mysql.com) / MariaDB (dengan Eloquent ORM & Soft Deletes)
- **PDF & QR Code Generator**: `barryvdh/laravel-dompdf` & `simplesoftwareio/simple-qrcode`

---

## 7. Struktur Data Utama

```
users
 └── members
      ├── member_educations
      ├── member_organizations
      ├── member_employments
      ├── member_nu_trainings
      ├── member_certifications
      ├── membership_status_histories
      ├── positions
      └── cards ──► card_verifications
```

---

## 8. Panduan Instalasi Lokal (Development Setup)

### Requirement:
- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `gd`, `xml`, `zip`)
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL Database

### Langkah-langkah:

1. **Clone Repository**:
   ```bash
   git clone https://github.com/nurvanindrapraja/sikapisnu.git
   cd sikapisnu
   ```

2. **Install Dependensi PHP & JavaScript**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Lingkungan (`.env`)**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan konfigurasi database MySQL pada file `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u6225576_sikapisnu
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan Migrasi Database & Seeder**:
   ```bash
   php artisan migrate --seed
   ```

5. **Kompilasi Aset Frontend & Symlink Storage**:
   ```bash
   npm run build
   php artisan storage:link
   ```

6. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## 9. Akun Default Pengujian

| Email | Password | Role | Keterangan |
|:---|:---:|:---:|:---|
| `superadmin@isnu-surabaya.or.id` | `password` | Super Admin | Akses Penuh Sistem |
| `admin@isnu-surabaya.or.id` | `password` | Admin Kota | Admin PC ISNU Surabaya |
| `ahmad.bashri@isnu-surabaya.or.id` | `password` | Member | Status: Pengurus (Ketua Umum) |
| `ahmad.husein@example.com` | `password` | Member | Status: Anggota Terverifikasi |

---

## 10. Lisensi & Hak Cipta

© **PC ISNU Kota Surabaya**. Hak Cipta Dilindungi Undang-Undang.  
Dikembangkan untuk penguatan konsolidasi dan optimalisasi potensi intelektual sarjana Nahdlatul Ulama Kota Surabaya.
