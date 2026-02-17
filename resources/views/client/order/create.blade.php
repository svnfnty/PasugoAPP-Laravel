@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-center mb-6">Place New Order</h2>
    
    <form method="POST" action="{{ route('client.order.store') }}">
        @csrf
        
        <div class="mb-4">
            <label for="pickup_address" class="block text-gray-700 font-bold mb-2">Pickup Address (Restaurant Name/Location)</label>
            <input type="text" name="pickup_address" id="pickup_address" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="e.g. Burger King, Main St" required>
            @error('pickup_address')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="delivery_address" class="block text-gray-700 font-bold mb-2">Delivery Address (Your Location)</label>
            <input type="text" name="delivery_address" id="delivery_address" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="e.g. 123 Home Ave" required>
            @error('delivery_address')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="details" class="block text-gray-700 font-bold mb-2">Order Details / Items</label>
            <textarea name="details" id="details" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="e.g. 2x Whopper Meal, No onions"></textarea>
            @error('details')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="total_amount" class="block text-gray-700 font-bold mb-2">Estimated Amount ($)</label>
            <input type="number" step="0.01" name="total_amount" id="total_amount" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="0.00" required>
            @error('total_amount')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex items-center justify-between">
            <a href="{{ route('client.dashboard') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
            <button type="submit" class="bg-orange-600 text-white font-bold py-2 px-6 rounded hover:bg-orange-700 transition">
                Place Order
            </button>
        </div>
    </form>
</div>
@endsection
