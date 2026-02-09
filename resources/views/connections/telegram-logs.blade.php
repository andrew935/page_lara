@extends('layouts.master')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <div class="card-title mb-0">Notification Logs</div>
                        <small class="text-muted">All notification attempts (sent/failed) across channels are recorded here.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('notifications.edit') }}" class="btn btn-light btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                        <span class="text-muted small me-1">Status:</span>
                        @php $statusQuery = request()->only('channel'); @endphp
                        <a class="btn btn-outline-primary btn-sm {{ empty($status) ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', $statusQuery) }}">All</a>
                        <a class="btn btn-outline-success btn-sm {{ $status === 'sent' ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', array_merge($statusQuery, ['status' => 'sent'])) }}">Sent</a>
                        <a class="btn btn-outline-danger btn-sm {{ $status === 'failed' ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', array_merge($statusQuery, ['status' => 'failed'])) }}">Failed</a>
                        <span class="text-muted small ms-2 me-1">Channel:</span>
                        @php $channelQuery = request()->only('status'); @endphp
                        <a class="btn btn-outline-secondary btn-sm {{ empty($channel) ? 'active' : '' }}"
                           href="{{ route('connections.telegram.logs', $channelQuery) }}">All</a>
                        @foreach(['telegram', 'email', 'slack', 'discord', 'teams'] as $ch)
                            <a class="btn btn-outline-secondary btn-sm {{ $channel === $ch ? 'active' : '' }}"
                               href="{{ route('connections.telegram.logs', array_merge($channelQuery, ['channel' => $ch])) }}">{{ ucfirst($ch) }}</a>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 160px;">Time</th>
                                <th style="width: 100px;">Channel</th>
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
                                    <td><span class="badge bg-secondary">{{ $log->channel ?? '—' }}</span></td>
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
                                    <td colspan="5" class="text-center text-muted py-4">No notification logs yet.</td>
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


