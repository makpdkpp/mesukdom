@extends('layouts.adminlte')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Tenant Management</h3>
        <a href="{{ route('admin.platform') }}" class="btn btn-sm btn-outline-secondary">Back to Platform Admin</a>
    </div>
    <form method="GET" action="{{ route('admin.tenants') }}" class="border-bottom">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-md-0">
                        <label for="tenant-filter-q">Search</label>
                        <input id="tenant-filter-q" type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Search tenant name or domain">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="tenant-filter-plan">Plan</label>
                        <select id="tenant-filter-plan" name="plan_id" class="form-control">
                            <option value="">All plans</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($filters['plan_id'] === (string) $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="tenant-filter-status">Status</label>
                        <select id="tenant-filter-status" name="status" class="form-control">
                            <option value="all" @selected($filters['status'] === 'all')>All active tenants</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="pending_checkout" @selected($filters['status'] === 'pending_checkout')>Pending checkout</option>
                            <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
                            <option value="deleted" @selected($filters['status'] === 'deleted')>Deleted</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="mb-md-0 w-100 d-flex">
                        <button class="btn btn-primary btn-block mr-2">Filter</button>
                        <a href="{{ route('admin.tenants') }}" class="btn btn-outline-secondary btn-block mt-0">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="card-body border-bottom bg-light">
        <strong>Archive tenant</strong> will soft delete the tenant so historical records remain available in the `Deleted` filter.
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Domain</th>
                    <th>Users</th>
                    <th>Plan</th>
                    <th>Slip verification usage</th>
                    <th>Status</th>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tenants as $tenant)
                @php($subscriptionPlan = $tenant->resolvedPlan())
                <tr>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->domain ?: '-' }}</td>
                    <td>{{ $tenant->users_count }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.tenants.plan.update', $tenant) }}" class="form-inline">
                            @csrf
                            @method('PATCH')
                            <select name="plan_id" class="form-control form-control-sm mr-2">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected((string) $tenant->plan_id === (string) $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-xs btn-outline-primary">Update</button>
                        </form>
                    </td>
                    <td>
                        @php($used = (int) ($slipOkUsageByTenant[$tenant->id] ?? 0))
                        @php($limit = $subscriptionPlan?->slipOkMonthlyLimit() ?? 0)
                        @if($subscriptionPlan?->supportsSlipOk())
                            <span class="badge badge-info">{{ $used }} / {{ $limit > 0 ? $limit : 'Unlimited' }}</span>
                        @else
                            <span class="badge badge-secondary">Not included</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->trashed())
                            <span class="badge badge-dark">Deleted</span>
                        @elseif($tenant->status === 'suspended')
                            <span class="badge badge-danger">Suspended</span>
                        @else
                            <span class="badge badge-success">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $tenant->deleted_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>
                        @if($tenant->trashed())
                            <form method="POST" action="{{ route('admin.tenants.restore', $tenant->id) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-xs btn-outline-success">Restore</button>
                            </form>
                        @elseif($tenant->status === 'suspended')
                            <form method="POST" action="{{ route('admin.tenants.unsuspend', $tenant) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-xs btn-success" onclick="return confirm('Reactivate this tenant?')">Unsuspend</button>
                            </form>
                            <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="d-inline" id="delete-tenant-form-{{ $tenant->id }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    class="btn btn-xs btn-outline-danger"
                                    data-toggle="modal"
                                    data-target="#deleteTenantModal"
                                    data-tenant-name="{{ $tenant->name }}"
                                    data-tenant-form="delete-tenant-form-{{ $tenant->id }}"
                                >Archive</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="d-inline mr-1">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-xs btn-danger" onclick="return confirm('Suspend this tenant?')">Suspend</button>
                            </form>
                            <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="d-inline" id="delete-tenant-form-{{ $tenant->id }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    class="btn btn-xs btn-outline-danger"
                                    data-toggle="modal"
                                    data-target="#deleteTenantModal"
                                    data-tenant-name="{{ $tenant->name }}"
                                    data-tenant-form="delete-tenant-form-{{ $tenant->id }}"
                                >Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No tenants found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())
        <div class="card-footer clearfix">
            {{ $tenants->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>

<div class="modal fade" id="deleteTenantModal" tabindex="-1" role="dialog" aria-labelledby="deleteTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteTenantModalLabel">Archive Tenant</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Archive tenant <strong id="deleteTenantName">-</strong>?</p>
                <p class="mb-0 text-muted">The tenant will be soft deleted and hidden from active lists. Historical records remain available through the Deleted filter.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTenantButton">Archive Tenant</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteTenantModal = document.getElementById('deleteTenantModal');
        const deleteTenantName = document.getElementById('deleteTenantName');
        const confirmDeleteTenantButton = document.getElementById('confirmDeleteTenantButton');
        let activeDeleteFormId = null;

        $('#deleteTenantModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                activeDeleteFormId = null;
                deleteTenantName.textContent = '-';
                return;
            }

            activeDeleteFormId = button.getAttribute('data-tenant-form');
            deleteTenantName.textContent = button.getAttribute('data-tenant-name') || '-';
        });

        confirmDeleteTenantButton.addEventListener('click', function () {
            if (!activeDeleteFormId) {
                return;
            }

            const form = document.getElementById(activeDeleteFormId);

            if (form) {
                form.submit();
            }
        });

        deleteTenantModal.addEventListener('hidden.bs.modal', function () {
            activeDeleteFormId = null;
            deleteTenantName.textContent = '-';
        });
    });
</script>
@endsection