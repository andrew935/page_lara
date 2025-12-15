@extends('layouts.master')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <div class="card-title mb-0">Telegram Logs</div>
                        <small class="text-muted">Every Telegram notification attempt (sent/failed) is recorded here.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('connections.telegram.edit') }}" class="btn btn-light btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a class="btn btn-outline-primary btn-sm {{ empty($status) ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs') }}">All</a>
                        <a class="btn btn-outline-success btn-sm {{ $status === 'sent' ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', ['status' => 'sent']) }}">Sent</a>
                        <a class="btn btn-outline-danger btn-sm {{ $status === 'failed' ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', ['status' => 'failed']) }}">Failed</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 160px;">Time</th>
                                <th style="width: 90px;">Status</th>
                                <th>Message</th>
                                <th style="min-width: 260px;">Error / Meta</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($logs as $log)
                                @php
                                    $meta = is_array($log->meta) ? $log->meta : [];
                                    $err = $meta['error'] ?? ($meta['reason'] ?? null);
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ optional($log->created_at)->toDateTimeString() }}</td>
                                    <td>
                                        @if($log->status === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-success">Sent</span>
                                        @endif
                                    </td>
                                    <td style="white-space: pre-wrap;">{{ $log->message }}</td>
                                    <td style="white-space: pre-wrap;">
                                        {{ $err ? $err : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No telegram logs yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $logs->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


