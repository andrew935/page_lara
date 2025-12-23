@extends('layouts.master')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Promotions</h1>
            <div class="text-muted fs-12">New signups promo: Max for free for a limited time window</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Create Promotion
            </a>
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

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title mb-0">All Promotions</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 80px;" class="text-center">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Registration Window</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col" class="text-center" style="width: 120px;">Status</th>
                                    <th scope="col" class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotions as $promo)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $promo->id }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $promo->name }}</div>
                                            <div class="text-muted fs-12">Promo plan: {{ $promo->promo_plan_slug }}</div>
                                        </td>
                                        <td>
                                            <div class="text-muted fs-12">
                                                Start: {{ $promo->starts_at ? $promo->starts_at->format('M d, Y H:i') : '—' }}
                                            </div>
                                            <div class="text-muted fs-12">
                                                End: {{ $promo->ends_at ? $promo->ends_at->format('M d, Y H:i') : '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $promo->duration_days }}</span>
                                            <span class="text-muted">days</span>
                                        </td>
                                        <td class="text-center">
                                            @if($promo->active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('admin.promotions.toggle', $promo) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $promo->active ? 'btn-danger-light' : 'btn-primary-light' }} btn-wave">
                                                    {{ $promo->active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                                    <i class="ri-coupon-3-line fs-1"></i>
                                                </div>
                                                <h6 class="fw-semibold mb-1">No Promotions</h6>
                                                <p class="text-muted mb-3">Create a promotion to give new users Max for free for a limited time</p>
                                                <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary btn-sm">
                                                    <i class="ri-add-line me-1"></i> Create Promotion
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($promotions->hasPages())
                    <div class="card-footer py-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="mb-2 mb-sm-0">
                                <span class="text-muted">
                                    Showing {{ $promotions->firstItem() }} to {{ $promotions->lastItem() }} of {{ $promotions->total() }} entries
                                </span>
                            </div>
                            <div>
                                {{ $promotions->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection


