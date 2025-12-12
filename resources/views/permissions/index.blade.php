@extends('layouts.master')

@section('styles')
@endsection

@section('content')

    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Permissions Management</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Add Permission
            </a>
        </div>
    </div>
    <!-- End::page-header -->

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Start::row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">All Permissions</div>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control form-control-sm" placeholder="Search permissions..." id="searchInput">
                            <button class="btn btn-sm btn-primary" type="button">
                                <i class="ri-search-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center" style="width: 80px;">ID</th>
                                    <th scope="col">Permission Name</th>
                                    <th scope="col">Assigned Roles</th>
                                    <th scope="col" style="width: 150px;">Created Date</th>
                                    <th scope="col" class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $permission)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $permission->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm avatar-rounded me-2 bg-primary-transparent">
                                                <i class="ri-shield-keyhole-line fs-16"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">{{ $permission->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($permission->roles as $role)
                                                <span class="badge bg-primary-transparent">
                                                    <i class="ri-shield-user-line me-1"></i>{{ $role->name }}
                                                </span>
                                            @empty
                                                <span class="badge bg-secondary-transparent">
                                                    <i class="ri-forbid-line me-1"></i>Not assigned
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $permission->created_at->format('M d, Y') }}</span>
                                    </td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-center">
                                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-primary-light btn-wave">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('permissions.destroy', $permission) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger-light btn-wave" onclick="return confirm('Are you sure you want to delete this permission?')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                                <i class="ri-shield-keyhole-line fs-1"></i>
                                            </div>
                                            <h6 class="fw-semibold mb-1">No Permissions Found</h6>
                                            <p class="text-muted mb-3">Create your first permission to get started</p>
                                            <a href="{{ route('permissions.create') }}" class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Add Permission
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($permissions->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-2 mb-sm-0">
                            <span class="text-muted">
                                Showing {{ $permissions->firstItem() }} to {{ $permissions->lastItem() }} of {{ $permissions->total() }} entries
                            </span>
                        </div>
                        <div>
                            {{ $permissions->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End::row -->

@endsection

@section('scripts')
<script>
    // Simple search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(function(row) {
                    let text = row.textContent.toLowerCase();
                    if (text.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endsection

