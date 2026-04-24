@extends('layouts.adminlte', ['title' => 'Account Profile', 'heading' => 'Account Profile'])

@push('head')
    {!! \App\Support\ViteAssets::render(['resources/css/app.css', 'resources/js/app.js']) !!}
    @livewireStyles
    <style>
        .profile-adminlte-page .profile-nav-card {
            position: sticky;
            top: 1rem;
        }

        .profile-adminlte-page .profile-avatar {
            width: 96px;
            height: 96px;
            object-fit: cover;
        }

        .profile-adminlte-page .profile-section + .profile-section {
            margin-top: 1.5rem;
        }

        .profile-adminlte-page .list-group-item i {
            width: 1.25rem;
        }

        .profile-adminlte-page .jetstream-modal {
            z-index: 1055;
        }
    </style>
@endpush

@section('content')
    @php($user = auth()->user())
    @php($isAdminProfile = $isAdminProfile ?? request()->routeIs('admin.*'))
    @php($showBillingLink = $showBillingLink ?? ! $isAdminProfile)
    @php($profileRouteName = $profileRouteName ?? 'profile.show')

    <div class="profile-adminlte-page">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-outline card-primary profile-nav-card">
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <img
                                src="{{ $user?->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user?->name ?? 'User').'&color=7F9CF5&background=EBF4FF' }}"
                                class="img-circle elevation-2 profile-avatar"
                                alt="{{ $user?->name ?? 'User' }}"
                            >
                        </div>

                        <h3 class="profile-username text-center mb-1">{{ $user?->name ?? 'User' }}</h3>
                        <p class="text-muted text-center mb-3">{{ $user?->email ?? '' }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            @if ($isAdminProfile)
                                <li class="list-group-item">
                                    <b>Workspace</b>
                                    <span class="float-right">Admin Console</span>
                                </li>
                            @else
                                <li class="list-group-item">
                                    <b>Tenant</b>
                                    <span class="float-right">{{ $currentTenant->name ?? 'Demo Tenant' }}</span>
                                </li>
                            @endif
                            <li class="list-group-item">
                                <b>Role</b>
                                <span class="float-right text-capitalize">{{ $user?->role ?? 'user' }}</span>
                            </li>
                            @if ($showBillingLink)
                                <li class="list-group-item">
                                    <b>Billing</b>
                                    <a href="{{ route('app.billing') }}" class="float-right">Open</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Account Menu</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route($profileRouteName) }}#profile-information" class="list-group-item list-group-item-action">
                            <i class="far fa-id-badge mr-2 text-primary"></i>Profile Information
                        </a>
                        <a href="{{ route($profileRouteName) }}#update-password" class="list-group-item list-group-item-action">
                            <i class="fas fa-key mr-2 text-primary"></i>Change Password
                        </a>
                        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                            <a href="{{ route($profileRouteName) }}#two-factor-authentication" class="list-group-item list-group-item-action">
                                <i class="fas fa-shield-alt mr-2 text-primary"></i>Two-Factor Authentication
                            </a>
                        @endif
                        <a href="{{ route($profileRouteName) }}#browser-sessions" class="list-group-item list-group-item-action">
                            <i class="fas fa-laptop mr-2 text-primary"></i>Browser Sessions
                        </a>
                        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                            <a href="{{ route($profileRouteName) }}#delete-account" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-user-times mr-2"></i>Delete Account
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h4 mb-2">Account Security</h2>
                        <p class="text-muted mb-0">
                            Manage your profile, password, sign-in protection, and active sessions using the same AdminLTE workspace shell as the rest of the app.
                        </p>
                    </div>
                </div>

                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    <div class="profile-section" id="profile-information">
                        @livewire('profile.update-profile-information-form')
                    </div>
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    <div class="profile-section" id="update-password">
                        @livewire('profile.update-password-form')
                    </div>
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <div class="profile-section" id="two-factor-authentication">
                        @livewire('profile.two-factor-authentication-form')
                    </div>
                @endif

                <div class="profile-section" id="browser-sessions">
                    @livewire('profile.logout-other-browser-sessions-form')
                </div>

                @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                    <div class="profile-section" id="delete-account">
                        @livewire('profile.delete-user-form')
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @livewireScripts
@endpush
