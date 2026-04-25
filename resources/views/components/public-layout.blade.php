@props([
    'authModals' => false,
])

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? 'MesukDorm ช่วยเจ้าของหอจัดการห้อง ผู้เช่า สัญญา บิล และการแจ้งเตือนในระบบเดียว' }}">
    <title>{{ $title ?? 'MesukDorm' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    {!! \App\Support\ViteAssets::render(['resources/css/app.css', 'resources/js/app.js']) !!}

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
            --brand-gold: #f59e0b;
            --brand-shadow: rgba(15, 23, 42, 0.14);
        }

        body {
            font-family: 'Manrope', sans-serif;
            color: var(--brand-ink);
            background:
                radial-gradient(circle at center top, rgba(255, 255, 255, 0.88), transparent 26%),
                radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(15, 118, 110, 0.14), transparent 26%),
                radial-gradient(circle at bottom right, rgba(148, 163, 184, 0.18), transparent 30%),
                linear-gradient(180deg, #fffcf7 0%, #fff6ea 42%, #f6f7fb 100%);
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

        .premium-panel {
            background:
                linear-gradient(160deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.88)),
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.16), transparent 32%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 28px 100px var(--brand-shadow);
        }

        .ambient-ring {
            position: absolute;
            inset: auto;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            opacity: 0.6;
            pointer-events: none;
        }

        @media (prefers-reduced-motion: no-preference) {
            .reveal-up,
            .reveal-soft {
                opacity: 0;
                animation-fill-mode: forwards;
                animation-duration: 0.86s;
                animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
                animation-delay: var(--enter-delay, 0s);
            }

            .reveal-up {
                animation-name: reveal-up;
            }

            .reveal-soft {
                animation-name: reveal-soft;
            }

            .float-glow {
                animation: float-glow 8s ease-in-out infinite;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal-up,
            .reveal-soft,
            .float-glow {
                animation: none;
                opacity: 1;
            }
        }

        @keyframes reveal-up {
            from {
                opacity: 0;
                transform: translateY(26px) scale(0.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes reveal-soft {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float-glow {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
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
                    <span class="font-display block text-base font-bold tracking-tight text-slate-950">MesukDorm</span>
                    <span class="block text-xs font-medium text-slate-500">Dormitory SaaS Platform</span>
                </span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-600 md:flex">
                <a href="{{ route('landing') }}#features" class="transition hover:text-slate-950">ฟีเจอร์</a>
                <a href="{{ route('landing') }}#fit" class="transition hover:text-slate-950">เหมาะกับใคร</a>
                <a href="{{ route('landing') }}#pricing" class="transition hover:text-slate-950">แพ็กเกจและราคา</a>
                <a href="{{ route('landing') }}#faq" class="transition hover:text-slate-950">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">Dashboard</a>
                @else
                    @if($authModals)
                        <button type="button" data-open-auth-modal="login" class="hidden text-sm font-semibold text-slate-600 transition hover:text-slate-950 sm:inline">Login</button>
                        <button type="button" data-open-auth-modal="signup" class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">เริ่มใช้งาน</button>
                    @else
                        <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-slate-600 transition hover:text-slate-950 sm:inline">Login</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">เริ่มใช้งาน</a>
                    @endif
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
                <p class="font-display text-xl font-bold text-slate-950">MesukDorm</p>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">ระบบบริหารจัดการหอพักและอพาร์ตเมนต์ครบวงจร จัดการห้อง ผู้เช่า บิล สัญญา และแจ้งซ่อมในที่เดียว</p>
            </div>
            <div class="flex flex-wrap gap-4 text-sm font-semibold text-slate-600">
                <a href="{{ route('landing') }}#features" class="transition hover:text-slate-950">ฟีเจอร์</a>
                <a href="{{ route('landing') }}#fit" class="transition hover:text-slate-950">เหมาะกับใคร</a>
                <a href="{{ route('landing') }}#pricing" class="transition hover:text-slate-950">แพ็กเกจและราคา</a>
                <a href="{{ route('landing') }}#faq" class="transition hover:text-slate-950">FAQ</a>
                @guest
                    @if($authModals)
                        <button type="button" data-open-auth-modal="signup" class="transition hover:text-slate-950">สมัครใช้งาน</button>
                        <button type="button" data-open-auth-modal="login" class="transition hover:text-slate-950">เข้าสู่ระบบ</button>
                    @else
                        <a href="{{ route('register') }}" class="transition hover:text-slate-950">สมัครใช้งาน</a>
                        <a href="{{ route('login') }}" class="transition hover:text-slate-950">เข้าสู่ระบบ</a>
                    @endif
                @endguest
            </div>
        </div>
    </footer>
</body>
</html>