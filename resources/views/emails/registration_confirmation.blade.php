<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pendaftaran SIKAP ISNU Kota Surabaya</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #006837;
            color: #ffffff;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .content {
            padding: 30px;
        }
        .badge-status {
            display: inline-block;
            background-color: #e2f0d9;
            color: #006837;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>SIKAP ISNU KOTA SURABAYA</h1>
            <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Sistem Informasi Keanggotaan & Potensi ISNU Kota Surabaya</p>
        </div>
        <div class="content">
            <span class="badge-status">Status: Menunggu Verifikasi Admin</span>
            
            <p>Yth. <strong>{{ $user->name }}</strong>,</p>

            <p>Terima kasih telah mendaftar sebagai anggota dalam Sistem Informasi Keanggotaan & Potensi (SIKAP) Ikatan Sarjana Nahdlatul Ulama (ISNU) Kota Surabaya.</p>

            <p>Pendaftaran Anda telah berhasil kami terima. Saat ini data Anda sedang dalam <strong>proses verifikasi dan persetujuan oleh Tim Admin PC ISNU Kota Surabaya</strong>.</p>

            <p>Setelah pendaftaran Anda diverifikasi dan disetujui oleh Admin, akun Anda akan diaktifkan secara otomatis dan Anda dapat langsung masuk (login) ke dalam sistem SIKAP ISNU serta mengunduh Kartu Anggota Digital.</p>

            <div style="background-color: #f8fafc; border-left: 4px solid #006837; padding: 15px; margin: 20px 0; font-size: 14px; border-radius: 4px;">
                <strong>Ringkasan Pendaftaran:</strong><br>
                • Nama: {{ $user->name }}<br>
                • Email: {{ $user->email }}<br>
                • No. WhatsApp: {{ $user->phone }}
            </div>

            <p style="margin-top: 25px;">Salam takzim,<br>
            <strong>PC ISNU Kota Surabaya</strong></p>
        </div>
        <div class="footer">
            Email ini dikirim secara otomatis oleh Sistem Informasi Keanggotaan ISNU Kota Surabaya.<br>
            Alamat Email Pengirim: admin@joshnu.isnusurabaya.or.id
        </div>
    </div>
</body>
</html>
