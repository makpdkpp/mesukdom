@extends('layouts.adminlte')

@section('content')
@php
    $lineAddFriendUrl = $currentTenant?->lineAddFriendUrl();
    $lineAddFriendQrSvg = $lineAddFriendUrl ? app(\App\Services\QrCodeService::class)->generateSvg($lineAddFriendUrl, 120) : null;
@endphp
<div class="row">
    <div class="col-lg-4">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title">Add Resident</h3></div>
            <form method="POST" action="{{ route('app.customers.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
                    <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
                    <div class="form-group"><label>LINE ID</label><input name="line_id" class="form-control" value="{{ old('line_id') }}"></div>
                    <div class="form-group"><label>ID Card</label><input name="id_card" class="form-control" value="{{ old('id_card') }}"></div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">-- Select room --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected((string) old('room_id') === (string) $room->id)>{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-success">Save Resident</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Residents</h3>
                <span class="text-sm text-muted">Edit residents, remove records, and review rental history</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Room</th><th>Rental History</th><th>LINE</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($customers as $customer)
                        @php($contracts = $customer->contracts->sortByDesc('start_date')->values())
                        @php($activeLineLink = $customer->lineLinks->first(fn ($link) => is_null($link->used_at) && optional($link->expired_at)->isFuture()))
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->room->room_number ?? '-' }}</td>
                            <td>
                                @if($contracts->isNotEmpty())
                                    <div class="font-weight-bold">{{ $contracts->first()->room->room_number ?? '-' }} • {{ ucfirst($contracts->first()->status) }}</div>
                                    <div class="text-muted small">{{ $contracts->count() }} contract(s) • {{ $contracts->first()->start_date->format('d/m/Y') }} - {{ $contracts->first()->end_date->format('d/m/Y') }}</div>
                                @else
                                    <span class="text-muted">No contract history</span>
                                @endif
                            </td>
                            <td>
                                @if($customer->line_user_id)
                                    <span class="badge badge-success">Linked</span>
                                    <div class="text-muted small">{{ $customer->line_id ?: $customer->line_user_id }}</div>
                                @else
                                    <span class="badge badge-secondary">Not linked</span>
                                    @if($activeLineLink)
                                        <div class="mt-1">
                                            <span class="badge badge-warning px-2 py-1" style="font-family:monospace;font-size:13px;letter-spacing:.16em;">{{ $activeLineLink->link_token }}</span>
                                        </div>
                                    @endif
                                    @if($customer->line_id)
                                        <div class="text-muted small">{{ $customer->line_id }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary" type="button" data-toggle="collapse" data-target="#customer-edit-{{ $customer->id }}" aria-expanded="false" aria-controls="customer-edit-{{ $customer->id }}">Edit</button>
                                <form method="POST" action="{{ route('app.customers.destroy', $customer) }}" class="d-inline" onsubmit="return confirm('Delete resident {{ $customer->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="customer-edit-{{ $customer->id }}">
                            <td colspan="7" class="p-0">
                                {{-- Edit form --}}
                                <form method="POST" action="{{ route('app.customers.update', $customer) }}" class="p-3 pb-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Name</label>
                                            <input name="name" class="form-control form-control-sm" value="{{ $customer->name }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Phone</label>
                                            <input name="phone" class="form-control form-control-sm" value="{{ $customer->phone }}">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Email</label>
                                            <input name="email" type="email" class="form-control form-control-sm" value="{{ $customer->email }}">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">LINE ID</label>
                                            <input name="line_id" class="form-control form-control-sm" value="{{ $customer->line_id }}">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">ID Card</label>
                                            <input name="id_card" class="form-control form-control-sm" value="{{ $customer->id_card }}">
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small">Room</label>
                                            <select name="room_id" class="form-control form-control-sm">
                                                <option value="">-</option>
                                                @foreach($rooms as $room)
                                                    <option value="{{ $room->id }}" @selected((string) $customer->room_id === (string) $room->id)>{{ $room->room_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                                            <button class="btn btn-sm btn-primary btn-block">Save</button>
                                        </div>
                                    </div>
                                </form>

                                {{-- Rental history (outside edit form) --}}
                                @if($contracts->isNotEmpty())
                                    <div class="px-3 pb-2 border-top">
                                        <div class="small font-weight-bold text-muted my-2">ประวัติการเช่า</div>
                                        <div class="d-flex flex-wrap">
                                            @foreach($contracts as $contract)
                                                <span class="badge badge-light border mr-2 mb-2 p-2">
                                                    Room {{ $contract->room->room_number ?? '-' }} • {{ $contract->start_date->format('d/m/Y') }} - {{ $contract->end_date->format('d/m/Y') }} • {{ ucfirst($contract->status) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="px-3 pb-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <div class="small font-weight-bold text-muted my-2">LINE Resident Link</div>
                                            @if($customer->line_user_id)
                                                <div class="small text-success">Linked at {{ optional($customer->line_linked_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                            @else
                                                <div class="small text-muted">ยังไม่ได้เชื่อม LINE กับผู้เช่ารายนี้</div>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('app.customers.line-link.store', $customer) }}" class="mb-2">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">{{ $activeLineLink ? 'Regenerate Link Code' : 'Generate Link Code' }}</button>
                                        </form>
                                    </div>
                                    @if($activeLineLink)
                                        @php($signedLinkUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('resident.line.link.create', $activeLineLink->expired_at ?? now()->addDay(), ['tenant' => $customer->tenant_id, 'token' => $activeLineLink->link_token]))
                                        <div class="alert alert-warning border mb-0 py-3 px-3">
                                            <div class="small text-uppercase font-weight-bold text-muted">Link Code</div>
                                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap:8px;">
                                                <span class="badge badge-dark px-3 py-2" style="font-family:monospace;font-size:20px;letter-spacing:.24em;">{{ $activeLineLink->link_token }}</span>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-dark"
                                                    onclick="navigator.clipboard && navigator.clipboard.writeText('LINK {{ $activeLineLink->link_token }}')"
                                                >Copy Command</button>
                                                <a href="{{ $signedLinkUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">Open Link Portal</a>
                                                @if($lineAddFriendUrl)
                                                    <a href="{{ $lineAddFriendUrl }}" target="_blank" class="btn btn-sm btn-success"><i class="fab fa-line mr-1"></i>Add Friend</a>
                                                @endif
                                            </div>
                                            <div class="mt-2 small"><strong>Expires:</strong> {{ optional($activeLineLink->expired_at)->format('d/m/Y H:i') }}</div>
                                            <div class="mt-1 small">ให้ผู้เช่าเพิ่มเพื่อน LINE OA แล้วกดปุ่ม <strong>ยืนยันห้องพัก</strong> ในแชต จากนั้นกรอกรหัส <strong style="font-family:monospace;letter-spacing:.08em;">{{ $activeLineLink->link_token }}</strong></div>
                                            <div class="mt-1 small text-muted">ยังสามารถใช้คำสั่งสำรอง <strong style="font-family:monospace;letter-spacing:.08em;">LINK {{ $activeLineLink->link_token }}</strong> หรือเปิด Link Portal โดยตรงได้หากจำเป็น</div>

                                            @if($lineAddFriendUrl && $lineAddFriendQrSvg)
                                                <div class="mt-3 d-flex flex-wrap align-items-center" style="gap:16px;">
                                                    <div class="border rounded bg-white p-2" style="width:140px;">
                                                        {!! $lineAddFriendQrSvg !!}
                                                    </div>
                                                    <div class="small text-muted" style="max-width:280px;">
                                                        สแกน QR นี้เพื่อเพิ่มเพื่อน LINE OA ของหอ แล้วค่อยกลับมากดยืนยันห้องพักในแชต
                                                    </div>
                                                </div>
                                            @else
                                                <div class="mt-3 small text-danger">ยังไม่ได้ตั้งค่า LINE Basic ID ที่หน้า Settings จึงยังสร้าง Add Friend link/QR ให้ผู้เช่าไม่ได้</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Documents (outside edit form) --}}
                                <div class="px-3 pb-3 border-top">
                                    <div class="small font-weight-bold text-muted my-2">เอกสารแนบ</div>
                                    @if($customer->documents->isNotEmpty())
                                        <div class="mb-2">
                                            @foreach($customer->documents as $doc)
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge badge-secondary mr-2">{{ ['id_card'=>'บัตรประชาชน','profile_photo'=>'รูปถ่าย','contract'=>'สัญญา','other'=>'อื่นๆ'][$doc->document_type] ?? $doc->document_type }}</span>
                                                    @if(!str_ends_with(strtolower($doc->file_path), '.pdf'))
                                                        <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank" class="mr-2">
                                                            <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="{{ $doc->original_name }}" style="height:36px;width:36px;object-fit:cover;border-radius:3px;border:1px solid #dee2e6;">
                                                        </a>
                                                    @endif
                                                    <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank" class="small mr-2 text-truncate" style="max-width:180px">{{ $doc->original_name }}</a>
                                                    <form method="POST" action="{{ route('app.customers.documents.destroy', [$customer, $doc]) }}" class="d-inline" onsubmit="return confirm('ลบเอกสารนี้?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-xs btn-outline-danger">ลบ</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted small mb-2">ยังไม่มีเอกสาร</p>
                                    @endif

                                    {{-- Upload form --}}
                                    <form method="POST" action="{{ route('app.customers.documents.store', $customer) }}" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end" style="gap:8px">
                                        @csrf
                                        <div>
                                            <label class="small d-block">ประเภท</label>
                                            <select name="document_type" class="form-control form-control-sm" style="min-width:130px">
                                                <option value="id_card">บัตรประชาชน</option>
                                                <option value="profile_photo">รูปถ่าย</option>
                                                <option value="contract">สัญญา</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="small d-block">ไฟล์ (JPG/PNG/PDF max 5MB)</label>
                                            <input type="file" name="file" class="form-control-file form-control-sm" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary">อัปโหลด</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No residents found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
