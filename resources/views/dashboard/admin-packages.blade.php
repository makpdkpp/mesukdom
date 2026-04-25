@extends('layouts.adminlte')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>Package could not be created.</strong>
        <div class="mt-1">{{ $errors->first() }}</div>
    </div>
@endif

<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Create Package</h3></div>
    <form method="POST" action="{{ route('admin.packages.store') }}">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="optional-auto-generate">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Price / month</label>
                        <input type="number" step="0.01" min="0" name="price_monthly" class="form-control" value="{{ old('price_monthly') }}" required>
                        <small class="form-text text-muted">For custom room pricing, this becomes the starting price shown to customers.</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group form-check mt-4">
                        <input type="hidden" name="custom_room_pricing" value="0">
                        <input type="checkbox" class="form-check-input" id="create_custom_room_pricing" name="custom_room_pricing" value="1" @checked(old('custom_room_pricing') == '1')>
                        <label class="form-check-label" for="create_custom_room_pricing">Custom room pricing</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room price / month</label>
                        <input type="number" step="0.01" min="0" name="room_price_monthly" class="form-control" value="{{ old('room_price_monthly') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Stripe Price ID</label>
                        <input type="text" name="stripe_price_id" class="form-control" value="{{ old('stripe_price_id') }}" placeholder="price_xxx">
                        <small class="form-text text-muted">Leave blank to auto-create for fixed packages. Custom room pricing packages calculate Stripe line items dynamically.</small>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group form-check mt-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="create_is_active" name="is_active" value="1" @checked(old('is_active', '1') == '1')>
                        <label class="form-check-label" for="create_is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Sort</label>
                        <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', '0') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room Limit</label>
                        <input type="number" min="0" name="rooms_limit" class="form-control" value="{{ old('rooms_limit', '0') }}">
                        <small class="form-text text-muted">Use for fixed packages only.</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group form-check mt-4">
                        <input type="hidden" name="recommended" value="0">
                        <input type="checkbox" class="form-check-input" id="create_recommended" name="recommended" value="1" @checked(old('recommended') == '1')>
                        <label class="form-check-label" for="create_recommended">Recommended</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group form-check mt-4">
                        <input type="hidden" name="slipok_enabled" value="0">
                        <input type="checkbox" class="form-check-input" id="create_slipok_enabled" name="slipok_enabled" value="1" @checked(old('slipok_enabled') == '1')>
                        <label class="form-check-label" for="create_slipok_enabled">SlipOK Addon</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>SlipOK price / room</label>
                        <input type="number" step="0.01" min="0" name="slipok_addon_price_monthly" class="form-control" value="{{ old('slipok_addon_price_monthly', '0') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>SlipOK Limit</label>
                        <input type="number" min="0" name="slipok_monthly_limit" class="form-control" value="{{ old('slipok_monthly_limit', '0') }}">
                        <small class="form-text text-muted">Use for fixed packages only.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary">Create Package</button>
        </div>
    </form>
</div>

<div class="card card-secondary">
    <div class="card-header"><h3 class="card-title">Package Management</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Mode</th>
                <th>Price</th>
                <th>Room Price</th>
                <th>Stripe Price ID</th>
                <th>Room Limit</th>
                <th>Recommended</th>
                <th>SlipOK Addon</th>
                <th>SlipOK Price</th>
                <th>Monthly Limit</th>
                <th>Sort</th>
                <th>Active</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <form method="POST" action="{{ route('admin.packages.update', $plan) }}">
                        @csrf
                        @method('PATCH')
                        <td><input type="text" name="name" class="form-control form-control-sm" value="{{ $plan->name }}" required></td>
                        <td><input type="text" name="slug" class="form-control form-control-sm" value="{{ $plan->slug }}" required></td>
                        <td>
                            <input type="hidden" name="custom_room_pricing" value="0">
                            <input type="checkbox" name="custom_room_pricing" value="1" @checked($plan->usesCustomRoomPricing())>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="price_monthly" class="form-control form-control-sm" value="{{ $plan->price_monthly }}" required>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="room_price_monthly" class="form-control form-control-sm" value="{{ $plan->usesCustomRoomPricing() ? $plan->roomPriceMonthly() : '' }}"></td>
                        <td>
                            <input type="text" name="stripe_price_id" class="form-control form-control-sm" value="{{ $plan->stripe_price_id }}" placeholder="price_xxx">
                            <small class="text-muted d-block mt-1">Fixed packages only</small>
                        </td>
                        <td><input type="number" min="0" name="rooms_limit" class="form-control form-control-sm" value="{{ $plan->usesCustomRoomPricing() ? 0 : $plan->roomsLimit() }}"></td>
                        <td>
                            <input type="hidden" name="recommended" value="0">
                            <input type="checkbox" name="recommended" value="1" @checked($plan->isRecommended())>
                        </td>
                        <td>
                            <input type="hidden" name="slipok_enabled" value="0">
                            <input type="checkbox" name="slipok_enabled" value="1" @checked($plan->supportsSlipOk())>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="slipok_addon_price_monthly" class="form-control form-control-sm" value="{{ $plan->usesCustomRoomPricing() ? $plan->slipAddonPriceMonthly() : 0 }}"></td>
                        <td><input type="number" min="0" name="slipok_monthly_limit" class="form-control form-control-sm" value="{{ $plan->usesCustomRoomPricing() ? 0 : $plan->slipOkMonthlyLimit() }}"></td>
                        <td><input type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ $plan->sort_order }}"></td>
                        <td>
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($plan->is_active)>
                        </td>
                        <input type="hidden" name="description" value="{{ $plan->description }}">
                        <td>
                            <div class="d-flex">
                                <button class="btn btn-xs btn-outline-primary mr-1">Save</button>
                            </div>
                    </form>
                            <form method="POST" action="{{ route('admin.packages.destroy', $plan) }}" onsubmit="return confirm('Delete this package?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @empty
                <tr><td colspan="14" class="text-center text-muted">No packages yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
