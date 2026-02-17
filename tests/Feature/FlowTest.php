<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Rider;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_delivery_flow()
    {
        // 1. Register Client
        $response = $this->post('/client/register', [
            'name' => 'John Client',
            'email' => 'client@example.com',
            'phone' => '1234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertRedirect(route('client.dashboard'));
        $this->assertDatabaseHas('clients', ['email' => 'client@example.com']);

        // 2. Register Rider
        $response = $this->post('/rider/register', [
            'name' => 'Jane Rider',
            'email' => 'rider@example.com',
            'phone' => '0987654321',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => 'bike',
        ]);
        $response->assertRedirect(route('rider.dashboard'));
        $this->assertDatabaseHas('riders', ['email' => 'rider@example.com']);

        // 3. Client Places Order
        $client = Client::where('email', 'client@example.com')->first();
        $this->actingAs($client, 'client');

        $response = $this->post('/client/order', [
            'pickup_address' => 'Test Restaurant',
            'delivery_address' => 'Test Home',
            'details' => '2 Burgers',
            'total_amount' => 25.50,
        ]);
        $response->assertRedirect(route('client.dashboard'));

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);

        // 4. Rider Accepts Order
        $rider = Rider::where('email', 'rider@example.com')->first();
        $this->actingAs($rider, 'rider');

        $response = $this->post("/rider/order/{$order->id}/accept");
        $response->assertRedirect(route('rider.dashboard'));

        $order->refresh();
        $this->assertEquals('accepted', $order->status);
        $this->assertEquals($rider->id, $order->rider_id);

        // 5. Rider Updates to Picked Up
        $response = $this->patch("/rider/order/{$order->id}/status", [
            'status' => 'picked_up',
        ]);
        $response->assertRedirect(route('rider.dashboard'));

        $order->refresh();
        $this->assertEquals('picked_up', $order->status);

        // 6. Rider Updates to Delivered
        $response = $this->patch("/rider/order/{$order->id}/status", [
            'status' => 'delivered',
        ]);
        $response->assertRedirect(route('rider.dashboard'));

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
    }
}
