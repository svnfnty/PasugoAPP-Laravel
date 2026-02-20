<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function create()
    {
        return view('client.order.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'details' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        // In a real app, we would geocode addresses to get lat/lng
        // For this demo, we'll leave them null or mock them

        $hasActive = Order::where('client_id', Auth::guard('client')->id())
            ->whereIn('status', ['pending', 'mission_accepted', 'accepted', 'picked_up'])
            ->exists();

        if ($hasActive) {
            return back()->with('error', 'You already have an active request or mission. Please complete or cancel it first.');
        }

        $order = Order::create([
            'client_id' => Auth::guard('client')->id(),
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'details' => $request->details,
            'total_amount' => $request->total_amount,
            'status' => 'pending',
        ]);

        return redirect()->route('client.dashboard')->with('success', 'Order placed successfully!');
    }

    public function accept(Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Order is no longer available.');
        }

        $order->update([
            'rider_id' => Auth::guard('rider')->id(),
            'status' => 'accepted',
        ]);

        $rider = Auth::guard('rider')->user();
        $rider->update(['status' => 'busy']);

        // Broadcast status update
        broadcast(new \App\Events\RiderLocationUpdated(
            $rider->id, $rider->lat, $rider->lng, 'busy', $rider->name, $rider->bio
            ))->toOthers();

        return redirect()->route('rider.dashboard')->with('success', 'Order accepted!');
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Ensure the authenticated rider owns this order
        if ($order->rider_id !== Auth::guard('rider')->id()) {
            return back()->with('error', 'Unauthorized access to this order.');
        }

        $request->validate([
            'status' => 'required|in:picked_up,delivered',
            'total_amount' => 'required_if:status,picked_up|nullable|numeric|min:0',
        ]);

        $updateData = ['status' => $request->status];
        if ($request->status === 'picked_up' && $request->has('total_amount')) {
            $updateData['total_amount'] = $request->total_amount;
        }

        $order->update($updateData);

        if ($request->status === 'delivered') {
            $rider = Auth::guard('rider')->user();
            $rider->update(['status' => 'available']);

            // Broadcast status update
            broadcast(new \App\Events\RiderLocationUpdated(
                $rider->id, $rider->lat, $rider->lng, 'available', $rider->name, $rider->bio
                ))->toOthers();

            // Notify client via chat
            $completionMsg = $order->service_type === 'pahatod' 
                ? "🏁 MISSION COMPLETED!\n\nI have successfully dropped you off at your destination. Thank you for using PasugoAPP!"
                : "🏁 MISSION COMPLETED!\n\nI have successfully delivered your order. Thank you for using PasugoAPP!";
            
            Message::create([
                'sender_id' => $rider->id,
                'receiver_id' => $order->client_id,
                'message' => $completionMsg,
                'sender_type' => 'rider',
                'order_id' => $order->id
            ]);

            broadcast(new \App\Events\ChatMessage(
                $rider->id,
                $order->client_id,
                $completionMsg,
                'rider',
                $order->id
            ));
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Order status updated!', 'order' => $order]);
        }

        return redirect()->route('rider.dashboard')->with('success', 'Order status updated!');
    }
}
