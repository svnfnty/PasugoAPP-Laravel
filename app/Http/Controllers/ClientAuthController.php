<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PersistentLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->has('remember');

        if (Auth::guard('client')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $client = Auth::guard('client')->user();
            
            // If this is a mobile/API request with remember me, generate persistent token
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                $tokenData = $this->generatePersistentToken($client, 'client', $remember, $request);
                return response()->json([
                    'success' => true,
                    'redirect' => route('client.dashboard'),
                    'token' => $tokenData['token'],
                    'expires_at' => $tokenData['expires_at'],
                    'persistent_login_id' => $tokenData['persistent_login_id'],
                    'user' => $client,
                ]);
            }
            
            return redirect()->route('client.dashboard');
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
        return view('client.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'phone' => 'required|string|max:20|unique:clients',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Auth::guard('client')->login($client);

        return redirect()->route('client.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function dashboard()
    {
        $client = Auth::guard('client')->user();
        $orders = $client->orders()->latest()->get();

        $totalSpent = $client->orders()->where('status', 'delivered')->sum('total_amount');
        $orderCount = $client->orders()->count();

        // Fetch ongoing mission (persistent chat phase)
        $activeMission = \App\Models\Order::where('client_id', $client->id)
            ->whereIn('status', ['mission_accepted', 'accepted', 'picked_up'])
            ->with('rider')
            ->latest()
            ->first();

        return view('client.dashboard', compact('orders', 'totalSpent', 'orderCount', 'activeMission'));
    }
}
