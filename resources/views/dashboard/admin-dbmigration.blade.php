@extends('layouts.adminlte')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
@endif

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0">Database Migration Manager</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-warning border">
            <strong>คำเตือน:</strong> กรุณา backup database ก่อนทำการ migrate ทุกครั้ง! การ migrate อาจส่งผลกระทบต่อข้อมูลในระบบ
        </div>

        <div class="row">
            <div class="col-lg-3 col-6 mb-3">
                <div class="small-box bg-info mb-0">
                    <div class="inner">
                        <h3>{{ $totalMigrations }}</h3>
                        <p>Total Migrations</p>
                    </div>
                    <div class="icon"><i class="fas fa-code-branch"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3">
                <div class="small-box bg-success mb-0">
                    <div class="inner">
                        <h3>{{ $completedMigrations }}</h3>
                        <p>Completed</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3">
                <div class="small-box bg-warning mb-0">
                    <div class="inner">
                        <h3>{{ $pendingMigrations }}</h3>
                        <p>Pending</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3">
                <div class="small-box bg-primary mb-0">
                    <div class="inner">
                        <h3>OK</h3>
                        <p>Database Connected</p>
                    </div>
                    <div class="icon"><i class="fas fa-database"></i></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap mb-3 align-items-end" style="gap:10px;">
            <form method="POST" action="{{ route('admin.maintenance.migrate') }}" class="d-flex flex-wrap align-items-end" style="gap:8px;">
                @csrf
                <div>
                    <label class="d-block text-sm text-muted mb-1" style="font-size:11px;">MIGRATE_TOKEN</label>
                    <input type="password" name="migrate_token" class="form-control form-control-sm" placeholder="Enter token..." autocomplete="off" required style="max-width:220px;">
                </div>
                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Run all pending migrations now?');">
                    <i class="fas fa-play mr-1"></i> Run All Pending Migrations
                </button>
            </form>

            <form method="POST" action="{{ route('admin.maintenance.rollback') }}" class="d-flex flex-wrap align-items-end" style="gap:8px;">
                @csrf
                <div>
                    <label class="d-block text-sm text-muted mb-1" style="font-size:11px;">MIGRATE_TOKEN</label>
                    <input type="password" name="migrate_token" class="form-control form-control-sm" placeholder="Enter token..." autocomplete="off" required style="max-width:220px;">
                </div>
                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Rollback last migration batch (Batch {{ $maxBatch }}) now?');">
                    <i class="fas fa-undo mr-1"></i> Rollback Last Batch
                </button>
            </form>

            <a href="{{ route('admin.dbmigration') }}" class="btn btn-sm btn-secondary align-self-end">
                <i class="fas fa-sync-alt mr-1"></i> Refresh Status
            </a>

            <button type="button" class="btn btn-sm btn-info align-self-end" data-toggle="collapse" data-target="#schemaSection" aria-expanded="false">
                <i class="fas fa-table mr-1"></i> View Database Schema
            </button>
        </div>

        @if(session('migrateOutput'))
            <pre class="border rounded p-2 small" style="white-space: pre-wrap;">{{ session('migrateOutput') }}</pre>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Migration Files</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Migration File</th>
                            <th style="width:80px;">Batch</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:160px;">Run Date</th>
                            <th style="width:90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($migrationItems as $index => $item)
                        <tr class="{{ $item['completed'] ? '' : 'table-warning' }}">
                            <td>{{ $index + 1 }}</td>
                            <td style="font-family:monospace;font-size:12px;">{{ $item['migration'] }}</td>
                            <td>{{ $item['batch'] ?? '-' }}</td>
                            <td>
                                @if($item['completed'])
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:12px;">
                                {{ $item['run_date'] ? \Carbon\Carbon::parse($item['run_date'])->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td>
                                @if($item['completed'])
                                    <span class="text-success"><i class="fas fa-check"></i> Done</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No migrations found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Database Schema Viewer --}}
        <div class="collapse" id="schemaSection">
            <div class="card mb-0">
                <div class="card-header bg-info">
                    <h3 class="card-title text-white"><i class="fas fa-table mr-1"></i> Database Schema</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool text-white" data-toggle="collapse" data-target="#schemaSection">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(empty($dbSchema))
                        <div class="p-3 text-muted">Unable to retrieve schema.</div>
                    @else
                        <div class="p-3">
                            <p class="text-muted mb-2">Total tables: <strong>{{ count($dbSchema) }}</strong></p>
                        </div>
                        <div class="accordion" id="schemaAccordion">
                            @foreach($dbSchema as $tableName => $columns)
                            <div class="card mb-0 rounded-0 border-top">
                                <div class="card-header p-0">
                                    <button class="btn btn-link btn-block text-left px-3 py-2" type="button"
                                        data-toggle="collapse" data-target="#table-{{ Str::slug($tableName) }}"
                                        style="font-family:monospace;font-weight:600;color:#333;text-decoration:none;">
                                        <i class="fas fa-table mr-2 text-secondary"></i>
                                        {{ $tableName }}
                                        <span class="badge badge-secondary ml-2">{{ count($columns) }} cols</span>
                                    </button>
                                </div>
                                <div id="table-{{ Str::slug($tableName) }}" class="collapse">
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Type</th>
                                                    <th>Null</th>
                                                    <th>Key</th>
                                                    <th>Default</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($columns as $col)
                                                <tr>
                                                    <td style="font-family:monospace;">{{ $col['field'] }}</td>
                                                    <td class="text-info">{{ $col['type'] }}</td>
                                                    <td>{{ $col['null'] }}</td>
                                                    <td>
                                                        @if($col['key'] === 'PRI') <span class="badge badge-warning">PRI</span>
                                                        @elseif($col['key'] === 'UNI') <span class="badge badge-info">UNI</span>
                                                        @elseif($col['key'] === 'MUL') <span class="badge badge-secondary">MUL</span>
                                                        @else {{ $col['key'] ?: '-' }}
                                                        @endif
                                                    </td>
                                                    <td class="text-muted">{{ $col['default'] ?? 'NULL' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
