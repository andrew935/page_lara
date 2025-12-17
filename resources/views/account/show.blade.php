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
                </div>
            </div>
        </div>
    </div>
@endsection


