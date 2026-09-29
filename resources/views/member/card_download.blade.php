<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Anggota Digital - {{ $member->full_name }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card-print-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .card-print-box {
                background: none !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="card-print-box">
        <h4 class="fw-bold text-white mb-2 no-print">Kartu Digital Resmi ISNU Surabaya</h4>
        <p class="text-white-50 small mb-4 no-print">Kartu ini telah siap dicetak atau disimpan sebagai tanda pengenal digital.</p>

        <div class="mb-4">
            <x-digital_card :member="$member" :card="$card" />
        </div>

        <div class="d-flex justify-content-center gap-3 no-print">
            <button onclick="window.print()" class="btn btn-warning fw-bold rounded-pill px-4">
                <i class="bi bi-printer-fill me-1"></i> Cetak / Simpan PDF
            </button>
            <a href="{{ route('member.dashboard') }}" class="btn btn-outline-light rounded-pill px-4">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>
