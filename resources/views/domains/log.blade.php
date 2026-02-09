@extends('layouts.master')

@section('content')
    <style>
        .uptime-bar { height: 10px; border-radius: 5px; overflow: hidden; }
        .uptime-seg { width: 5px; min-width: 5px; }
        .uptime-bar .up { background-color: #32d484; }
        .uptime-bar .down { background-color: #ff6757; }
        .uptime-bar .idle { background-color: #6c7e96; }
    </style>

    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <a href="{{ route('domains.index') }}" class="text-muted text-decoration-none me-2"><i class="ri-arrow-left-line"></i></a>
            <h1 class="page-title fw-medium fs-20 mb-0 d-inline">Check Log: {{ $domain->domain }}</h1>
        </div>
    </div>

    {{-- Current status --}}
    <div class="card custom-card mb-3">
        <div class="card-header">
            <h6 class="card-title mb-0">Current status</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <span class="text-muted small">Status</span>
                    <div class="mt-1">
                        @if($domain->status === 'ok')
                            <span class="badge bg-success">Up</span>
                        @elseif($domain->status === 'down')
                            <span class="badge bg-danger">Down</span>
                        @elseif($domain->status === 'error')
                            <span class="badge bg-warning">Error</span>
                        @else
                            <span class="badge bg-secondary">{{ $domain->status ?? 'Pending' }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <span class="text-muted small">SSL</span>
                    <div class="mt-1">
                        @if($domain->ssl_valid === true)
                            <span class="badge bg-success">Valid</span>
                        @elseif($domain->ssl_valid === false)
                            <span class="badge bg-danger">Invalid</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <span class="text-muted small">Last checked</span>
                    <div class="mt-1">{{ $domain->last_checked_at?->format('M j, H:i') ?? '—' }}</div>
                </div>
                <div class="col-md-2">
                    <span class="text-muted small">Status since</span>
                    <div class="mt-1">{{ $domain->status_since?->diffForHumans() ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small">Last error</span>
                    <div class="mt-1 text-break">{{ $domain->last_check_error ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Last 24 checks (uptime bar) --}}
    <div class="card custom-card mb-3">
        <div class="card-header">
            <h6 class="card-title mb-0">Last 24 checks</h6>
        </div>
        <div class="card-body">
            @php
                $history = $domain->lastcheck ?? [];
                $segments = array_slice(is_array($history) ? $history : [], -24);
                $segments = array_map(function ($v) {
                    if ($v === 1 || $v === 'up') return 'up';
                    if ($v === 0 || $v === 2 || $v === 'down') return 'down';
                    return 'idle';
                }, $segments);
            @endphp
            @if(!empty($segments))
                <div class="uptime-bar d-flex gap-1">
                    @foreach($segments as $seg)
                        @php $cls = match ($seg) { 'up' => 'up', 'down' => 'down', default => 'idle' }; @endphp
                        <span class="uptime-seg {{ $cls }}"></span>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">No check history yet.</p>
            @endif
        </div>
    </div>

    {{-- Incidents (last 30 days) --}}
    <div class="card custom-card">
        <div class="card-header">
            <h6 class="card-title mb-0">Incidents (last 30 days)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Opened</th>
                            <th>Closed</th>
                            <th>Duration</th>
                            <th>Status change</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $incident)
                            <tr>
                                <td>{{ $incident->opened_at?->format('M j, Y H:i') ?? '—' }}</td>
                                <td>{{ $incident->closed_at ? $incident->closed_at->format('M j, Y H:i') : 'Ongoing' }}</td>
                                <td>
                                    @if($incident->closed_at)
                                        {{ $incident->opened_at->diffForHumans($incident->closed_at, true) }}
                                    @else
                                        Ongoing ({{ $incident->opened_at->diffForHumans() }})
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $incident->status_before ?? '—' }}</span>
                                    <i class="ri-arrow-right-line small text-muted"></i>
                                    <span class="badge bg-secondary">{{ $incident->status_after ?? '—' }}</span>
                                </td>
                                <td class="text-break">{{ $incident->message ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No incidents in the last 30 days.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($incidents->hasPages())
                <div class="card-footer">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
