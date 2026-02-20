@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[85vh] py-12 px-4 relative overflow-hidden">
    <!-- Fluid Brand Accent -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-orange-500/10 blur-[100px] rounded-full"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-red-500/10 blur-[100px] rounded-full"></div>

    <!-- Hero Title -->
    <div class="text-center mb-16 relative">
        <div class="inline-block px-4 py-1.5 bg-orange-50 text-orange-600 text-[10px] font-black uppercase tracking-[0.3em] rounded-full mb-6 border border-orange-100">Gingoog City Express</div>
        <h1 class="text-7xl font-black text-slate-900 tracking-tighter leading-none mb-6">
            Anything.<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-red-600">Pasugo.</span>
        </h1>
        <p class="text-slate-400 font-bold text-sm max-w-xs mx-auto leading-relaxed uppercase tracking-wider">The most elite on-demand delivery network in the Gingoog city.</p>
    </div>
    
    <!-- Action Cards -->
    <div class="w-full space-y-4 relative">
        <!-- Client Choice -->
        <a href="{{ route('client.login') }}" class="group block bg-white p-8 rounded-[3rem] shadow-xl border border-slate-50 transition-all active:scale-95 duration-500">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-slate-900 text-white rounded-[2rem] flex items-center justify-center text-3xl group-hover:rotate-6 transition-transform">🍔</div>
                <div class="text-left">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">I need help</h2>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Order Food or Pasugo</p>
                </div>
            </div>
        </a>

        <!-- Rider Choice -->
        <a href="{{ route('rider.login') }}" class="group block bg-white p-8 rounded-[3rem] shadow-xl border border-slate-50 transition-all active:scale-95 duration-500">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-orange-600 text-white rounded-[2rem] flex items-center justify-center text-3xl group-hover:-rotate-6 transition-transform">🏍️</div>
                <div class="text-left">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">I'm a Rider</h2>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Join the mobile elite fleet</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Stats Bar -->
    <div class="mt-20 flex justify-between w-full max-w-[280px] text-slate-300 font-black text-[9px] uppercase tracking-widest">
        <span>Fast</span>
        <span class="text-slate-200">/</span>
        <span>Secure</span>
        <span class="text-slate-200">/</span>
        <span>Always On</span>
    </div>
</div>
@endsection
