<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PersistentLogin;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    /**
     * Create a persistent login token for mobile app.
     */
    public function create(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:client,rider',
            'user_id' => 'required|integer',
            'device_id' => 'nullable|string|max:128',
            'device_name' => 'nullable|string|max:255',
            'remember' => 'boolean',
        ]);

        $user = null;
        if ($request->user_type === 'client') {
            $user = Client::find($request->user_id);
        } else {
            $user = Rider::find($request->user_id);
        }

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Generate a secure random token
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        // Calculate expiration (30 days if remember me, 1 day otherwise)
        $expiresAt = $request->boolean('remember') 
            ? now()->addDays(30) 
            : now()->addDay();

        // Delete any existing tokens for this device/user combination
        PersistentLogin::where('user_type', $request->user_type)
            ->where('user_id', $request->user_id)
            ->where('device_id', $request->device_id)
            ->delete();

        // Create persistent login record
        $persistentLogin = PersistentLogin::create([
            'user_type' => $request->user_type,
            'user_id' => $request->user_id,
            'token_hash' => $tokenHash,
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'pin_enabled' => false,
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'token' => $plainToken, // Send plain token to client (stored securely on device)
            'expires_at' => $expiresAt,
            'persistent_login_id' => $persistentLogin->id,
        ]);
    }

    /**
     * Validate a persistent token and return user info.
     */
    public function validate(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'device_id' => 'nullable|string|max:128',
        ]);

        $tokenHash = hash('sha256', $request->token);

        $persistentLogin = PersistentLogin::valid()
            ->byToken($tokenHash)
            ->first();

        if (!$persistentLogin) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Update last used
        $persistentLogin->touchLastUsed();

        // Extend expiration if it's close to expiring (within 7 days)
        if ($persistentLogin->expires_at->diffInDays(now()) < 7) {
            $persistentLogin->update(['expires_at' => now()->addDays(30)]);
        }

        $user = $persistentLogin->user;

        return response()->json([
            'valid' => true,
            'user_type' => $persistentLogin->user_type,
            'user' => $user,
            'pin_enabled' => $persistentLogin->pin_enabled,
            'persistent_login_id' => $persistentLogin->id,
        ]);
    }

    /**
     * Refresh an existing token.
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $tokenHash = hash('sha256', $request->token);

        $persistentLogin = PersistentLogin::valid()
            ->byToken($tokenHash)
            ->first();

        if (!$persistentLogin) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Generate new token
        $newPlainToken = Str::random(64);
        $newTokenHash = hash('sha256', $newPlainToken);

        // Update with new token
        $persistentLogin->update([
            'token_hash' => $newTokenHash,
            'expires_at' => now()->addDays(30),
            'last_used_at' => now(),
        ]);

        return response()->json([
            'token' => $newPlainToken,
            'expires_at' => $persistentLogin->expires_at,
        ]);
    }

    /**
     * Revoke/delete a persistent login token.
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $tokenHash = hash('sha256', $request->token);

        PersistentLogin::byToken($tokenHash)->delete();

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }

    /**
     * Setup PIN for a persistent login.
     */
    public function setupPin(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'pin' => 'required|string|size:4', // 4-digit PIN
        ]);

        $tokenHash = hash('sha256', $request->token);

        $persistentLogin = PersistentLogin::valid()
            ->byToken($tokenHash)
            ->first();

        if (!$persistentLogin) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Hash the PIN
        $pinHash = Hash::make($request->pin);

        $persistentLogin->update([
            'pin_hash' => $pinHash,
            'pin_enabled' => true,
        ]);

        return response()->json([
            'message' => 'PIN setup successfully',
            'pin_enabled' => true,
        ]);
    }

    /**
     * Verify PIN for a persistent login.
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'pin' => 'required|string|size:4',
        ]);

        $tokenHash = hash('sha256', $request->token);

        $persistentLogin = PersistentLogin::valid()
            ->byToken($tokenHash)
            ->first();

        if (!$persistentLogin || !$persistentLogin->pin_enabled) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token or PIN not enabled',
            ], 401);
        }

        // Verify PIN
        if (!Hash::check($request->pin, $persistentLogin->pin_hash)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid PIN',
            ], 401);
        }

        // Update last used
        $persistentLogin->touchLastUsed();

        $user = $persistentLogin->user;

        return response()->json([
            'valid' => true,
            'user_type' => $persistentLogin->user_type,
            'user' => $user,
        ]);
    }

    /**
     * Disable/remove PIN for a persistent login.
     */
    public function disablePin(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $tokenHash = hash('sha256', $request->token);

        $persistentLogin = PersistentLogin::valid()
            ->byToken($tokenHash)
            ->first();

        if (!$persistentLogin) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        if (!$persistentLogin->pin_enabled) {
            return response()->json(['error' => 'PIN not enabled'], 400);
        }

        // Remove PIN
        $persistentLogin->update([
            'pin_hash' => null,
            'pin_enabled' => false,
        ]);

        return response()->json([
            'message' => 'PIN disabled successfully',
            'pin_enabled' => false,
        ]);
    }
}
