<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Reset Password - SIKAP ISNU Kota Surabaya</title>
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
            background-color: #e8f5e9;
            color: #006837;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-action {
            display: inline-block;
            background-color: #006837;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 104, 55, 0.25);
        }
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #006837;
            padding: 15px;
            margin: 22px 0;
            font-size: 13px;
            color: #475569;
            border-radius: 4px;
        }
        .url-box {
            word-break: break-all;
            background-color: #f1f5f9;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 12px;
            color: #0369a1;
            margin-top: 8px;
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
            <span class="badge-status">Pengaturan Ulang Kata Sandi</span>

            <p>Yth. <strong>{{ $user->name }}</strong>,</p>

            <p>Kami menerima permintaan untuk mengatur ulang kata sandi (password) akun SIKAP ISNU yang terdaftar dengan alamat email ini.</p>

            <p>Untuk melanjutkan proses pembuatan password baru, silakan klik tombol di bawah ini:</p>

            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="btn-action" target="_blank">Atur Ulang Password</a>
            </div>

            <div class="info-box">
                <strong>Catatan Keamanan:</strong><br>
                • Tautan ini hanya berlaku selama <strong>60 menit</strong> sejak email ini dikirimkan.<br>
                • Jika Anda tidak pernah meminta pengaturan ulang kata sandi, Anda dapat mengabaikan email ini dengan aman. Akun dan password Anda tidak akan berubah.
            </div>

            <p style="font-size: 13px; color: #64748b; margin-top: 20px;">
                Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut ke peramban (browser) web Anda:
            </p>
            <div class="url-box">
                {{ $resetUrl }}
            </div>

            <p style="margin-top: 28px; margin-bottom: 0;">Salam takzim,<br>
            <strong>PC ISNU Kota Surabaya</strong></p>
        </div>
        <div class="footer">
            Email ini dikirim secara otomatis oleh Sistem Informasi Keanggotaan ISNU Kota Surabaya.<br>
            Alamat Email Pengirim: admin@joshnu.isnusurabaya.or.id
        </div>
    </div>
</body>
</html>
