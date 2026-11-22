<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\FinancialTransaction;
use App\Models\Letter;
use App\Models\Member;
use App\Models\MemberCertification;
use App\Models\MemberEducation;
use App\Models\MemberNuTraining;
use App\Models\Mwc;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = Cache::remember('admin_dashboard_stats', 120, function () {
            return [
                'total' => Member::count(),
                'terverifikasi' => Member::where('membership_status', 'terverifikasi')->count(),
                'pengurus' => Member::where('membership_status', 'pengurus')->count(),
                'pending' => Member::where('membership_status', 'menunggu_verifikasi')->count(),
                'perbaikan' => Member::where('membership_status', 'perbaikan')->count(),
                'ditolak' => Member::where('membership_status', 'ditolak')->count(),
            ];
        });

        // Breakdown by MWC
        $mwcStats = Mwc::withCount(['members' => function ($q) {
            $q->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        }])->get();

        // Breakdown by Occupation / Pekerjaan
        $occupationStats = Member::select('occupation', DB::raw('count(*) as total'))
            ->whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->groupBy('occupation')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        // Breakdown by Education / Pendidikan (hanya member aktif, data sampah/soft-deleted tidak direkap)
        $educationStats = MemberEducation::whereHas('member')
            ->select('level', DB::raw('count(distinct member_id) as total'))
            ->groupBy('level')
            ->orderByDesc('total')
            ->get();

        // Breakdown by NU Training / Kaderisasi NU (hanya member aktif)
        $nuTrainingStats = MemberNuTraining::whereHas('member')
            ->select('training_type', DB::raw('count(distinct member_id) as total'))
            ->groupBy('training_type')
            ->orderByDesc('total')
            ->get();

        // Breakdown by Skills Field / Sertifikasi (hanya member aktif)
        $certificationStats = MemberCertification::whereHas('member')
            ->select('field', DB::raw('count(distinct member_id) as total'))
            ->whereNotNull('field')
            ->groupBy('field')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        // Recent Registration Queue
        $pendingMembers = Member::where('membership_status', 'menunggu_verifikasi')
            ->latest()
            ->take(5)
            ->get();

        // Siapkan sumbu 6 bulan terakhir (database-agnostic untuk SQLite & MySQL)
        $months = [];
        $monthKeys = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $key = $m->format('Y-m');
            $monthKeys[] = $key;
            $months[$key] = $m->translatedFormat('M Y');
        }
        $sixMonthsStart = now()->startOfMonth()->subMonths(5)->startOfDay();
        $monthLabels = array_values($months);

        // ============================================================
        // 1. REPORTING KEGIATAN & PRESENSI
        // ============================================================
        $eventTotal = Event::count();
        $eventUpcoming = Event::whereDate('event_date', '>=', now()->toDateString())->count();
        $eventCompleted = max(0, $eventTotal - $eventUpcoming);
        $eventTotalPresences = EventPresence::count();
        $eventTotalBudget = (float) Event::sum('budget');
        $eventLuringCount = Event::where('method', 'luring')->count();
        $eventDaringCount = Event::where('method', 'daring')->count();
        $eventHybridCount = Event::where('method', 'hybrid')->count();

        $eventsPerMonth = array_fill_keys($monthKeys, 0);
        $presencesPerMonth = array_fill_keys($monthKeys, 0);

        $recentSixMonthEvents = Event::select(['id', 'event_date'])
            ->whereDate('event_date', '>=', $sixMonthsStart->toDateString())
            ->withCount('presences')
            ->get();

        foreach ($recentSixMonthEvents as $ev) {
            if ($ev->event_date) {
                $k = Carbon::parse($ev->event_date)->format('Y-m');
                if (isset($eventsPerMonth[$k])) {
                    $eventsPerMonth[$k]++;
                    $presencesPerMonth[$k] += (int) $ev->presences_count;
                }
            }
        }

        $recentEvents = Event::with('section')
            ->withCount('presences')
            ->orderByDesc('event_date')
            ->take(5)
            ->get();

        $eventReport = [
            'total_events' => $eventTotal,
            'upcoming_events' => $eventUpcoming,
            'completed_events' => $eventCompleted,
            'total_presences' => $eventTotalPresences,
            'total_budget' => $eventTotalBudget,
            'luring' => $eventLuringCount,
            'daring' => $eventDaringCount,
            'hybrid' => $eventHybridCount,
            'recent_events' => $recentEvents,
            'chart' => [
                'labels' => $monthLabels,
                'events' => array_values($eventsPerMonth),
                'presences' => array_values($presencesPerMonth),
            ],
        ];

        // ============================================================
        // 2. REPORTING KEUANGAN & KAS
        // ============================================================
        $financeBase = FinancialTransaction::query();
        if ($user && $user->isAdminPac() && $user->getManagedPacId()) {
            $financeBase->where('pac_id', $user->getManagedPacId());
        }

        $financeTotalIn = (float) (clone $financeBase)->where('type', FinancialTransaction::TYPE_IN)->sum('amount');
        $financeTotalOut = (float) (clone $financeBase)->where('type', FinancialTransaction::TYPE_OUT)->sum('amount');
        $financeBalance = $financeTotalIn - $financeTotalOut;
        $financeTotalCount = (clone $financeBase)->count();

        $financeInPerMonth = array_fill_keys($monthKeys, 0.0);
        $financeOutPerMonth = array_fill_keys($monthKeys, 0.0);

        $recentSixMonthTransactions = (clone $financeBase)
            ->select(['type', 'amount', 'transaction_date'])
            ->whereDate('transaction_date', '>=', $sixMonthsStart->toDateString())
            ->get();

        foreach ($recentSixMonthTransactions as $trx) {
            if ($trx->transaction_date) {
                $k = Carbon::parse($trx->transaction_date)->format('Y-m');
                if (isset($financeInPerMonth[$k])) {
                    if ($trx->type === FinancialTransaction::TYPE_IN) {
                        $financeInPerMonth[$k] += (float) $trx->amount;
                    } else {
                        $financeOutPerMonth[$k] += (float) $trx->amount;
                    }
                }
            }
        }

        $recentTransactions = (clone $financeBase)
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();

        $financeReport = [
            'total_in' => $financeTotalIn,
            'total_out' => $financeTotalOut,
            'balance' => $financeBalance,
            'total_transactions' => $financeTotalCount,
            'recent_transactions' => $recentTransactions,
            'chart' => [
                'labels' => $monthLabels,
                'income' => array_values($financeInPerMonth),
                'expense' => array_values($financeOutPerMonth),
            ],
        ];

        // ============================================================
        // 3. REPORTING PERSURATAN & E-TTD
        // ============================================================
        $letterBase = Letter::query();
        if ($user && $user->isScopedToPacLetters()) {
            $pacId = $user->getLetterPacId();
            if ($pacId) {
                $letterBase->where('level', Letter::LEVEL_PAC)->where('pac_id', $pacId);
            } else {
                $letterBase->whereRaw('1 = 0');
            }
        }

        $letterTotal = (clone $letterBase)->count();
        $letterDraft = (clone $letterBase)->where('status', Letter::STATUS_DRAFT)->count();
        $letterWaitSec = (clone $letterBase)->where('status', Letter::STATUS_MENUNGGU_SEKRETARIS)->count();
        $letterWaitChair = (clone $letterBase)->where('status', Letter::STATUS_MENUNGGU_KETUA)->count();
        $letterDone = (clone $letterBase)->where('status', Letter::STATUS_SELESAI)->count();
        $letterRejected = (clone $letterBase)->where('status', Letter::STATUS_DITOLAK)->count();

        $letterTypeBiasa = (clone $letterBase)->where(function ($q) {
            $q->where('letter_type', Letter::TYPE_SURAT_BIASA)->orWhereNull('letter_type');
        })->count();
        $letterTypeMandat = (clone $letterBase)->where('letter_type', Letter::TYPE_SURAT_MANDAT)->count();
        $letterTypeRekom = (clone $letterBase)->where('letter_type', Letter::TYPE_SURAT_REKOMENDASI)->count();

        $lettersPerMonth = array_fill_keys($monthKeys, 0);
        $recentSixMonthLetters = (clone $letterBase)
            ->select(['date_letter', 'created_at'])
            ->where(function ($q) use ($sixMonthsStart) {
                $q->whereDate('date_letter', '>=', $sixMonthsStart->toDateString())
                    ->orWhere('created_at', '>=', $sixMonthsStart);
            })
            ->get();

        foreach ($recentSixMonthLetters as $ltr) {
            $refDate = $ltr->date_letter ?? $ltr->created_at;
            if ($refDate) {
                $k = Carbon::parse($refDate)->format('Y-m');
                if (isset($lettersPerMonth[$k])) {
                    $lettersPerMonth[$k]++;
                }
            }
        }

        $recentLetters = (clone $letterBase)
            ->with('pac')
            ->latest('date_letter')
            ->latest('id')
            ->take(5)
            ->get();

        $letterReport = [
            'total' => $letterTotal,
            'draft' => $letterDraft,
            'menunggu_sekretaris' => $letterWaitSec,
            'menunggu_ketua' => $letterWaitChair,
            'selesai' => $letterDone,
            'ditolak' => $letterRejected,
            'by_type' => [
                'biasa' => $letterTypeBiasa,
                'mandat' => $letterTypeMandat,
                'rekomendasi' => $letterTypeRekom,
            ],
            'recent_letters' => $recentLetters,
            'chart' => [
                'labels' => $monthLabels,
                'monthly_counts' => array_values($lettersPerMonth),
                'status_labels' => ['Draft', 'Menunggu Sekretaris', 'Menunggu Ketua', 'Selesai', 'Ditolak'],
                'status_counts' => [$letterDraft, $letterWaitSec, $letterWaitChair, $letterDone, $letterRejected],
            ],
        ];

        // ============================================================
        // 4. REPORTING TOKO & MERCHANDISE
        // ============================================================
        $merchTotalProducts = Product::count();
        $merchActiveProducts = Product::where('is_active', true)->count();
        $merchLowStockProducts = Product::where('is_active', true)->where('stock', '<', 5)->count();

        $merchTotalOrders = ProductOrder::count();
        $merchPendingPayment = ProductOrder::where('status', ProductOrder::STATUS_PENDING_PAYMENT)->count();
        $merchPaymentUploaded = ProductOrder::where('status', ProductOrder::STATUS_PAYMENT_UPLOADED)->count();
        $merchPaid = ProductOrder::where('status', ProductOrder::STATUS_PAID)->count();
        $merchShipped = ProductOrder::where('status', ProductOrder::STATUS_SHIPPED)->count();
        $merchReceived = ProductOrder::where('status', ProductOrder::STATUS_RECEIVED)->count();
        $merchRevenue = (float) ProductOrder::whereIn('status', [
            ProductOrder::STATUS_PAID,
            ProductOrder::STATUS_SHIPPED,
            ProductOrder::STATUS_RECEIVED,
        ])->sum('grand_total');

        $merchOrdersPerMonth = array_fill_keys($monthKeys, 0);
        $merchRevenuePerMonth = array_fill_keys($monthKeys, 0.0);

        $recentSixMonthOrders = ProductOrder::select(['status', 'grand_total', 'created_at'])
            ->where('created_at', '>=', $sixMonthsStart)
            ->get();

        foreach ($recentSixMonthOrders as $ord) {
            if ($ord->created_at) {
                $k = Carbon::parse($ord->created_at)->format('Y-m');
                if (isset($merchOrdersPerMonth[$k])) {
                    $merchOrdersPerMonth[$k]++;
                    if (in_array($ord->status, [
                        ProductOrder::STATUS_PAID,
                        ProductOrder::STATUS_SHIPPED,
                        ProductOrder::STATUS_RECEIVED,
                    ], true)) {
                        $merchRevenuePerMonth[$k] += (float) $ord->grand_total;
                    }
                }
            }
        }

        $topProducts = ProductOrderItem::select(
            'product_name',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(subtotal) as total_sales')
        )
            ->whereHas('order', function ($q) {
                $q->where('status', '!=', ProductOrder::STATUS_CANCELLED);
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $merchandiseReport = [
            'total_products' => $merchTotalProducts,
            'active_products' => $merchActiveProducts,
            'low_stock_products' => $merchLowStockProducts,
            'total_orders' => $merchTotalOrders,
            'pending_payment' => $merchPendingPayment,
            'payment_uploaded' => $merchPaymentUploaded,
            'paid' => $merchPaid,
            'shipped' => $merchShipped,
            'received' => $merchReceived,
            'revenue' => $merchRevenue,
            'top_products' => $topProducts,
            'chart' => [
                'labels' => $monthLabels,
                'orders' => array_values($merchOrdersPerMonth),
                'revenue' => array_values($merchRevenuePerMonth),
                'status_labels' => ['Menunggu Bayar', 'Perlu Verifikasi', 'Lunas/Diproses', 'Dikirim', 'Selesai'],
                'status_counts' => [$merchPendingPayment, $merchPaymentUploaded, $merchPaid, $merchShipped, $merchReceived],
            ],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'mwcStats',
            'occupationStats',
            'educationStats',
            'nuTrainingStats',
            'certificationStats',
            'pendingMembers',
            'eventReport',
            'financeReport',
            'letterReport',
            'merchandiseReport'
        ));
    }
}
