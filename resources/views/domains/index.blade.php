@extends('layouts.master')

@section('content')

    <style>
        .uptime-bar{
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
        }
        .uptime-seg.down {
            
            background-color: red;
        }
        .uptime-seg.up {
            
            background-color: #32d484;
        }
        .uptime-seg {
            width: 5px;
        }
        .uptime-bar .up{
            background-color: #32d484;
            min-width: 5px;
        }
        .uptime-bar .down{
 
            background-color: #ff6757;
            min-width: 5px;
        }
        .uptime-bar .idle{
            background-color: #6c7e96;
            min-width: 5px;
        }
    </style>

    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Domains</h1>
            <p class="text-muted mb-0">Liveness &amp; SSL status</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addDomainModal">
                <i class="ri-add-line me-1"></i> Add domain(s)
            </button>
            <button class="btn btn-primary btn-sm" id="btn-check-batch">
                <i class="ri-refresh-line me-1"></i> Manual check (all)
            </button>
        </div>
    </div>
    <!-- End::page-header -->

    <div id="alert-container"></div>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card custom-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Total Domains</div>
                        <div class="fs-4 fw-semibold">{{ $total }}</div>
                    </div>
                    <span class="avatar avatar-md bg-primary-transparent text-primary">
                        <i class="ri-links-line"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card custom-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Online</div>
                        <div class="fs-4 fw-semibold text-success">{{ $up }}</div>
                    </div>
                    <span class="avatar avatar-md bg-success-transparent text-success">
                        <i class="ri-checkbox-circle-line"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card custom-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Offline</div>
                        <div class="fs-4 fw-semibold text-danger">{{ $down }}</div>
                    </div>
                    <span class="avatar avatar-md bg-danger-transparent text-danger">
                        <i class="ri-close-circle-line"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card custom-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Pending</div>
                        <div class="fs-4 fw-semibold text-secondary">{{ $pending }}</div>
                    </div>
                    <span class="avatar avatar-md bg-secondary-transparent text-secondary">
                        <i class="ri-time-line"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Domain Modal -->
    <div class="modal fade" id="addDomainModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add domain(s)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Add one domain per line. We’ll create any new ones as pending.</p>
                    <form id="addDomainsForm">
                        <div class="mb-3">
                            <textarea name="domains" class="form-control" rows="6" placeholder="example.com&#10;another-domain.com" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-sm-6 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="ri-search-line text-muted"></i>
                        </span>
                        <input id="domainSearch" type="search" class="form-control border-start-0" placeholder="Search domains, campaigns, status...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>SSL</th>
                            <th>Last Checked</th>
                            <th>Campaign</th>
                           
                            <th>Error</th>
                            <th class="text-end" style="min-width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($domains as $domain)
                            <tr data-domain-id="{{ $domain->id }}">
                                <td class="col-domain">{{ $domain->domain }}</td>
                                <td class="col-status">
                                    @if($domain->status === 'ok')
                                        <span class="badge bg-success">Up</span>
                                    @elseif($domain->status === 'down')
                                        <span class="badge bg-danger">Down</span>
                                    @elseif($domain->status === 'pending')
                                        <span class="badge bg-secondary">Pending</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Error</span>
                                    @endif
                                </td>
                                <td class="col-ssl">
                                    @if(is_null($domain->ssl_valid))
                                        <span class="badge bg-secondary">Unknown</span>
                                    @elseif($domain->ssl_valid)
                                        <span class="badge bg-success">Valid</span>
                                    @else
                                        <span class="badge bg-danger">Invalid</span>
                                    @endif
                                </td>
                                <td class="col-checked">{{ $domain->last_checked_at ? $domain->last_checked_at->diffForHumans() : '—' }}</td>
                                <td class="col-campaign text-truncate" style="max-width:180px;">
                                    {{ $domain->campaign ?? '—' }}
                                </td>
                               
                                <td class="col-error" style="max-width: 360px;">
                                    @php
                                        $isDown = $domain->status === 'down';
                                        $downSince = $domain->status_since?->diffForHumans() ?? '—';
                                        $lastUp = $domain->last_up_at ? $domain->last_up_at->diffForHumans() : '—';
                                        $lastDown = $domain->last_down_at ? $domain->last_down_at->diffForHumans() : '—';
                                        $errMsg = $domain->last_check_error ?? '—';
                                        $history = $domain->lastcheck ?? ($domain->history ?? []);
                                        $segments = array_slice(is_array($history) ? $history : [], -12);
                                        $segments = array_map(function ($v) {
                                            if ($v === 1 || $v === 'up') return 'up';
                                            if ($v === 0 || $v === 2 || $v === 'down') return 'down';
                                            return 'idle';
                                        }, $segments);
                                    @endphp
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="{{ $isDown ? 'text-danger' : 'text-success' }}" data-bs-toggle="tooltip" title="{{ $errMsg }}">
                                            <i class="{{ $isDown ? 'ri-arrow-down-line' : 'ri-arrow-up-line' }}"></i>
                                        </span>
                                        <div class="w-100">
                                            <small class="text-muted d-block">
                                                @if($isDown)
                                                    Down since {{ $downSince }}  <br>last up {{ $lastUp }}
                                                @else
                                                    Up since {{ $downSince }}  <br>last down {{ $lastDown }}
                                                @endif
                                            </small>

                                            @if(!empty($segments))
                                                <div class="uptime-bar d-flex gap-1 mt-1">
                                                    @foreach($segments as $seg)
                                                        @php
                                                            $cls = match ($seg) {
                                                                'up' => 'up',
                                                                'down' => 'down',
                                                                default => 'idle',
                                                            };
                                                        @endphp
                                                    <span class="uptime-seg {{ $cls }}"></span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end" style="min-width: 140px; white-space: nowrap; padding: 8px;">
                                    <div class="btn-group gap-1" role="group">
                                        <button type="button" class="btn btn-primary btn-sm btn-check-domain" data-domain-id="{{ $domain->id }}" title="Check this domain">
                                            <i class="ri-search-line me-1"></i> Check
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-domain"
                                                data-domain-id="{{ $domain->id }}"
                                                data-domain="{{ $domain->domain }}"
                                                data-campaign="{{ $domain->campaign }}"
                                                title="Edit domain">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form method="POST" action="{{ route('domains.destroy', $domain) }}" onsubmit="return confirm('Delete this domain?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete domain">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No domains ingested yet.</td>
                            </tr>
                        @endforelse
                        <tr id="domains-no-results" class="d-none">
                            <td colspan="6" class="text-center py-4 text-muted">No matching domains.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer py-2">
            {{ $domains->links('pagination::bootstrap-5') }}
        </div>
    </div>

<!-- Edit Domain Modal -->
<div class="modal fade" id="editDomainModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Edit domain</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDomainForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Domain URL</label>
                        <input type="text" name="domain" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Campaign</label>
                        <input type="text" name="campaign" class="form-control" placeholder="optional">
                    </div>
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

@push('styles')
<style>
    #alert-container.alert-fixed {
         display: block;
        min-width: 280px;
        max-width: 520px;
        
    }
    /* #alert-container.alert-fixed .alert {
        opacity: 1 !important;
    } */
    .uptime-seg {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 2px;
    }
    .uptime-seg.up {
        background-color: #32d484;
    }
    .uptime-seg.down {
        background-color: #ff6757;
    }
    .uptime-seg.idle {
        background-color: rgba(108, 117, 125, 0.5);
    }
    .col-error {
        min-height: 48px;
    }
    #alert-container{
        z-index: 1080;
        position: sticky;
        top: 150px;
        max-width: 400px;
    }
    i.ri-arrow-up-line,
    i.ri-arrow-down-line {
        font-size: 2em;
    }
   
</style>
@endpush

@section('scripts')
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    // Use relative URLs to avoid http/https mixed-content issues behind reverse proxies (e.g. Cloudflare).
    const checkBase = @json('/domains');
    const routes = {
        // Use non-absolute route URLs (3rd param = false) so browser keeps current scheme/host.
        store: @json(route('domains.store', [], false)),
        checkAll: @json(route('domains.checkAll', [], false)),
        checkOne: function(id) {
            return `${checkBase}/${id}/check-now`;
        },
    };

    const alertContainer = document.getElementById('alert-container');
    if (alertContainer) {
        //alertContainer.classList.add('position-fixed',   );
        alertContainer.style.zIndex = '1080';
    }

    function showAlert(type, message) {
        const div = document.createElement('div');
        div.className = `alert alert-${type} alert-dismissible fade show  `;
        div.role = 'alert';
        div.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(div);
        setTimeout(() => div.classList.add('show'), 10);
        setTimeout(() => div.remove(), 5000);
    }

    function renderBadgeStatus(status) {
        if (status === 'ok') return '<span class="badge bg-success">Up</span>';
        if (status === 'down') return '<span class="badge bg-danger">Down</span>';
        if (status === 'pending') return '<span class="badge bg-secondary">Pending</span>';
        return '<span class="badge bg-warning text-dark">Error</span>';
    }

    function renderBadgeSsl(val) {
        if (val === null || val === undefined) return '<span class="badge bg-secondary">Unknown</span>';
        return val ? '<span class="badge bg-success">Valid</span>' : '<span class="badge bg-danger">Invalid</span>';
    }

    function initTooltips() {
        if (!window.bootstrap) return;
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => {
            if (el._tooltip) return;
            el._tooltip = new bootstrap.Tooltip(el);
        });
    }

    // Live search/filter
    const searchInput = document.getElementById('domainSearch');
    const tableBody = document.querySelector('table tbody');
    const noResultsRow = document.getElementById('domains-no-results');

    function applyFilter(term) {
        if (!tableBody) return;
        const q = term.trim().toLowerCase();
        let visible = 0;
        tableBody.querySelectorAll('tr[data-domain-id]').forEach(row => {
            const text = row.innerText.toLowerCase();
            const match = text.includes(q);
            row.classList.toggle('d-none', !match);
            if (match) visible++;
        });

        if (noResultsRow) {
            noResultsRow.classList.toggle('d-none', visible !== 0);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            applyFilter(e.target.value);
        });
    }

    function updateRow(domain) {
        let row = document.querySelector(`tr[data-domain-id="${domain.id}"]`);
        if (!row) {
            const tbody = document.querySelector('table tbody');
            row = document.createElement('tr');
            row.setAttribute('data-domain-id', domain.id);
            row.innerHTML = `
                <td class="col-domain"></td>
                <td class="col-status"></td>
                <td class="col-ssl"></td>
                <td class="col-checked"></td>
                <td class="col-campaign text-truncate" style="max-width:180px;"></td>
                <td class="col-error text-truncate" style="max-width:250px;"></td>
                <td class="text-end" style="min-width: 140px; white-space: nowrap; padding: 8px;">
                    <div class="btn-group gap-1" role="group">
                        <button type="button" class="btn btn-primary btn-sm btn-check-domain" data-domain-id="${domain.id}" title="Check this domain">
                            <i class="ri-search-line me-1"></i> Check
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-domain"
                            data-domain-id="${domain.id}" data-domain="${domain.domain ?? ''}" data-campaign="${domain.campaign ?? ''}"
                            title="Edit domain">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete-domain" data-domain-id="${domain.id}" title="Delete domain">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.prepend(row);
        }
        const cd = row.querySelector('.col-domain');
        const cs = row.querySelector('.col-status');
        const ssl = row.querySelector('.col-ssl');
        const chk = row.querySelector('.col-checked');
        const camp = row.querySelector('.col-campaign');
        const err = row.querySelector('.col-error');

        if (cd) cd.textContent = domain.domain;
        if (cs) cs.innerHTML = renderBadgeStatus(domain.status);
        if (ssl) ssl.innerHTML = renderBadgeSsl(domain.ssl_valid);
        if (chk) chk.textContent = domain.last_checked_at ?? '—';
        if (camp) camp.textContent = domain.campaign ?? '—';
        const editBtn = row.querySelector('.btn-edit-domain');
        if (editBtn) {
            editBtn.dataset.domain = domain.domain ?? '';
            editBtn.dataset.campaign = domain.campaign ?? '';
        }
        if (err) {
            const isDown = domain.status === 'down';
            const downSince = domain.status_since ?? '—';
            const lastUp = domain.last_up_at ?? '—';
            const lastDown = domain.last_down_at ?? '—';
            const errMsg = domain.error ?? '—';
            const history = Array.isArray(domain.lastcheck)
                ? domain.lastcheck
                : (Array.isArray(domain.history) ? domain.history : []);

            // Normalize history to uptime segments (last 12).
            let segments = history
                .slice(-12)
                .map(v => v === 1 || v === 'up' ? 'up' : ((v === 0 || v === 2 || v === 'down') ? 'down' : 'idle'));
            const segHtml = segments.length
                ? segments.map(seg => {
                    const cls = seg === 'up' ? 'up' : (seg === 'down' ? 'down' : 'idle');
                    return `<span class="uptime-seg ${cls}"></span>`;
                }).join('')
                : '';

            err.innerHTML = `
                <div class="d-flex align-items-start gap-2">
                    <span class="${isDown ? 'text-danger' : 'text-success'}" data-bs-toggle="tooltip" title="${errMsg}">
                        <i class="${isDown ? 'ri-arrow-down-line' : 'ri-arrow-up-line'}"></i>
                    </span>
                    <div class="w-100">
                        <small class="text-muted d-block">
                            ${isDown
                                ? `Down since ${downSince}<br>last up ${lastUp}`
                                : `Up since ${downSince}<br>last down ${lastDown}`
                            }
                        </small>
                        ${segHtml ? `<div class="uptime-bar d-flex gap-1 mt-1">${segHtml}</div>` : ``}
                    </div>
                </div>
            `;
            initTooltips();
        }
    }

    async function postJson(url, body = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            // Prefer a clean message from JSON; avoid showing raw HTML/stack traces in UI.
            let msg = 'Request failed';
            try {
                const data = await res.json();
                msg = data?.message || msg;
            } catch (e) {
                const text = await res.text();
                if (text && typeof text === 'string') {
                    msg = (text.includes('<!DOCTYPE') || text.includes('<html')) ? 'Request failed' : text;
                }
            }
            throw new Error(msg);
        }
        try {
            return await res.json();
        } catch (e) {
            throw new Error('Unexpected response from server.');
        }
    }

    // Add domains
    document.getElementById('addDomainsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const textarea = e.target.querySelector('textarea[name="domains"]');
        try {
            const data = await postJson(routes.store, { domains: textarea.value });
            (data.domains || []).forEach(updateRow);
            showAlert('success', data.message || 'Domains added.');
            textarea.value = '';
            const modalEl = document.getElementById('addDomainModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } catch (err) {
            showAlert('danger', err.message);
        }
    });

    // Check batch (manual all)
    const btnCheckBatch = document.getElementById('btn-check-batch');
    if (btnCheckBatch) {
        btnCheckBatch.addEventListener('click', async () => {
            const originalHtml = btnCheckBatch.innerHTML;
            btnCheckBatch.disabled = true;
            btnCheckBatch.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Checking...`;
            try {
                const data = await postJson(routes.checkAll);
                (data.domains || []).forEach(updateRow);
                showAlert('success', data.message || 'Checked batch.');
                initTooltips();
            } catch (err) {
                showAlert('danger', err.message || 'Error running manual check.');
            } finally {
                btnCheckBatch.disabled = false;
                btnCheckBatch.innerHTML = originalHtml;
            }
        });
    }

    // Check single domain - event delegation
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-check-domain');
        if (!btn) return;
        
        const id = btn.dataset.domainId;
        if (!id) return;
        
        btn.disabled = true;
        
        try {
            const url = routes.checkOne(id);
            const data = await postJson(url);
            if (data.domain) updateRow(data.domain);
            showAlert('success', 'Domain checked.');
            initTooltips();
        } catch (err) {
            const raw = (err && err.message) ? String(err.message) : '';
            const safeMsg = raw && raw.length < 200 && !raw.includes('api.telegram.org')
                ? raw
                : 'Error checking domain.';
            showAlert('danger', safeMsg);
        } finally {
            btn.disabled = false;
        }
    });

    // Delete domain (AJAX)
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-delete-domain');
        if (!btn) return;
        if (!confirm('Delete this domain?')) return;

        const id = btn.dataset.domainId;
        if (!id) return;

        btn.disabled = true;
        try {
            const res = await fetch(`${checkBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) {
                const text = await res.text();
                throw new Error(text || 'Delete failed');
            }
            // remove row
            const row = document.querySelector(`tr[data-domain-id="${id}"]`);
            if (row) row.remove();
            showAlert('success', 'Domain deleted.');
        } catch (err) {
            showAlert('danger', err.message || 'Error deleting domain.');
        } finally {
            btn.disabled = false;
        }
    });

    // Edit domain - open modal
    const editForm = document.getElementById('editDomainForm');
    const editModalEl = document.getElementById('editDomainModal');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-domain');
        if (!btn || !editForm || !editModal) return;
        const id = btn.dataset.domainId;
        const domainVal = btn.dataset.domain || '';
        const campaignVal = btn.dataset.campaign || '';
        editForm.action = `${checkBase}/${id}`;
        editForm.querySelector('input[name="domain"]').value = domainVal;
        editForm.querySelector('input[name="campaign"]').value = campaignVal;
        editModal.show();
    });

    // init existing tooltips on load
    initTooltips();

})();
</script>
@endsection