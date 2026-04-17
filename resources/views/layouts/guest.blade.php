<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        <style>
            :root {
                --auth-ink: #0f172a;
                --auth-muted: #475569;
                --auth-line: rgba(148, 163, 184, 0.22);
                --auth-surface: rgba(255, 255, 255, 0.84);
            }

            body {
                font-family: 'Manrope', sans-serif;
                color: var(--auth-ink);
                background:
                    radial-gradient(circle at top left, rgba(245, 158, 11, 0.16), transparent 25%),
                    radial-gradient(circle at 80% 10%, rgba(15, 118, 110, 0.12), transparent 24%),
                    linear-gradient(180deg, #fffdf8 0%, #fff7ed 56%, #fffaf5 100%);
            }

            .font-display {
                font-family: 'Space Grotesk', sans-serif;
            }

            .auth-shell {
                background: var(--auth-surface);
                border: 1px solid var(--auth-line);
                box-shadow: 0 28px 80px rgba(15, 23, 42, 0.08);
                backdrop-filter: blur(20px);
            }

            .auth-aside {
                background:
                    linear-gradient(160deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.92)),
                    radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 32%);
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between rounded-full border border-white/60 bg-white/70 px-5 py-3 shadow-lg shadow-slate-200/40 backdrop-blur sm:px-6">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 text-sm font-extrabold text-white shadow-lg shadow-amber-500/30">MD</span>
                    <span>
                        <span class="font-display block text-base font-bold tracking-tight text-slate-950">MesukDorm</span>
                        <span class="block text-xs font-medium text-slate-500">Dormitory SaaS Platform</span>
                    </span>
                </a>
                <div class="hidden items-center gap-5 text-sm font-semibold text-slate-600 sm:flex">
                    <a href="{{ route('landing') }}" class="transition hover:text-slate-950">Landing</a>
                    <a href="{{ route('pricing') }}" class="transition hover:text-slate-950">Pricing</a>
                    <a href="{{ route('login') }}" class="transition hover:text-slate-950">Login</a>
                </div>
            </div>
        </div>

        <div class="text-gray-900 antialiased">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
