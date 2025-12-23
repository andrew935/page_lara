@extends('layouts.master')

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Account</h1>
            <div class="text-muted fs-12">Your account details and plan limits</div>
        </div>
    </div>
    <!-- End::page-header -->

    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">User</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md avatar-rounded bg-primary text-white fw-bold d-flex align-items-center justify-content-center">
                            @php
                                $name = $user->name ?? 'Account';
                                $parts = preg_split('/\s+/', trim($name));
                                $initials = strtoupper(substr($parts[0] ?? 'A', 0, 1) . substr($parts[1] ?? '', 0, 1));
                            @endphp
                            {{ $initials }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $user->name ?? '—' }}</div>
                            <div class="text-muted">{{ $user->email ?? '—' }}</div>
                            @if(method_exists($user, 'getRoleNames'))
                                <div class="mt-1">
                                    <span class="badge bg-secondary">{{ $user->getRoleNames()->first() ?? 'user' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6 col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Plan</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $plan->name ?? 'Free' }}</div>
                            <div class="text-muted fs-12">Default plan: Free (up to 50 domains)</div>
                        </div>
                        <span class="badge bg-primary">Active</span>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Domains usage</span>
                            <span class="fw-semibold">{{ $domainCount }} / {{ $maxDomains }}</span>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            @php
                                $pct = $maxDomains > 0 ? min(100, (int) round(($domainCount / $maxDomains) * 100)) : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted d-block mt-2">If you need more than {{ $maxDomains }} domains, upgrade the plan.</small>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Minimum check interval</span>
                            <span class="fw-semibold">{{ $minInterval }} min</span>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="card-title mb-0">Upgrade plan</div>
                    <span class="text-muted fs-12">Select a plan to upgrade</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach(($allPlans ?? []) as $p)
                            @php
                                $isCurrent = ($plan && $p->id === $plan->id) || (!$plan && $p->slug === 'free');
                                $price = ($p->price_cents ?? 0) > 0 ? '$' . number_format(($p->price_cents / 100), 2) . '/mo' : '$0/mo';
                                $hasSslCheck = in_array($p->slug, ['pro', 'max'], true);
                                $currentPrice = $plan?->price_cents ?? 0;
                                $canUpgradeToThis = !$isCurrent && ($p->price_cents ?? 0) > $currentPrice;
                            @endphp
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <div class="card border h-100 {{ $isCurrent ? 'border-primary' : '' }}">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">{{ $p->name }}</div>
                                                <div class="text-muted fs-12">{{ $price }}</div>
                                            </div>
                                            @if($isCurrent)
                                                <span class="badge bg-primary">Current</span>
                                            @endif
                                        </div>

                                        <ul class="mt-3 mb-0 ps-3">
                                            <li>Up to <strong>{{ $p->max_domains }}</strong> domains</li>
                                            <li>Minimum check interval: <strong>{{ $p->check_interval_minutes }}</strong> minutes</li>
                                            @if($hasSslCheck)
                                                <li><strong>SSL check</strong> (certificate validity)</li>
                                            @else
                                                <li>SSL check: <span class="text-muted">not included</span></li>
                                            @endif
                                            <li>Same dashboard, alerts, and queue-based monitoring</li>
                                        </ul>

                                        <div class="mt-3">
                                            @if($canUpgradeToThis)
                                                <form method="POST" action="{{ route('account.upgrade') }}">
                                                    @csrf
                                                    <input type="hidden" name="plan" value="{{ $p->slug }}">
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                                        Upgrade to {{ $p->name }}
                                                    </button>
                                                </form>
                                            @elseif($isCurrent)
                                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                    Current plan
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                    Not available
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-3">
                        Note: This upgrade action only changes the active plan in the database (no payment integration).
                    </small>
                </div>
            </div>
        </div>
    </div>
@endsection


