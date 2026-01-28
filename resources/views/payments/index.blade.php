@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Payment History</h1>
            <div class="text-muted fs-12">Track all payments and revenue</div>
        </div>
        <div>
            <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-primary">
                <i class="ri-download-line me-1"></i> Export CSV
            </a>
        </div>
    </div>
    <!-- End::page-header -->

    <!-- Statistics Cards -->
    <div class="row mb-3">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Total Revenue</div>
                            <h4 class="fw-semibold mb-0">${{ number_format($stats['total_revenue'], 2) }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-primary-transparent">
                            <i class="ri-money-dollar-circle-line fs-24 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">This Month</div>
                            <h4 class="fw-semibold mb-0">${{ number_format($stats['this_month_revenue'], 2) }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-success-transparent">
                            <i class="ri-calendar-line fs-24 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Successful Payments</div>
                            <h4 class="fw-semibold mb-0">{{ number_format($stats['total_payments']) }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-success-transparent">
                            <i class="ri-check-line fs-24 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Failed Payments</div>
                            <h4 class="fw-semibold mb-0">{{ number_format($stats['failed_payments']) }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-danger-transparent">
                            <i class="ri-close-line fs-24 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card custom-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="succeeded" {{ ($filters['status'] ?? '') === 'succeeded' ? 'selected' : '' }}>Succeeded</option>
                        <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="refunded" {{ ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card custom-card">
        <div class="card-header">
            <div class="card-title">Payment Transactions</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>#{{ $payment->id }}</td>
                            <td>
                                {{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : $payment->created_at->format('M d, Y H:i') }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $payment->account->name ?? 'N/A' }}</div>
                                @if($payment->account)
                                <div class="text-muted small">ID: {{ $payment->account_id }}</div>
                                @endif
                            </td>
                            <td>
                                @if($payment->plan)
                                    <span class="badge bg-info">{{ $payment->plan->name }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">${{ number_format($payment->amount_cents / 100, 2) }}</div>
                                <div class="text-muted small">{{ $payment->currency }}</div>
                            </td>
                            <td>
                                @if($payment->status === 'succeeded')
                                    <span class="badge bg-success">Succeeded</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @elseif($payment->status === 'refunded')
                                    <span class="badge bg-warning">Refunded</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary-transparent">{{ ucfirst($payment->type) }}</span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $payment->description }}">
                                    {{ $payment->description ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-light">
                                    <i class="ri-eye-line"></i>
                                </a>
                                @if($payment->stripe_invoice_id && $payment->metadata && isset($payment->metadata['hosted_invoice_url']))
                                <a href="{{ $payment->metadata['hosted_invoice_url'] }}" target="_blank" class="btn btn-sm btn-light" title="View in Stripe">
                                    <i class="ri-external-link-line"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No payments found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="card-footer">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

    <!-- Monthly Revenue Chart (Optional) -->
    @if($stats['monthly_revenue']->isNotEmpty())
    <div class="card custom-card mt-3">
        <div class="card-header">
            <div class="card-title">Monthly Revenue (Last 12 Months)</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['monthly_revenue'] as $monthly)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($monthly->month)->format('F Y') }}</td>
                            <td class="fw-semibold">${{ number_format($monthly->revenue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection
