<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing • MesukDom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="{{ route('landing') }}">MesukDom</a>
            <div>
                @auth
                    <a class="btn btn-outline-primary" href="{{ route('app.dashboard') }}">Dashboard</a>
                @else
                    <a class="btn btn-outline-secondary mr-2" href="{{ route('login') }}">Login</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Sign up</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="h3 mb-2">Pricing Plans</h1>
            <p class="text-muted mb-0">เลือกแพ็กเกจที่เหมาะกับหอพักของคุณ (เตรียมพร้อมสำหรับการเชื่อมต่อระบบชำระเงินในอนาคต)</p>
        </div>

        <div class="row">
            @forelse($plans as $plan)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">{{ $plan->name }}</h5>
                            <div class="display-4" style="font-size: 2rem; font-weight: 700;">
                                {{ number_format((float) $plan->price_monthly, 0) }}
                                <small class="text-muted" style="font-size: 1rem;">/เดือน</small>
                            </div>
                            @if($plan->description)
                                <p class="text-muted mt-2">{{ $plan->description }}</p>
                            @endif

                            @php($limits = (array) ($plan->limits ?? []))
                            @if(count($limits))
                                <ul class="list-unstyled mt-3 mb-0">
                                    @foreach($limits as $key => $value)
                                        <li class="mb-1"><span class="text-muted">{{ ucfirst((string) $key) }}:</span> <strong>{{ $value }}</strong></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-0">
                            @guest
                                <a href="{{ route('register', ['plan' => $plan->id]) }}" class="btn btn-primary btn-block">เลือกแพ็กเกจนี้</a>
                            @else
                                <a href="{{ route('app.dashboard') }}" class="btn btn-outline-primary btn-block">ไปที่ Dashboard</a>
                            @endguest
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning">ยังไม่มีแพ็กเกจในระบบ</div>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-4 text-muted">
            *ราคายังเป็นตัวอย่าง และยังไม่เชื่อมต่อการชำระเงินจริง
        </div>
    </div>
</body>
</html>
