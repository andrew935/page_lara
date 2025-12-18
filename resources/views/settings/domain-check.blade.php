@extends('layouts.master')

@section('styles')
@endsection

@section('content')
    <div class="page">
        @include('layouts.components.main-header')
        @include('layouts.components.main-sidebar')

        <div class="main-content app-content">
            <div class="container-fluid page-container main-body-container">
                
                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                    <h1 class="page-title fw-semibold fs-18 mb-0">Domain Check Settings</h1>
                    <div class="ms-md-1 ms-0">
                        <nav>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('domains.index') }}">Domains</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Check Settings</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Current Mode Status -->
                <div class="row">
                    <div class="col-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Current Configuration</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Active Mode: 
                                            <span class="badge {{ $currentMode === 'server' ? 'bg-primary' : 'bg-success' }} fs-14">
                                                {{ $modeInfo['name'] }}
                                            </span>
                                        </h5>
                                        <p class="text-muted mb-2">{{ $modeInfo['cost'] }} | {{ $modeInfo['speed'] }}</p>
                                        <p class="mb-0"><strong>Time to check 500 domains:</strong> {{ $modeInfo['total_time'] }}</p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        @if($currentMode === 'cloudflare')
                                            <span class="badge bg-warning-transparent">Requires Cloudflare Workers Paid Plan</span>
                                        @else
                                            <span class="badge bg-success-transparent">Running on Free Tier</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mode Selection -->
                <div class="row mt-4">
                    <div class="col-12">
                        <form method="POST" action="{{ route('settings.domain-check.update') }}">
                            @csrf
                            
                            <div class="row">
                                <!-- Server Mode Card -->
                                <div class="col-lg-6">
                                    <div class="card custom-card {{ $currentMode === 'server' ? 'border-primary' : '' }}">
                                        <div class="card-header bg-primary-transparent">
                                            <div class="card-title d-flex align-items-center gap-2">
                                                <input type="radio" name="check_mode" value="server" id="mode-server" 
                                                       {{ $currentMode === 'server' ? 'checked' : '' }} 
                                                       class="form-check-input mt-0">
                                                <label for="mode-server" class="mb-0 fs-16 fw-semibold">
                                                    Server-Based Checking
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <span class="badge bg-success fs-13">FREE</span>
                                                <span class="badge bg-secondary-transparent fs-13">10 domains/minute</span>
                                            </div>
                                            
                                            <h6 class="fw-semibold mb-2">✅ Pros:</h6>
                                            <ul class="mb-3">
                                                @foreach($modes['server']['pros'] as $pro)
                                                    <li>{{ $pro }}</li>
                                                @endforeach
                                            </ul>
                                            
                                            <h6 class="fw-semibold mb-2">⚠️ Cons:</h6>
                                            <ul class="mb-3">
                                                @foreach($modes['server']['cons'] as $con)
                                                    <li>{{ $con }}</li>
                                                @endforeach
                                            </ul>

                                            <div class="alert alert-info mb-0">
                                                <strong>Best for:</strong> Up to 500 domains, cost-conscious users
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cloudflare Mode Card -->
                                <div class="col-lg-6">
                                    <div class="card custom-card {{ $currentMode === 'cloudflare' ? 'border-success' : '' }}">
                                        <div class="card-header bg-success-transparent">
                                            <div class="card-title d-flex align-items-center gap-2">
                                                <input type="radio" name="check_mode" value="cloudflare" id="mode-cloudflare" 
                                                       {{ $currentMode === 'cloudflare' ? 'checked' : '' }} 
                                                       class="form-check-input mt-0">
                                                <label for="mode-cloudflare" class="mb-0 fs-16 fw-semibold">
                                                    Cloudflare Workers
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <span class="badge bg-warning fs-13">$5/month</span>
                                                <span class="badge bg-success-transparent fs-13">50 concurrent workers</span>
                                            </div>
                                            
                                            <h6 class="fw-semibold mb-2">✅ Pros:</h6>
                                            <ul class="mb-3">
                                                @foreach($modes['cloudflare']['pros'] as $pro)
                                                    <li>{{ $pro }}</li>
                                                @endforeach
                                            </ul>
                                            
                                            <h6 class="fw-semibold mb-2">⚠️ Cons:</h6>
                                            <ul class="mb-3">
                                                @foreach($modes['cloudflare']['cons'] as $con)
                                                    <li>{{ $con }}</li>
                                                @endforeach
                                            </ul>

                                            <div class="alert alert-warning mb-0">
                                                <strong>Best for:</strong> 500+ domains, need fast checking, have budget
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card custom-card">
                                        <div class="card-body text-center">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="ri-save-line me-2"></i>Save Configuration
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Setup Instructions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Setup Instructions</div>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="setupAccordion">
                                    <!-- Server Mode Instructions -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button {{ $currentMode === 'server' ? '' : 'collapsed' }}" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#serverInstructions">
                                                Server Mode Setup
                                            </button>
                                        </h2>
                                        <div id="serverInstructions" class="accordion-collapse collapse {{ $currentMode === 'server' ? 'show' : '' }}">
                                            <div class="accordion-body">
                                                <p>Server mode is active by default. To ensure it's running:</p>
                                                <pre class="bg-light p-3 rounded"><code>cd ~/page_lara
docker compose ps  # Check scheduler is running
docker compose logs scheduler -f  # View logs</code></pre>
                                                <p class="mb-0">The scheduler runs every minute and checks 10 domains per run.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cloudflare Mode Instructions -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button {{ $currentMode === 'cloudflare' ? '' : 'collapsed' }}" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#cloudflareInstructions">
                                                Cloudflare Mode Setup
                                            </button>
                                        </h2>
                                        <div id="cloudflareInstructions" class="accordion-collapse collapse {{ $currentMode === 'cloudflare' ? 'show' : '' }}">
                                            <div class="accordion-body">
                                                <p><strong>Prerequisites:</strong></p>
                                                <ol>
                                                    <li>Cloudflare account with <a href="https://dash.cloudflare.com/workers/plans" target="_blank">Workers Paid plan ($5/month)</a></li>
                                                    <li>Node.js 18+ installed locally</li>
                                                    <li>Wrangler CLI installed: <code>npm install -g wrangler</code></li>
                                                </ol>

                                                <p><strong>Setup Steps:</strong></p>
                                                <pre class="bg-light p-3 rounded"><code>cd cloudflare
npm install
wrangler login
wrangler queues create domain-check-queue
wrangler queues create domain-check-dlq
wrangler secret put LARAVEL_API_URL  # Enter: {{ url('/') }}
wrangler secret put WEBHOOK_SECRET   # Generate with: openssl rand -hex 32
npm run deploy</code></pre>

                                                <p><strong>On Server:</strong></p>
                                                <pre class="bg-light p-3 rounded"><code>cd ~/page_lara
nano docker/env.docker  # Add CLOUDFLARE_WEBHOOK_SECRET=...
docker compose restart app
docker compose stop scheduler  # Stop server checking</code></pre>

                                                <p class="mb-0">See <code>cloudflare/README.md</code> for detailed instructions.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @include('layouts.components.main-footer')
    </div>
@endsection

@section('scripts')
@endsection

