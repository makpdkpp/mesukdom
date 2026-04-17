@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Broadcast Message</h3></div>
            <form method="POST" action="{{ route('app.broadcasts.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Scope</label>
                        <select name="scope" class="form-control">
                            <option value="all">ทั้งหอ</option>
                            <option value="building">เฉพาะตึก</option>
                            <option value="floor">เฉพาะชั้น</option>
                            <option value="room">เฉพาะห้อง</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Building</label>
                        <select name="building" class="form-control">
                            <option value="">ทุกตึก / ไม่ระบุ</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building }}">{{ $building }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">ใช้เมื่อเลือก scope เป็นตึก หรือใช้ร่วมกับ scope ชั้นเพื่อระบุชั้นในตึกนั้น</small>
                    </div>
                    <div class="form-group">
                        <label>Floor</label>
                        <input name="floor" type="number" min="1" class="form-control" placeholder="เช่น 2">
                    </div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">เลือกห้อง</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->building }} / ชั้น {{ $room->floor }} / ห้อง {{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Message</label>
                        <textarea name="message" rows="5" class="form-control" placeholder="พิมพ์ข้อความที่ต้องการ broadcast" required>{{ old('message') }}</textarea>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Send Broadcast</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Broadcast History</h3>
                <span class="text-sm text-muted">เก็บประวัติข้อความที่ส่งผ่าน LINE OA พร้อม segment ที่ใช้</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                    <tr>
                        <th>Sent At</th>
                        <th>Scope</th>
                        <th>Segment</th>
                        <th>Recipients</th>
                        <th>Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($broadcasts as $broadcast)
                        <tr>
                            <td>{{ optional($broadcast->sent_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td>{{ ucfirst($broadcast->scope) }}</td>
                            <td>
                                @if($broadcast->scope === 'building')
                                    {{ $broadcast->target_building ?: '-' }}
                                @elseif($broadcast->scope === 'floor')
                                    {{ $broadcast->target_building ? $broadcast->target_building.' / ' : '' }}ชั้น {{ $broadcast->target_floor ?: '-' }}
                                @elseif($broadcast->scope === 'room')
                                    {{ $broadcast->room?->room_number ?? '-' }}
                                @else
                                    ทั้งหอ
                                @endif
                            </td>
                            <td>{{ $broadcast->recipient_count }}</td>
                            <td style="white-space:normal; min-width:260px;">{{ $broadcast->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No broadcasts sent yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
