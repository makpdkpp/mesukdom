<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? 'MesukDom ช่วยเจ้าของหอจัดการห้อง ผู้เช่า สัญญา บิล และการแจ้งเตือนในระบบเดียว' }}">
    <title>{{ $title ?? 'MesukDom' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --brand-ink: #0f172a;
            --brand-muted: #475569;
            --brand-sand: #fff9f1;
            --brand-surface: rgba(255, 255, 255, 0.82);
            --brand-line: rgba(148, 163, 184, 0.22);
            --brand-accent: #d97706;
            --brand-accent-deep: #b45309;
            --brand-teal: #0f766e;
            --brand-rose: #be123c;
        }

        body {
            font-family: 'Manrope', sans-serif;
            color: var(--brand-ink);
            background:
                radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(15, 118, 110, 0.14), transparent 26%),
                linear-gradient(180deg, #fffdf8 0%, #fff7ed 55%, #fffaf5 100%);
        }

        h1, h2, h3, .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }

        .glass-panel {
            background: var(--brand-surface);
            backdrop-filter: blur(18px);
            border: 1px solid var(--brand-line);
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.08);
        }

        .mesh-card {
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(255, 247, 237, 0.96)),
                radial-gradient(circle at top right, rgba(217, 119, 6, 0.12), transparent 35%);
            border: 1px solid rgba(217, 119, 6, 0.12);
            box-shadow: 0 18px 60px rgba(148, 163, 184, 0.16);
        }

        .signal-dot {
            position: absolute;
            border-radius: 9999px;
            filter: blur(2px);
            opacity: 0.55;
        }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="signal-dot h-40 w-40 bg-amber-200/70 left-0 top-12 -translate-x-1/3"></div>
    <div class="signal-dot h-64 w-64 bg-teal-200/60 right-0 top-80 translate-x-1/3"></div>

    <header class="sticky top-0 z-30 px-4 py-4 sm:px-6 lg:px-8">
        <div class="glass-panel mx-auto flex max-w-7xl items-center justify-between rounded-full px-5 py-3 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 text-sm font-extrabold text-white shadow-lg shadow-amber-500/30">MD</span>
                <span>
                    <span class="font-display block text-base font-bold tracking-tight text-slate-950">MesukDom</span>
                    <span class="block text-xs font-medium text-slate-500">Dormitory SaaS Platform</span>
                </span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-600 md:flex">
                <a href="{{ route('landing') }}#features" class="transition hover:text-slate-950">Features</a>
                <a href="{{ route('landing') }}#workflow" class="transition hover:text-slate-950">Workflow</a>
                <a href="{{ route('pricing') }}" class="transition hover:text-slate-950">Pricing</a>
                <a href="{{ route('landing') }}#faq" class="transition hover:text-slate-950">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-slate-600 transition hover:text-slate-950 sm:inline">Login</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">เริ่มใช้งาน</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="px-4 pb-10 pt-16 sm:px-6 lg:px-8">
        <div class="glass-panel mx-auto flex max-w-7xl flex-col gap-6 rounded-[2rem] px-6 py-8 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="font-display text-xl font-bold text-slate-950">MesukDom</p>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">Public site สำหรับแนะนำระบบ จัดการ onboarding ของเจ้าของหอ และเชื่อมต่อสู่ user portal ได้ใน flow เดียว</p>
            </div>
            <div class="flex flex-wrap gap-4 text-sm font-semibold text-slate-600">
                <a href="{{ route('pricing') }}" class="transition hover:text-slate-950">Pricing</a>
                <a href="{{ route('register') }}" class="transition hover:text-slate-950">Sign up</a>
                <a href="{{ route('login') }}" class="transition hover:text-slate-950">Login</a>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="transition hover:text-slate-950">Forgot Password</a>
                @endif
            </div>
        </div>
    </footer>
</body>
</html>