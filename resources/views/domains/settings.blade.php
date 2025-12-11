@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1">Domain Settings</h5>
            <p class="text-muted mb-0">Check frequency, notifications, maintenance.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card custom-card mb-3">
        <div class="card-header">
            <div class="card-title mb-0">Checks & Notifications</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('domains.settings.update') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Check interval (minutes)</label>
                        <input type="number" name="check_interval_minutes" class="form-control" min="1" max="1440"
                               value="{{ old('check_interval_minutes', $settings->check_interval_minutes) }}" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" value="1" id="notify_on_fail" name="notify_on_fail"
                                   {{ old('notify_on_fail', $settings->notify_on_fail) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_on_fail">
                                Send notification on failure
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notification payload (JSON)</label>
                        <textarea name="notify_payload" class="form-control" rows="4"
                                  placeholder='{"webhook":"https://...","message":"domain failed"}'>{{ old('notify_payload', $settings->notify_payload) }}</textarea>
                        <small class="text-muted">Optional; define what to send to your webhook or integration.</small>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-save-line me-1"></i> Save settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Maintenance</div>
            <a href="{{ route('domains.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ri-edit-line me-1"></i> Edit domains
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('domains.deleteAll') }}" onsubmit="return confirm('Delete ALL domains?');">
                @csrf
                <button class="btn btn-danger" type="submit">
                    <i class="ri-delete-bin-line me-1"></i> Delete all domains
                </button>
            </form>
            <hr>
            <form method="POST" action="{{ route('domains.importJson') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Add domains via JSON (array of domain strings)</label>
                    <textarea name="json" class="form-control" rows="4" placeholder='["example.com","another.com"]' required></textarea>
                </div>
                <div class="text-end">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="ri-download-2-line me-1"></i> Import JSON
                    </button>
                </div>
            </form>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Import from latest feed</div>
                    <small class="text-muted">Uses {{ $settings->feed_url ?? config('domain.source_url') }} (domain + campaign)</small>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editFeedModal">
                        <i class="ri-edit-line me-1"></i> Edit URL
                    </button>
                    <form method="POST" action="{{ route('domains.importLatest') }}">
                        @csrf
                        <button class="btn btn-outline-success btn-sm" type="submit">
                            <i class="ri-cloud-download-line me-1"></i> Import latest
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Feed URL Modal -->
<div class="modal fade" id="editFeedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('domains.settings.update') }}">
                @csrf
                {{-- Preserve required settings so validation passes when only feed_url is edited --}}
                <input type="hidden" name="check_interval_minutes" value="{{ old('check_interval_minutes', $settings->check_interval_minutes) }}">
                <input type="hidden" name="notify_payload" value="{{ old('notify_payload', $settings->notify_payload) }}">
                <input type="hidden" name="notify_on_fail" value="{{ old('notify_on_fail', $settings->notify_on_fail) }}">
                <div class="modal-header">
                    <h6 class="modal-title">Edit feed URL</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Feed URL</label>
                    <input type="text" name="feed_url" class="form-control" value="{{ old('feed_url', $settings->feed_url ?? config('domain.source_url')) }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

