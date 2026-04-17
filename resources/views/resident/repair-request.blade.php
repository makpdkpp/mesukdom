<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แจ้งซ่อม</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <div class="text-muted small">LINE Resident Portal</div>
                        <h1 class="h3 mb-2">แจ้งซ่อม</h1>
                        <p class="text-muted mb-0">ห้อง {{ $customer->room?->room_number ?? '-' }} • {{ $customer->name }}</p>
                    </div>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('resident.line.repair.store', $customer) }}">
                        @csrf
                        <div class="form-group">
                            <label>หัวข้อปัญหา</label>
                            <input name="title" class="form-control" value="{{ old('title') }}" placeholder="เช่น แอร์ไม่เย็น" required>
                        </div>
                        <div class="form-group">
                            <label>รายละเอียด</label>
                            <textarea name="description" rows="5" class="form-control" placeholder="อธิบายอาการ, เวลาที่พบปัญหา, จุดที่ต้องการให้เข้าตรวจ" required>{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-primary btn-block">ส่งคำขอแจ้งซ่อม</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
