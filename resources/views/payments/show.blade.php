@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Payment Details</h1>
            <div class="text-muted fs-12">View payment transaction details</div>
        </div>
        <div>
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back to Payments
            </a>
        </div>
    </div>
    <!-- End::page-header -->

    <div class="row">
        <!-- Payment Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Payment Information</div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small">Payment ID</label>
                                <div class="fw-semibold">#{{ $payment->id }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Amount</label>
                                <div class="fw-semibold fs-18">${{ number_format($payment->amount_cents / 100, 2) }} {{ $payment->currency }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Status</label>
                                <div>
                                    @if($payment->status === 'succeeded')
                                        <span class="badge bg-success">Succeeded</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @elseif($payment->status === 'refunded')
                                        <span class="badge bg-warning">Refunded</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Type</label>
                                <div>
                                    <span class="badge bg-primary-transparent">{{ ucfirst($payment->type) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small">Date</label>
                                <div class="fw-semibold">
                                    {{ $payment->paid_at ? $payment->paid_at->format('F d, Y \a\t H:i:s') : $payment->created_at->format('F d, Y \a\t H:i:s') }}
                                </div>
                            </div>
                            @if($payment->period_start && $payment->period_end)
                            <div class="mb-3">
                                <label class="text-muted small">Billing Period</label>
                                <div>{{ $payment->period_start->format('M d, Y') }} - {{ $payment->period_end->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($payment->failed_at)
                            <div class="mb-3">
                                <label class="text-muted small">Failed At</label>
                                <div class="text-danger">{{ $payment->failed_at->format('F d, Y \a\t H:i:s') }}</div>
                            </div>
                            @endif
                            @if($payment->failure_reason)
                            <div class="mb-3">
                                <label class="text-muted small">Failure Reason</label>
                                <div class="text-danger">{{ $payment->failure_reason }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($payment->description)
                    <div class="mb-3">
                        <label class="text-muted small">Description</label>
                        <div>{{ $payment->description }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Stripe Information -->
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <div class="card-title">Stripe Information</div>
                </div>
                <div class="card-body">
                    @if($payment->stripe_invoice_id)
                    <div class="mb-3">
                        <label class="text-muted small">Invoice ID</label>
                        <div class="d-flex align-items-center">
                            <code class="me-2">{{ $payment->stripe_invoice_id }}</code>
                            @if($payment->metadata && isset($payment->metadata['hosted_invoice_url']))
                            <a href="{{ $payment->metadata['hosted_invoice_url'] }}" target="_blank" class="btn btn-sm btn-primary">
                                View in Stripe <i class="ri-external-link-line ms-1"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($payment->stripe_payment_intent_id)
                    <div class="mb-3">
                        <label class="text-muted small">Payment Intent ID</label>
                        <div><code>{{ $payment->stripe_payment_intent_id }}</code></div>
                    </div>
                    @endif

                    @if($payment->stripe_charge_id)
                    <div class="mb-3">
                        <label class="text-muted small">Charge ID</label>
                        <div><code>{{ $payment->stripe_charge_id }}</code></div>
                    </div>
                    @endif

                    @if($payment->metadata && isset($payment->metadata['invoice_number']))
                    <div class="mb-3">
                        <label class="text-muted small">Invoice Number</label>
                        <div>{{ $payment->metadata['invoice_number'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Account & Subscription Info -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Account Details</div>
                </div>
                <div class="card-body">
                    @if($payment->account)
                    <div class="mb-3">
                        <label class="text-muted small">Account Name</label>
                        <div class="fw-semibold">{{ $payment->account->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Account ID</label>
                        <div>{{ $payment->account_id }}</div>
                    </div>
                    @endif

                    @if($payment->plan)
                    <div class="mb-3">
                        <label class="text-muted small">Plan</label>
                        <div>
                            <span class="badge bg-info">{{ $payment->plan->name }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Plan Details</label>
                        <div class="small">
                            <div>Max Domains: {{ $payment->plan->max_domains }}</div>
                            <div>Check Interval: {{ $payment->plan->check_interval_minutes }} minutes</div>
                            <div>Price: ${{ number_format($payment->plan->price_cents / 100, 2) }}/month</div>
                        </div>
                    </div>
                    @endif

                    @if($payment->subscription)
                    <div class="mb-3">
                        <label class="text-muted small">Subscription</label>
                        <div>
                            <span class="badge bg-{{ $payment->subscription->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($payment->subscription->status) }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            @if($payment->metadata && count($payment->metadata) > 0)
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <div class="card-title">Additional Metadata</div>
                </div>
                <div class="card-body">
                    <div class="small">
                        @foreach($payment->metadata as $key => $value)
                        <div class="mb-2">
                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                            @if(is_string($value))
                                {{ $value }}
                            @else
                                {{ json_encode($value) }}
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
