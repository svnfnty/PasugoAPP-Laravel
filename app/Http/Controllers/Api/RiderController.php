<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
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

        if ($rider) {
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
                ))->toOthers();

            return response()->json(['message' => 'Location updated and broadcasted']);
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

        try {
            // Broadcast to Rider
            broadcast(new \App\Events\RiderOrdered($rider->id, $client->id, $client->name, $request->service_type));
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
            broadcast(new \App\Events\RiderResponse($clientId, $rider->id, $rider->name, $request->decision, $serviceType));

            // If rider accepts an 'order', send an automatic greeting
            if ($request->decision === 'accept') {
                $greeting = "Hello! This is {$rider->name}. What is your order for today?";
                if ($serviceType !== 'order') {
                    $greeting = "Hello! This is {$rider->name}. I have accepted your pasugo request. Please send me the details.";
                }

                broadcast(new \App\Events\ChatMessage($rider->id, $clientId, $greeting, 'rider'));
            }

            return response()->json(['message' => 'Response sent to client']);
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
        ]);

        broadcast(new \App\Events\ChatMessage(
            $request->sender_id,
            $request->receiver_id,
            $request->message,
            $request->sender_type
            ));

        return response()->json(['message' => 'Message sent']);
    }

    public function placeOrderFromChat(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'details' => 'required|string',
            'type' => 'required|string' // 'order' or 'pahatod'
        ]);

        $rider = auth()->guard('rider')->user();

        $order = \App\Models\Order::create([
            'client_id' => $request->client_id,
            'rider_id' => $rider->id,
            'pickup_address' => $request->type === 'order' ? 'Store/Restaurant' : 'Client Location',
            'delivery_address' => 'Gingoog City Destination',
            'details' => $request->details,
            'status' => 'accepted', // Automatically accepted since rider clicked it
            'total_amount' => 0, // Simplified for demo
        ]);

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
        broadcast(new \App\Events\ChatMessage(
            $rider->id,
            $request->client_id,
            "✅ I have formally placed your order: " . $request->details,
            'rider'
            ));

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order
        ]);
    }
    public function cancelRequest(Request $request, $id)
    {
        $clientId = auth()->guard('client')->id();
        broadcast(new \App\Events\RiderRequestCancelled($id, $clientId));
        return response()->json(['message' => 'Request cancelled']);
    }
}
