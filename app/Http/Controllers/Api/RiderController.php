<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $query = Rider::where('status', '!=', 'offline');

        if ($request->has(['lat', 'lng'])) {
            $lat = $request->lat;
            $lng = $request->lng;
            $radius = $request->get('radius', 5); // Default 5km

            // Haversine formula
            $query->selectRaw("id, name, bio, lat, lng, status, vehicle_type,
                (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
            [$lat, $lng, $lat]
            )
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
        }
        else {
            $query->select('id', 'name', 'bio', 'lat', 'lng', 'status', 'vehicle_type');
        }

        $riders = $query->withCount(['orders as active_orders_count' => function ($query) {
            $query->whereIn('status', ['pending', 'accepted', 'picked_up']);
        }])
            ->get();

        return response()->json($riders);
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $rider = auth()->guard('rider')->user() ?? $request->user();

        if (!$rider) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Update rider location in database
        $rider->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        // Broadcast real-time location with error handling
        try {
            broadcast(new \App\Events\RiderLocationUpdated(
                $rider->id,
                $request->lat,
                $request->lng,
                $rider->status,
                $rider->name,
                $rider->bio
            ))->toOthers();
        } catch (\Exception $e) {
            // Log the error but don't fail the request if broadcast fails
            Log::warning('Rider location broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Location updated',
            'rider_id' => $rider->id,
            'lat' => $request->lat,
            'lng' => $request->lng
        ]);
    }

    public function updateClientLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $client = auth()->guard('client')->user();

        if ($client) {
            $client->update([
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);

            // Broadcast real-time location specifically for tracking
            broadcast(new \App\Events\ClientLocationUpdated(
                $client->id,
                $request->lat,
                $request->lng
            ))->toOthers();

            return response()->json(['message' => 'Client location updated and broadcasted']);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function orderRider(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);
        $client = auth()->guard('client')->user();

        if (!$client) {
            return response()->json(['message' => 'Client not authenticated'], 401);
        }

        // Check if rider is currently busy / accommodating another user
        $riderHasActiveOrder = Order::where('rider_id', $rider->id)
            ->whereIn('status', ['pending', 'mission_accepted', 'accepted', 'picked_up'])
            ->exists();

        if ($rider->status === 'busy' || $riderHasActiveOrder) {
            return response()->json([
                'message' => 'This rider is currently accommodating another user. Please choose a different rider.',
                'reason' => 'rider_busy',
                'rider_name' => $rider->name,
            ], 409);
        }

        // Prevent booking if there's already an active mission or order
        $hasActive = Order::where('client_id', $client->id)
            ->whereIn('status', ['pending', 'mission_accepted', 'accepted', 'picked_up'])
            ->exists();

        if ($hasActive) {
            return response()->json([
                'message' => 'You already have an active request or mission. Please complete or cancel it first.'
            ], 403);
        }

        try {
            // Broadcast to Rider with route details
            broadcast(new \App\Events\RiderOrdered(
                $rider->id, 
                $client->id, 
                $client->name, 
                $request->service_type,
                $request->pickup,
                $request->dropoff
            ));
            return response()->json(['message' => 'Rider notified']);
        }
        catch (\Exception $e) {
            // Log the error but don't crash the whole request if broadcast fails
            Log::error('Broadcast failed in orderRider: ' . $e->getMessage());
            return response()->json(['message' => 'Notification service unavailable', 'error' => $e->getMessage()], 503);
        }
    }

    public function respondToClient(Request $request, $clientId)
    {
        $rider = auth()->guard('rider')->user();

        if (!$rider) {
            return response()->json(['message' => 'Rider not authenticated'], 401);
        }

        try {
            $serviceType = $request->get('service_type', 'order');
            $order = null;

            // If rider accepts, create a mission order to keep them connected
            if ($request->decision === 'accept') {
                $pickup = $request->get('pickup');
                $dropoff = $request->get('dropoff');

                $pickupAddress = 'Awaiting Formalization';
                $deliveryAddress = 'Awaiting Formalization';

                if ($serviceType === 'pahatod' && $pickup && $dropoff) {
                    $pickupAddress = is_array($pickup) ? ($pickup['name'] ?? 'Pickup') : $pickup;
                    $deliveryAddress = is_array($dropoff) ? ($dropoff['name'] ?? 'Dropoff') : $dropoff;
                }

                $order = Order::create([
                    'client_id' => $clientId,
                    'rider_id' => $rider->id,
                    'pickup_address' => $pickupAddress,
                    'delivery_address' => $deliveryAddress,
                    'status' => 'mission_accepted',
                    'service_type' => $serviceType,
                    'details' => $serviceType === 'pahatod' ? "Pahatod: {$pickupAddress} to {$deliveryAddress}" : 'Mission accepted, negotiating details...'
                ]);

                $greeting = "Hello! This is {$rider->name}. What is your order for today?";
                if ($serviceType === 'pahatod') {
                    $greeting = "Hello! This is {$rider->name}. I have accepted your Pahatod request from {$pickupAddress} to {$deliveryAddress}. I'm on my way!";
                } elseif ($serviceType === 'pasugo') {
                    $greeting = "Hello! This is {$rider->name}. I have accepted your pasugo request. Please send me the details.";
                }

                // Save message
                Message::create([
                    'sender_id' => $rider->id,
                    'receiver_id' => $clientId,
                    'message' => $greeting,
                    'sender_type' => 'rider',
                    'order_id' => $order->id
                ]);

                broadcast(new \App\Events\ChatMessage($rider->id, $clientId, $greeting, 'rider', $order->id));
            }

            broadcast(new \App\Events\RiderResponse($clientId, $rider->id, $rider->name, $request->decision, $serviceType, $order ? $order->id : null));

            return response()->json([
                'message' => 'Response sent to client',
                'order' => $order ?? null
            ]);
        }
        catch (\Exception $e) {
            Log::error('Broadcast failed in respondToClient: ' . $e->getMessage());
            return response()->json(['message' => 'Notification service unavailable', 'error' => $e->getMessage()], 503);
        }
    }

    public function updateLocationDemo(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);

        $rider->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        // Broadcast real-time location
        broadcast(new \App\Events\RiderLocationUpdated(
            $rider->id,
            $request->lat,
            $request->lng,
            $rider->status,
            $rider->name,
            $rider->bio
            ));

        return response()->json(['message' => 'Location updated and broadcasted']);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'message' => 'required|string',
            'sender_type' => 'required|in:client,rider',
            'type' => 'nullable|string',
            'location_data' => 'nullable',
            'order_id' => 'nullable'
        ]);

        $type = $request->get('type', 'text');
        $locationData = $request->get('location_data');

        // Save message to database
        $msg = Message::create([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'type' => $type,
            'location_data' => $locationData,
            'sender_type' => $request->sender_type,
            'order_id' => $request->order_id
        ]);

        broadcast(new \App\Events\ChatMessage(
            $request->sender_id,
            $request->receiver_id,
            $request->message,
            $request->sender_type,
            $request->order_id,
            $type,
            $locationData
            ));

        return response()->json(['message' => 'Message sent', 'msg' => $msg]);
    }

    public function getChatHistory(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'rider_id' => 'required',
        ]);

        $messages = Message::where(function($q) use ($request) {
            $q->where('sender_id', $request->client_id)->where('sender_type', 'client')
              ->where('receiver_id', $request->rider_id);
        })->orWhere(function($q) use ($request) {
            $q->where('sender_id', $request->rider_id)->where('sender_type', 'rider')
              ->where('receiver_id', $request->client_id);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    public function placeOrderFromChat(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'details' => 'required|string',
            'type' => 'required|string', // 'order' or 'pahatod'
            'amount' => 'required|numeric|min:0',
            'service_fee' => 'required|numeric|min:0'
        ]);

        $rider = auth()->guard('rider')->user();

        // Update the existing mission order instead of creating a new one
        $order = \App\Models\Order::where('rider_id', $rider->id)
            ->where('client_id', $request->client_id)
            ->where('status', 'mission_accepted')
            ->latest()
            ->first();

        $amount = (float) $request->amount;
        $serviceFee = (float) $request->service_fee;

        if ($order) {
            $order->update([
                'pickup_address' => $order->pickup_address === 'Awaiting Formalization' 
                    ? ($request->type === 'order' ? 'Store/Restaurant' : 'Client Location')
                    : $order->pickup_address,
                'delivery_address' => $order->delivery_address === 'Awaiting Formalization'
                    ? 'Gingoog City Destination'
                    : $order->delivery_address,
                'details' => $request->details,
                'status' => 'accepted', // Automatically accepted since rider clicked it
                'total_amount' => $amount,
                'service_fee' => $serviceFee,
                'service_type' => $request->type
            ]);
        } else {
            // Fallback for safety
            $order = \App\Models\Order::create([
                'client_id' => $request->client_id,
                'rider_id' => $rider->id,
                'pickup_address' => $request->type === 'order' ? 'Store/Restaurant' : 'Client Location',
                'delivery_address' => 'Gingoog City Destination',
                'details' => $request->details,
                'status' => 'accepted',
                'service_type' => $request->type,
                'total_amount' => $amount,
                'service_fee' => $serviceFee,
            ]);
        }

        // Update rider status to busy
        $rider->update(['status' => 'busy']);

        // Broadcast status update
        broadcast(new \App\Events\RiderLocationUpdated(
            $rider->id,
            $rider->lat,
            $rider->lng,
            'busy',
            $rider->name,
            $rider->bio
            ))->toOthers();

        // Broadcast to client that the order is formally placed
        $formattedAmount = number_format($amount, 2);
        $formattedFee = number_format($serviceFee, 2);
        $riderName = $rider->name;
        $vehicleBrand = $rider->vehicle_brand ?? 'N/A';
        $formalizedMsg = "✅ MISSION FORMALIZED!\n\nRider Name: {$riderName}\nVehicle: {$vehicleBrand}\n\nDetails: {$request->details}\nService Fee: ₱{$formattedFee}\nTotal Cost: ₱{$formattedAmount}\n\nI am now proceeding with your request. Thank you!";
        
        // Save message
        Message::create([
            'sender_id' => $rider->id,
            'receiver_id' => $request->client_id,
            'message' => $formalizedMsg,
            'sender_type' => 'rider',
            'order_id' => $order->id
        ]);

        broadcast(new \App\Events\ChatMessage(
            $rider->id,
            $request->client_id,
            $formalizedMsg,
            'rider',
            $order->id
            ));

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order
        ]);
    }
    public function cancelMission(Request $request, $orderId)
    {
        $rider = auth()->guard('rider')->user();

        if (!$rider) {
            return response()->json(['message' => 'Rider not authenticated'], 401);
        }

        try {
            $order = Order::where('id', $orderId)
                ->where('rider_id', $rider->id)
                ->whereIn('status', ['mission_accepted', 'accepted', 'picked_up'])
                ->first();

            if (!$order) {
                return response()->json(['message' => 'No active mission found to cancel'], 404);
            }

            $clientId = $order->client_id;
            $reason = $request->get('reason', 'Rider cancelled the mission');

            // Update order status to cancelled
            $order->update(['status' => 'cancelled']);

            // Set rider back to available
            $rider->update(['status' => 'available']);

            // Broadcast rider status update
            broadcast(new \App\Events\RiderLocationUpdated(
                $rider->id,
                $rider->lat,
                $rider->lng,
                'available',
                $rider->name,
                $rider->bio
            ))->toOthers();

            // Send a cancellation message in chat
            $cancelMsg = "❌ MISSION CANCELLED\n\nThe rider has cancelled this mission.\nReason: {$reason}\n\nYou can request a new rider from the map.";
            
            Message::create([
                'sender_id' => $rider->id,
                'receiver_id' => $clientId,
                'message' => $cancelMsg,
                'sender_type' => 'rider',
                'order_id' => $order->id
            ]);

            broadcast(new \App\Events\ChatMessage(
                $rider->id,
                $clientId,
                $cancelMsg,
                'rider',
                $order->id
            ));

            // Broadcast cancellation event to client
            broadcast(new \App\Events\MissionCancelled(
                $clientId,
                $rider->id,
                $rider->name,
                $order->id,
                $reason
            ));

            return response()->json([
                'message' => 'Mission cancelled successfully',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Cancel mission failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel mission', 'error' => $e->getMessage()], 500);
        }
    }

    public function cancelRequest(Request $request, $id)
    {
        $clientId = auth()->guard('client')->id();
        broadcast(new \App\Events\RiderRequestCancelled($id, $clientId));
        return response()->json(['message' => 'Request cancelled']);
    }
}
