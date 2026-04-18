@extends('layouts.adminlte', ['title' => 'Room Status', 'heading' => 'Room Status'])

@push('head')
<style>
    .vacant-room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.75rem;
    }

    .vacant-room-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        min-height: 108px;
    }

    .vacant-room-card--vacant {
        border-color: #86efac;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    }

    .vacant-room-card--occupied {
        border-color: #fca5a5;
        background: linear-gradient(180deg, #fef2f2 0%, #ffffff 100%);
    }

    .vacant-room-card--maintenance {
        border-color: #fcd34d;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }

    .vacant-room-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        padding: 1.25rem 0.75rem;
        text-align: center;
    }

    .vacant-room-number {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        color: #3f3424;
    }

    .vacant-room-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
    }

    .vacant-room-badge--occupied {
        background: #fee2e2;
        color: #b91c1c;
    }

    .vacant-room-badge--maintenance {
        background: #fef3c7;
        color: #a16207;
    }

    .room-filter-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .room-filter-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
    }

    .room-filter-link:hover {
        color: #1e293b;
        border-color: #94a3b8;
        text-decoration: none;
    }

    .room-filter-link.is-active {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    .vacant-room-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 0.75rem;
        background: #f8fafc;
        padding: 1.5rem;
        text-align: center;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:0.75rem;">
                <h3 class="card-title mb-0">Dormitory Dashboard</h3>
                <div class="text-muted small">ติดตามสถานะห้องทั้งหมดของ {{ $tenant->name ?? 'your dormitory' }}</div>
            </div>
            <div class="card-body">
                <p class="mb-3">Room Status for {{ $tenant->name ?? 'your dormitory' }}</p>
                <div class="room-filter-links mb-3" aria-label="Room status filters">
                    <a href="{{ route('app.room-status') }}" class="room-filter-link {{ $roomStatusFilter === 'all' ? 'is-active' : '' }}">ทั้งหมด</a>
                    <a href="{{ route('app.room-status', ['room_status' => 'vacant']) }}" class="room-filter-link {{ $roomStatusFilter === 'vacant' ? 'is-active' : '' }}">ว่าง</a>
                    <a href="{{ route('app.room-status', ['room_status' => 'unavailable']) }}" class="room-filter-link {{ $roomStatusFilter === 'unavailable' ? 'is-active' : '' }}">ไม่ว่าง</a>
                </div>
                @if($dashboardRooms->isNotEmpty())
                    <div class="vacant-room-grid" aria-label="Vacant room grid">
                        @foreach($dashboardRooms as $room)
                            @php($roomStatusLabel = $room->status === 'vacant' ? 'ว่าง' : ($room->status === 'maintenance' ? 'กำลังปรับปรุง' : 'ไม่ว่าง'))
                            @php($roomStatusCardClass = $room->status === 'vacant' ? 'vacant-room-card--vacant' : ($room->status === 'maintenance' ? 'vacant-room-card--maintenance' : 'vacant-room-card--occupied'))
                            @php($roomStatusBadgeClass = $room->status === 'vacant' ? '' : ($room->status === 'maintenance' ? 'vacant-room-badge--maintenance' : 'vacant-room-badge--occupied'))
                            <div class="card vacant-room-card {{ $roomStatusCardClass }} h-100">
                                <div class="card-body">
                                    <h4 class="vacant-room-number">ห้อง {{ $room->room_number }}</h4>
                                    <span class="vacant-room-badge {{ $roomStatusBadgeClass }}">{{ $roomStatusLabel }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vacant-room-empty">
                        @if($roomStatusFilter === 'vacant')
                            ไม่มีห้องว่างในขณะนี้
                        @elseif($roomStatusFilter === 'unavailable')
                            ไม่มีห้องที่ไม่ว่างในขณะนี้
                        @else
                            ยังไม่มีข้อมูลห้องในขณะนี้
                        @endif
                    </div>
                @endif
                <div class="small text-muted mt-3">
                    ใช้ตัวกรองเพื่อสลับดูห้องทั้งหมด, ห้องว่าง, หรือห้องที่ไม่ว่าง
                </div>
            </div>
        </div>
    </div>
</div>
@endsection