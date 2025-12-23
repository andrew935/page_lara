@extends('layouts.master')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Create Promotion</h1>
            <div class="text-muted fs-12">New users who register during the window get Max for free for N days</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8 col-lg-10 col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Promotion details</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.promotions.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. New Year Promo" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Registration window start (optional)</label>
                                <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                                <small class="text-muted">If empty, promo can start immediately.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Registration window end (optional)</label>
                                <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                                <small class="text-muted">If empty, promo has no end date (not recommended).</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Duration (days)</label>
                                <input type="number" name="duration_days" class="form-control" min="1" max="3650" value="{{ old('duration_days', 60) }}" required>
                                <small class="text-muted">Example: 60 days ≈ 2 months.</small>
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" value="1" id="active" name="active" {{ old('active') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active">
                                        Activate immediately (only one promo can be active)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <a href="{{ route('admin.promotions.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Create promotion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection


