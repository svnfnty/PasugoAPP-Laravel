@extends('layouts.app')

@section('content')
<style>
    .client-card { @apply bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 mb-4; }
    .client-stat-pill { @apply flex-1 p-5 rounded-3xl text-center border border-slate-100; }
    .order-item { @apply flex justify-between items-center py-4 border-b border-slate-50 last:border-none; }
</style>

<!-- Header Section -->
<div class="mb-10 px-2">
    <h1 class="text-3xl font-black tracking-tighter text-slate-900 leading-tight">My Profile</h1>
    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">Verified PasugoAPP Client</p>
</div>

<!-- Quick Stats -->
<div class="flex gap-3 mb-8">
    <div class="client-stat-pill bg-orange-50 border-orange-100">
        <div class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">Spent</div>
        <div class="text-xl font-black tracking-tighter text-orange-600">₱{{ number_format($totalSpent, 0) }}</div>
    </div>
    <div class="client-stat-pill bg-white">
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Orders</div>
        <div class="text-xl font-black tracking-tighter text-slate-800">{{ $orderCount }}</div>
    </div>
</div>

<!-- Main Action -->
<div class="mb-12">
    <a href="{{ route('client.riders.map') }}" class="block w-full group relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 text-center shadow-2xl transition-all active:scale-[0.98]">
        <div class="absolute inset-0 bg-gradient-to-tr from-orange-600/20 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative z-10">
            <div class="text-4xl mb-4">🚀</div>
            <h2 class="text-xl font-black text-white tracking-tight">Express PasugoAPP</h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">Choose a rider & start ordering</p>
        </div>
    </a>
</div>

<!-- Recent Orders -->
<div class="mb-10">
    <div class="flex items-center justify-between mb-6 px-2">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Recent History</h2>
        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full uppercase">Live Updates</span>
    </div>

    @forelse($orders as $order)
        <div class="client-card">
            <div class="flex justify-between items-start mb-4">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-300 uppercase">#{{ $order->id }} • {{ $order->created_at->format('M d') }}</span>
                    <h3 class="font-black text-slate-900 text-lg mt-1 truncate max-w-[200px]">{{ $order->details ?: 'Express PasugoAPP' }}</h3>
                </div>
                <div class="text-right">
                    <div class="font-black text-slate-900 tracking-tighter">₱{{ number_format($order->total_amount, 0) }}</div>
                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full mt-1 inline-block
                        @if($order->status == 'delivered') bg-emerald-100 text-emerald-700 
                        @elseif($order->status == 'cancelled') bg-rose-100 text-rose-700 
                        @else bg-blue-100 text-blue-700 @endif">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-3 pt-4 border-t border-slate-50">
                <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                <p class="text-[11px] font-bold text-slate-400 truncate">{{ $order->pickup_address }}</p>
                <span class="text-slate-200">→</span>
                <p class="text-[11px] font-black text-slate-900 truncate">{{ $order->delivery_address }}</p>
            </div>
        </div>
    @empty
        <div class="p-16 text-center bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <div class="text-5xl mb-6">🏜️</div>
            <h3 class="text-xl font-black text-slate-900 mb-2">History is empty</h3>
            <p class="text-slate-400 text-sm font-medium mb-8 leading-relaxed px-4">Ready to experience the fastest delivery in Gingoog City?</p>
            <a href="{{ route('client.riders.map') }}" class="inline-block bg-slate-100 text-slate-900 font-black py-4 px-8 rounded-3xl text-xs uppercase tracking-widest hover:bg-slate-200 transition">
                Start Exploring
            </a>
        </div>
    @endforelse
</div>
@endsection
