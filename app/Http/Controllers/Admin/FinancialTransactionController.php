<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinancialTransaction;
use App\Models\Pac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialTransactionController extends Controller
{
    /**
     * Memeriksa otorisasi: hanya Bendahara dan Admin yang berhak mengakses menu keuangan.
     */
    protected function checkAccess(): void
    {
        if (! auth()->check() || ! auth()->user()->canManageFinances()) {
            abort(403, 'Hanya Bendahara dan Admin yang berhak mengakses dan mengelola menu Pengelolaan Keuangan.');
        }
    }

    public function index(Request $request): View|string
    {
        $this->checkAccess();

        // Scope berdasarkan wilayah jika admin PAC
        $baseQuery = FinancialTransaction::query();
        if (auth()->user()->isAdminPac() && auth()->user()->getManagedPacId()) {
            $baseQuery->where('pac_id', auth()->user()->getManagedPacId());
        }

        // Ringkasan Finansial Keseluruhan (Overall Kas)
        $totalIncomeAll = (float) (clone $baseQuery)->where('type', FinancialTransaction::TYPE_IN)->sum('amount');
        $totalExpenseAll = (float) (clone $baseQuery)->where('type', FinancialTransaction::TYPE_OUT)->sum('amount');
        $currentBalance = $totalIncomeAll - $totalExpenseAll;
        $totalTransactionsCount = (clone $baseQuery)->count();

        // Query dengan Filter
        $query = (clone $baseQuery)->with(['user', 'pac']);

        // 1. Filter Jenis Transaksi
        if ($request->filled('type') && in_array($request->type, [FinancialTransaction::TYPE_IN, FinancialTransaction::TYPE_OUT], true)) {
            $query->where('type', $request->type);
        }

        // 2. Filter Kategori
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // 3. Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // 4. Filter Metode Pembayaran
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // 5. Filter Tingkatan / PAC
        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }
        if ($request->filled('pac_id')) {
            $query->where('pac_id', $request->pac_id);
        }

        // 6. Pencarian Teks (Judul, Keterangan, Kode Transaksi)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('transaction_code', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Metrik Filtered
        $filteredIncome = (float) (clone $query)->where('type', FinancialTransaction::TYPE_IN)->sum('amount');
        $filteredExpense = (float) (clone $query)->where('type', FinancialTransaction::TYPE_OUT)->sum('amount');
        $filteredBalance = $filteredIncome - $filteredExpense;

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $pacs = Pac::orderBy('name')->get();

        if ($request->ajax()) {
            return view('admin.finances.partials.transaction_list', compact('transactions'))->render();
        }

        return view('admin.finances.index', compact(
            'transactions',
            'pacs',
            'totalIncomeAll',
            'totalExpenseAll',
            'currentBalance',
            'totalTransactionsCount',
            'filteredIncome',
            'filteredExpense',
            'filteredBalance'
        ));
    }

    public function create(Request $request): View
    {
        $this->checkAccess();

        $defaultType = $request->query('type', FinancialTransaction::TYPE_IN);
        if (! in_array($defaultType, [FinancialTransaction::TYPE_IN, FinancialTransaction::TYPE_OUT], true)) {
            $defaultType = FinancialTransaction::TYPE_IN;
        }

        $pacs = Pac::orderBy('name')->get();

        return view('admin.finances.create', compact('defaultType', 'pacs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'type' => 'required|in:'.FinancialTransaction::TYPE_IN.','.FinancialTransaction::TYPE_OUT,
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'payment_method' => 'required|string|max:50',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('finances/proofs', 'public');
        }

        $code = FinancialTransaction::generateTransactionCode($validated['type']);

        $transaction = FinancialTransaction::create([
            'transaction_code' => $code,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'],
            'proof_path' => $proofPath,
            'level' => $validated['level'],
            'pac_id' => $validated['level'] === 'PAC ISNU' ? ($validated['pac_id'] ?? null) : null,
            'user_id' => auth()->id(),
        ]);

        $typeLabel = $transaction->isIncome() ? 'Uang Masuk' : 'Uang Keluar';
        AuditLog::record(
            auth()->id(),
            'create_financial_transaction',
            "Mencatat transaksi {$typeLabel}: {$transaction->title} sebesar {$transaction->formatted_amount} (Kode: {$transaction->transaction_code})",
            ['transaction_id' => $transaction->id, 'amount' => $transaction->amount, 'type' => $transaction->type]
        );

        return redirect()->route('admin.finances.index')->with('success', "Transaksi {$typeLabel} berhasil dicatat.");
    }

    public function preview(int $id): View
    {
        $this->checkAccess();

        $transaction = FinancialTransaction::with(['user', 'pac'])->findOrFail($id);

        return view('admin.finances.partials.transaction_preview_modal_content', compact('transaction'));
    }

    public function show(int $id): View
    {
        $this->checkAccess();

        $transaction = FinancialTransaction::with(['user', 'pac'])->findOrFail($id);

        return view('admin.finances.show', compact('transaction'));
    }

    public function edit(int $id): View
    {
        $this->checkAccess();

        $transaction = FinancialTransaction::findOrFail($id);
        $pacs = Pac::orderBy('name')->get();

        return view('admin.finances.edit', compact('transaction', 'pacs'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $transaction = FinancialTransaction::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:'.FinancialTransaction::TYPE_IN.','.FinancialTransaction::TYPE_OUT,
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'payment_method' => 'required|string|max:50',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
        ]);

        $proofPath = $transaction->proof_path;
        if ($request->hasFile('proof_file')) {
            if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                Storage::disk('public')->delete($proofPath);
            }
            $proofPath = $request->file('proof_file')->store('finances/proofs', 'public');
        }

        $transaction->update([
            'type' => $validated['type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'],
            'proof_path' => $proofPath,
            'level' => $validated['level'],
            'pac_id' => $validated['level'] === 'PAC ISNU' ? ($validated['pac_id'] ?? null) : null,
        ]);

        AuditLog::record(
            auth()->id(),
            'update_financial_transaction',
            "Memperbarui transaksi keuangan: {$transaction->title} (Kode: {$transaction->transaction_code})",
            ['transaction_id' => $transaction->id]
        );

        return redirect()->route('admin.finances.show', $transaction->id)->with('success', 'Perubahan transaksi keuangan berhasil disimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess();

        $transaction = FinancialTransaction::findOrFail($id);

        if ($transaction->proof_path && Storage::disk('public')->exists($transaction->proof_path)) {
            Storage::disk('public')->delete($transaction->proof_path);
        }

        $code = $transaction->transaction_code;
        $title = $transaction->title;
        $amount = $transaction->formatted_amount;
        $type = $transaction->type_label;

        $transaction->delete();

        AuditLog::record(
            auth()->id(),
            'delete_financial_transaction',
            "Menghapus transaksi {$type}: {$title} sebesar {$amount} (Kode: {$code})"
        );

        return redirect()->route('admin.finances.index')->with('success', 'Data transaksi keuangan berhasil dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->checkAccess();

        $query = FinancialTransaction::with(['user', 'pac']);

        if ($request->filled('type') && in_array($request->type, [FinancialTransaction::TYPE_IN, FinancialTransaction::TYPE_OUT], true)) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->get();

        $filename = 'Laporan_Keuangan_ISNU_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            // Byte Order Mark (BOM) untuk support Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($file, [
                'No',
                'Kode Transaksi',
                'Tanggal',
                'Tipe Transaksi',
                'Kategori',
                'Judul Transaksi',
                'Keterangan',
                'Metode Pembayaran',
                'Uang Masuk (Rp)',
                'Uang Keluar (Rp)',
                'Tingkatan',
                'Petugas Pencatat',
            ]);

            $no = 1;
            $totalIn = 0;
            $totalOut = 0;

            foreach ($transactions as $t) {
                $in = $t->isIncome() ? $t->amount : 0;
                $out = $t->isExpense() ? $t->amount : 0;
                $totalIn += $in;
                $totalOut += $out;

                fputcsv($file, [
                    $no++,
                    $t->transaction_code,
                    $t->transaction_date?->format('d/m/Y') ?? '-',
                    $t->type_label,
                    $t->category,
                    $t->title,
                    $t->description ?? '-',
                    $t->payment_method ?? 'Tunai',
                    $in > 0 ? $in : 0,
                    $out > 0 ? $out : 0,
                    $t->level.($t->pac ? ' ('.$t->pac->name.')' : ''),
                    $t->user?->name ?? 'Sistem',
                ]);
            }

            // Baris Total
            fputcsv($file, []);
            fputcsv($file, [
                '', '', '', '', '', '', '',
                'TOTAL',
                $totalIn,
                $totalOut,
                '', '',
            ]);
            fputcsv($file, [
                '', '', '', '', '', '', '',
                'SALDO KAS BERSIH',
                $totalIn - $totalOut,
                '', '', '',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
