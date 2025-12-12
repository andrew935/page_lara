@extends('layouts.master')

@section('styles')
@endsection

@section('content')

    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Roles Management</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Add Role
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
                    <div class="card-title">All Roles</div>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control form-control-sm" placeholder="Search roles..." id="searchInput">
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
                                    <th scope="col">Role Name</th>
                                    <th scope="col">Permissions</th>
                                    <th scope="col" style="width: 150px;">Created Date</th>
                                    <th scope="col" class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $role->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm avatar-rounded me-2 bg-success-transparent">
                                                <i class="ri-shield-user-line fs-16"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">{{ $role->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-transparent">
                                            <i class="ri-key-2-line me-1"></i>{{ $role->permissions_count }} Permissions
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $role->created_at->format('M d, Y') }}</span>
                                    </td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-center">
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-primary-light btn-wave">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger-light btn-wave" onclick="return confirm('Are you sure you want to delete this role?')">
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
                                                <i class="ri-shield-user-line fs-1"></i>
                                            </div>
                                            <h6 class="fw-semibold mb-1">No Roles Found</h6>
                                            <p class="text-muted mb-3">Create your first role to get started</p>
                                            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i> Add Role
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($roles->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-2 mb-sm-0">
                            <span class="text-muted">
                                Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} entries
                            </span>
                        </div>
                        <div>
                            {{ $roles->links('pagination::bootstrap-5') }}
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

