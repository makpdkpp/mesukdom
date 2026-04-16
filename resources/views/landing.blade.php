<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MesukDom • Dormitory SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="jumbotron jumbotron-fluid bg-white mb-0">
        <div class="container">
            <h1 class="display-4">MesukDom Dormitory SaaS</h1>
            <p class="lead">ระบบบริหารหอพักแบบ Multi-Tenant พร้อมห้องพัก ผู้เช่า สัญญา บิล การชำระเงิน และ LINE OA แจ้งเตือน</p>
            <a href="{{ route('pricing') }}" class="btn btn-primary btn-lg mr-2">ดูราคา</a>
            @auth
                <a href="{{ route('app.dashboard') }}" class="btn btn-outline-primary btn-lg mr-2">Open Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg mr-2">สมัครใช้งาน</a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">เข้าสู่ระบบ</a>
            @endauth
        </div>
    </div>

    <div class="container py-5">
        <div class="row text-center">
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body"><h5>Room Management</h5><p>เพิ่ม แก้ไข และติดตามสถานะห้องพัก</p></div></div></div>
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body"><h5>Billing & Payments</h5><p>ออกบิลค่าเช่าและบันทึกการชำระเงินได้ทันที</p></div></div></div>
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body"><h5>LINE OA</h5><p>รองรับ webhook และการส่งแจ้งเตือนบิลไปยังผู้เช่า</p></div></div></div>
        </div>
    </div>
</body>
</html>
