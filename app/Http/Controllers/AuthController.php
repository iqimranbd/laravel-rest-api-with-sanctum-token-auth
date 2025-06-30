<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\RefreshToken;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['message' => 'No refresh token'], 401);
        }

        $hashed = hash('sha256', $refreshToken);

        $tokenRecord = RefreshToken::where('token', $hashed)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        // Issue new access token
        $accessToken = $tokenRecord->user->createToken('authToken', [], now()->addMinutes(15))->plainTextToken;

        // Rotate refresh token
        $tokenRecord->delete();
        $newRefreshToken = Str::random(60);
        $tokenRecord->user->refreshTokens()->create([
            'token' => hash('sha256', $newRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()
            ->json(['access_token' => $accessToken])
            ->cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, null, null, true, true, false, 'Strict');
    }
}
