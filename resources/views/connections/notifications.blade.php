@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
                <div>
                    <h1 class="page-title fw-medium fs-20 mb-0">Notifications</h1>
                    <div class="text-muted fs-12">Configure channels for domain down/up alerts</div>
                </div>
                <a href="{{ route('connections.telegram.logs') }}" class="btn btn-outline-primary btn-sm">
                    <i class="ri-file-list-3-line me-1"></i> Notification Logs
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
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

            <form id="notificationsForm" method="POST" action="{{ route('notifications.update') }}">
                @csrf
                <div class="mb-3">
                    <div class="form-check">
                        @php $channels = is_array($settings->channels) ? $settings->channels : []; @endphp
                        <input class="form-check-input" type="checkbox" value="1" id="notify_on_fail" name="notify_on_fail"
                               {{ old('notify_on_fail', $settings->notify_on_fail) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_on_fail">
                            Enable notifications when a domain check fails or recovers
                        </label>
                    </div>
                </div>

                {{-- Telegram --}}
                <div class="card custom-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="ri-send-plane-line me-2"></i>Telegram</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="channel_telegram" value="1"
                                       {{ in_array('telegram', $channels, true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Enable</label>
                            </span>
                            <form method="POST" action="{{ route('notifications.test', 'telegram') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Send test</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Chat ID</label>
                                <input type="text" class="form-control form-control-sm" name="telegram_chat_id"
                                       value="{{ old('telegram_chat_id', $settings->telegram_chat_id) }}"
                                       placeholder="e.g. 123456789 or @channel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Bot API Key</label>
                                <input type="text" class="form-control form-control-sm" name="telegram_api_key"
                                       value="{{ old('telegram_api_key', $settings->telegram_api_key) }}"
                                       placeholder="e.g. 123456789:ABC...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Email --}}
                <div class="card custom-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="ri-mail-line me-2"></i>Email</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="channel_email" value="1"
                                       {{ in_array('email', $channels, true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Enable</label>
                            </span>
                            <form method="POST" action="{{ route('notifications.test', 'email') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Send test</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label small">Email address</label>
                        <input type="email" class="form-control form-control-sm" name="email"
                               value="{{ old('email', $settings->email) }}"
                               placeholder="alerts@example.com">
                    </div>
                </div>

                {{-- Slack --}}
                <div class="card custom-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="ri-slack-line me-2"></i>Slack</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="channel_slack" value="1"
                                       {{ in_array('slack', $channels, true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Enable</label>
                            </span>
                            <form method="POST" action="{{ route('notifications.test', 'slack') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Send test</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label small">Incoming Webhook URL</label>
                        <input type="url" class="form-control form-control-sm" name="slack_webhook_url"
                               value="{{ old('slack_webhook_url', $settings->slack_webhook_url) }}"
                               placeholder="https://hooks.slack.com/services/...">
                    </div>
                </div>

                {{-- Discord --}}
                <div class="card custom-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="ri-discord-line me-2"></i>Discord</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="channel_discord" value="1"
                                       {{ in_array('discord', $channels, true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Enable</label>
                            </span>
                            <form method="POST" action="{{ route('notifications.test', 'discord') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Send test</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label small">Webhook URL</label>
                        <input type="url" class="form-control form-control-sm" name="discord_webhook_url"
                               value="{{ old('discord_webhook_url', $settings->discord_webhook_url) }}"
                               placeholder="https://discord.com/api/webhooks/...">
                    </div>
                </div>

                {{-- Microsoft Teams --}}
                <div class="card custom-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="ri-microsoft-line me-2"></i>Microsoft Teams</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="channel_teams" value="1"
                                       {{ in_array('teams', $channels, true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Enable</label>
                            </span>
                            <form method="POST" action="{{ route('notifications.test', 'teams') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Send test</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label small">Incoming Webhook URL</label>
                        <input type="url" class="form-control form-control-sm" name="teams_webhook_url"
                               value="{{ old('teams_webhook_url', $settings->teams_webhook_url) }}"
                               placeholder="https://outlook.office.com/webhook/...">
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" form="notificationsForm" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Save all settings
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
