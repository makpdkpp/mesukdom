<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MesukDorm Dormitory SaaS' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        .brand-link { background: linear-gradient(135deg, #198754, #0d6efd); }
        .content-header h1 { font-size: 1.5rem; }
        .small-box .icon i { font-size: 56px; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item text-sm text-muted pt-2 pr-2">Tenant: {{ $currentTenant->name ?? 'Demo Tenant' }}</li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary mt-1">Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        @php($isAdminRoute = request()->routeIs('admin.*'))
        <a href="{{ $isAdminRoute ? route('admin.dashboard') : route('app.dashboard') }}" class="brand-link text-center">
            <span class="brand-text font-weight-light">MesukDorm</span>
        </a>

        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    @if($isAdminRoute)
                        <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="nav-link"><i class="nav-icon fas fa-chart-line"></i><p>Dashboard Admin</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.dbmigration') }}" class="nav-link"><i class="nav-icon fas fa-database"></i><p>DBmigration</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.packages') }}" class="nav-link"><i class="nav-icon fas fa-boxes"></i><p>Package Management</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.platform') }}" class="nav-link"><i class="nav-icon fas fa-cogs"></i><p>Platform Admin</p></a></li>
                    @else
                        <li class="nav-item"><a href="{{ route('app.dashboard') }}" class="nav-link"><i class="nav-icon fas fa-chart-pie"></i><p>Dashboard</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.rooms') }}" class="nav-link"><i class="nav-icon fas fa-door-open"></i><p>Rooms</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.customers') }}" class="nav-link"><i class="nav-icon fas fa-users"></i><p>Residents</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.contracts') }}" class="nav-link"><i class="nav-icon fas fa-file-signature"></i><p>Contracts</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.invoices') }}" class="nav-link"><i class="nav-icon fas fa-file-invoice-dollar"></i><p>Invoices</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.payments') }}" class="nav-link"><i class="nav-icon fas fa-money-check-alt"></i><p>Payments</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.billing') }}" class="nav-link"><i class="nav-icon fas fa-credit-card"></i><p>Billing</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.line-activity') }}" class="nav-link"><i class="nav-icon fab fa-line"></i><p>LINE Activity</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.broadcasts') }}" class="nav-link"><i class="nav-icon fas fa-bullhorn"></i><p>Broadcast</p></a></li>
                        <li class="nav-item"><a href="{{ route('app.settings') }}" class="nav-link"><i class="nav-icon fas fa-cog"></i><p>Settings</p></a></li>
                        @can('accessAdminPortal')
                            <li class="nav-item"><a href="{{ route('admin.platform') }}" class="nav-link"><i class="nav-icon fas fa-cogs"></i><p>Platform Admin</p></a></li>
                        @endcan
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $heading ?? 'Dormitory SaaS' }}</h1>
                    </div>
                    <div class="col-sm-6 text-right text-muted">
                        Multi-tenant • LINE OA ready • Laravel
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if (session('status_card'))
                    @php($statusCard = session('status_card'))
                    @php($addFriendQrSvg = !empty($statusCard['add_friend_url']) ? app(\App\Services\QrCodeService::class)->generateSvg($statusCard['add_friend_url'], 132) : null)
                    <div class="alert alert-{{ $statusCard['theme'] ?? 'success' }} border shadow-sm">
                        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;">
                            <div>
                                <div class="text-uppercase small font-weight-bold text-muted">{{ $statusCard['title'] ?? 'Success' }}</div>
                                <div class="mt-1">
                                    สร้างโค้ดเชื่อม LINE สำหรับ <strong>{{ $statusCard['customer'] ?? 'resident' }}</strong> เรียบร้อยแล้ว
                                </div>
                                @if(!empty($statusCard['expires_at']))
                                    <div class="small mt-1"><strong>Expires:</strong> {{ $statusCard['expires_at'] }}</div>
                                @endif
                            </div>
                            @if(!empty($statusCard['code']))
                                <div class="text-md-right">
                                    <div class="badge badge-dark px-3 py-2" style="font-family:monospace;font-size:20px;letter-spacing:.24em;">{{ $statusCard['code'] }}</div>
                                    @if(!empty($statusCard['instruction']))
                                        <div class="small mt-2">How to link: <strong>{{ $statusCard['instruction'] }}</strong></div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if(!empty($statusCard['add_friend_url']) || !empty($statusCard['link_url']))
                            <div class="d-flex flex-wrap align-items-center mt-3" style="gap:16px;">
                                @if($addFriendQrSvg)
                                    <div class="border rounded bg-white p-2" style="width:152px;">
                                        {!! $addFriendQrSvg !!}
                                    </div>
                                @endif
                                <div>
                                    @if(!empty($statusCard['add_friend_url']))
                                        <a href="{{ $statusCard['add_friend_url'] }}" target="_blank" class="btn btn-sm btn-success mr-2 mb-2">
                                            <i class="fab fa-line mr-1"></i> Add Friend
                                        </a>
                                    @endif
                                    @if(!empty($statusCard['link_url']))
                                        <a href="{{ $statusCard['link_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">Open Link Portal</a>
                                        <div class="small mt-1 text-muted">Signed portal link: {{ $statusCard['link_url'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-sm">
        <strong>MesukDorm</strong> dormitory management SaaS MVP
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
