<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Billing\Payment;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    /**
     * Display payments list (Admin only for all accounts, Users see their own)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['account', 'subscription', 'plan'])
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('paid_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('paid_at', '<=', $request->end_date);
        }

        // Filter by account (for non-admin users)
        if (!auth()->user()->hasRole('Admin')) {
            $account = AccountResolver::current();
            $query->where('account_id', $account->id);
        } elseif ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $payments = $query->paginate(50);

        // Calculate statistics
        $stats = $this->calculateStats($request);

        return view('payments.index', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['status', 'start_date', 'end_date', 'account_id']),
        ]);
    }

    /**
     * Calculate payment statistics
     */
    protected function calculateStats(Request $request)
    {
        $query = Payment::query();

        // Apply same filters as main query
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->where('paid_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('paid_at', '<=', $request->end_date);
        }
        if (!auth()->user()->hasRole('Admin')) {
            $account = AccountResolver::current();
            $query->where('account_id', $account->id);
        } elseif ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $stats = [
            'total_revenue' => $query->clone()->where('status', 'succeeded')->sum('amount_cents') / 100,
            'total_payments' => $query->clone()->where('status', 'succeeded')->count(),
            'failed_payments' => $query->clone()->where('status', 'failed')->count(),
            'this_month_revenue' => $query->clone()
                ->where('status', 'succeeded')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount_cents') / 100,
        ];

        // Monthly revenue breakdown (last 12 months)
        $monthlyRevenue = Payment::select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('SUM(amount_cents) / 100 as revenue')
            )
            ->where('status', 'succeeded')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stats['monthly_revenue'] = $monthlyRevenue;

        return $stats;
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        // Authorize access
        if (!auth()->user()->hasRole('Admin')) {
            $account = AccountResolver::current();
            if ($payment->account_id !== $account->id) {
                abort(403, 'Unauthorized access to payment');
            }
        }

        $payment->load(['account', 'subscription', 'plan']);

        return view('payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Export payments to CSV
     */
    public function export(Request $request)
    {
        $query = Payment::with(['account', 'plan'])
            ->orderBy('paid_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->where('paid_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('paid_at', '<=', $request->end_date);
        }
        if (!auth()->user()->hasRole('Admin')) {
            $account = AccountResolver::current();
            $query->where('account_id', $account->id);
        }

        $payments = $query->get();

        $filename = 'payments_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID',
                'Date',
                'Account',
                'Plan',
                'Amount',
                'Currency',
                'Status',
                'Type',
                'Invoice ID',
                'Description',
            ]);

            // Data
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : $payment->created_at->format('Y-m-d H:i:s'),
                    $payment->account->name ?? 'N/A',
                    $payment->plan->name ?? 'N/A',
                    number_format($payment->amount_cents / 100, 2),
                    $payment->currency,
                    $payment->status,
                    $payment->type,
                    $payment->stripe_invoice_id ?? '',
                    $payment->description ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
