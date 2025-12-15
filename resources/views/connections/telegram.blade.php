@extends('layouts.master')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-9">
            <div class="card custom-card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="card-title mb-1">Telegram Connection</div>
                            <p class="text-muted mb-0">Save the bot API key and the chat ID where notifications should be sent.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('connections.telegram.logs') }}" class="btn btn-outline-primary btn-sm">
                                <i class="ri-file-list-3-line me-1"></i> Logs
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="telegramSaveForm" method="POST" action="{{ route('connections.telegram.update') }}">
                        @csrf
                        <div class="row gy-3">
                            <div class="col-12">
                                <label class="form-label" for="name">Connection Name (optional)</label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{ old('name', optional($connection)->name) }}"
                                       placeholder="e.g., Main Bot">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="chat_id">Chat ID</label>
                                <input type="text"
                                       class="form-control @error('chat_id') is-invalid @enderror"
                                       id="chat_id" name="chat_id"
                                       value="{{ old('chat_id', optional($connection)->chat_id) }}"
                                       placeholder="e.g., 123456789 or @channelusername" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="api_key">Bot API Key</label>
                                <input type="text"
                                       class="form-control @error('api_key') is-invalid @enderror"
                                       id="api_key" name="api_key"
                                       value="{{ old('api_key', optional($connection)->api_key) }}"
                                       placeholder="e.g., 123456789:ABCDEF..." required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="notify_on_fail" name="notify_on_fail"
                                           {{ old('notify_on_fail', optional($settings)->notify_on_fail) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_on_fail">
                                        Enable Telegram notifications on failed checks
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <form method="POST" action="{{ route('connections.telegram.test') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="ri-send-plane-line me-1"></i> Send test
                            </button>
                        </form>
                        <button type="submit" form="telegramSaveForm" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Save Connection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

