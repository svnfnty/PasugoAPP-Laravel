<?php

namespace App\Http\Controllers;

use App\Models\Rider;
use App\Models\Order; // Needed for dashboard
use App\Models\PersistentLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // For distance calculation if needed raw
use Illuminate\Support\Str;

class RiderAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('rider.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->has('remember');

        if (Auth::guard('rider')->attempt($credentials, $remember)) {
            $rider = Auth::guard('rider')->user();
            $rider->update(['status' => 'available']);

            $request->session()->regenerate();
            
            // If this is a mobile/API request with remember me, generate persistent token
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                $tokenData = $this->generatePersistentToken($rider, 'rider', $remember, $request);
                return response()->json([
                    'success' => true,
                    'redirect' => route('rider.dashboard'),
                    'token' => $tokenData['token'],
                    'expires_at' => $tokenData['expires_at'],
                    'persistent_login_id' => $tokenData['persistent_login_id'],
                    'user' => $rider,
                ]);
            }
            
            return redirect()->route('rider.dashboard');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Generate a persistent token for mobile app session persistence.
     */
    private function generatePersistentToken($user, string $userType, bool $remember, Request $request): array
    {
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $expiresAt = $remember 
            ? now()->addDays(30) 
            : now()->addDay();

        // Delete existing tokens for this device
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-ID') ?? 'unknown';
        
        PersistentLogin::where('user_type', $userType)
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->delete();

        $persistentLogin = PersistentLogin::create([
            'user_type' => $userType,
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'device_id' => $deviceId,
            'device_name' => $request->input('device_name') ?? $request->header('X-Device-Name') ?? 'Unknown Device',
            'pin_enabled' => false,
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $plainToken,
            'expires_at' => $expiresAt,
            'persistent_login_id' => $persistentLogin->id,
        ];
    }

    public function showRegisterForm()
    {
        return view('rider.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:riders',
            'phone' => 'required|string|max:20|unique:riders',
            'password' => 'required|string|min:8|confirmed',
            'vehicle_type' => 'required|string',
            'vehicle_brand' => 'required|string|max:255',
        ]);

        $rider = Rider::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'vehicle_type' => $request->vehicle_type,
            'vehicle_brand' => $request->vehicle_brand,
            'status' => 'available', // Default to available
        ]);

        Auth::guard('rider')->login($rider);

        return redirect()->route('rider.dashboard');
    }

    public function logout(Request $request)
    {
        $rider = Auth::guard('rider')->user();
        if ($rider) {
            $rider->update(['status' => 'offline']);

            // Broadcast offline status immediately
            broadcast(new \App\Events\RiderLocationUpdated(
                $rider->id, $rider->lat, $rider->lng, 'offline', $rider->name, $rider->bio
                ))->toOthers();
        }

        Auth::guard('rider')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function dashboard()
    {
        $rider = Auth::guard('rider')->user();

        // Find available orders (pending status)
        // In a real app, we would filter by location here.
        // For now, let's just show all pending orders to simulate "nearby"
        $availableOrders = Order::where('status', 'pending')->latest()->get();

        // Also show active orders for this rider (accepted/picked_up)
        $myActiveOrders = Order::where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picked_up'])
            ->get();

        // Fetch ongoing missions (persistent chat phase)
        $myMissions = Order::where('rider_id', $rider->id)
            ->whereIn('status', ['mission_accepted', 'accepted', 'picked_up'])
            ->with('client')
            ->get();

        // Financial Stats
        $totalIncome = Order::where('rider_id', $rider->id)->where('status', 'delivered')->sum('total_amount');
        $totalOrders = Order::where('rider_id', $rider->id)->where('status', 'delivered')->count();
        $netIncome = $totalIncome * 0.9; // Simulation: 10% app fee

        return view('rider.dashboard', compact('rider', 'availableOrders', 'myActiveOrders', 'myMissions', 'totalIncome', 'totalOrders', 'netIncome'));
    }
}
