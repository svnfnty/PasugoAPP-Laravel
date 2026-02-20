<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>@yield('title', 'PasugoAPP GINGOOG')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: #f8fafc;
            -webkit-tap-highlight-color: transparent;
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
</head>
<body class="text-slate-900 antialiased selection:bg-orange-100 selection:text-orange-900">

    <nav class="glass-nav sticky top-0 z-50 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-2xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-red-600">PasugoAPP</a>
            <div class="flex items-center gap-3">
                @if(Auth::guard('client')->check() || Auth::guard('rider')->check())
                    <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition">
                        <span class="text-xs font-black">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    </button>
                    <form action="{{ Auth::guard('client')->check() ? route('client.logout') : route('rider.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-black text-rose-500 hover:text-rose-600 uppercase tracking-widest">Exit</button>
                    </form>
                @else
                    <a href="{{ route('client.login') }}" class="text-xs font-black text-slate-600 uppercase tracking-widest">Sign In</a>
                @endif
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-6 max-w-lg">
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl mb-6 text-sm font-bold animate-bounce">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-100 text-rose-700 px-4 py-3 rounded-2xl mb-6 text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif
        
        @yield('content')
    </main>

    <footer class="mt-auto py-10 text-center text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] safe-bottom">
        &copy; {{ date('Y') }} PasugoAPP GINGOOG - Fast & Reliable
    </footer>
</body>
</html>
