<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\OtpRequest;

class OTPController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $phone = $request->phone;

        // 🚫 جلوگیری spam (3 requests per minute)
        $recentRequests = OtpRequest::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            return response()->json([
                'message' => 'Too many OTP requests. Please wait.'
            ], 429);
        }

        try {
            // 📡 Call Movider API
            $response = Http::asForm()->post('https://api.movider.co/v1/verify', [
                'to' => $phone,
                'api_key' => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Failed to send OTP'
                ], 500);
            }

            $data = $response->json();

            if (!isset($data['request_id'])) {
                return response()->json([
                    'message' => 'Invalid API response'
                ], 500);
            }

            // 💾 Save to DB
            $otp = OtpRequest::create([
                'user_id' => auth()->id(), // nullable if guest
                'phone' => $phone,
                'request_id' => $data['request_id'],
                'expires_at' => now()->addMinutes(5),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'OTP sent successfully',
                'request_id' => $otp->request_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'request_id' => 'required|string',
            'otp' => 'required|string'
        ]);

        $otp = OtpRequest::where('request_id', $request->request_id)->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Invalid request ID'
            ], 404);
        }

        // ⏱ Check expiration
        if ($otp->isExpired()) {
            return response()->json([
                'message' => 'OTP expired'
            ], 400);
        }

        // 🔁 Prevent reuse
        if ($otp->is_verified) {
            return response()->json([
                'message' => 'OTP already used'
            ], 400);
        }

        try {
            // 📡 Verify via Movider
            $response = Http::asForm()->post('https://api.movider.co/v1/verify/acknowledge', [
                'request_id' => $otp->request_id,
                'code' => $request->otp,
                'api_key' => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
            ]);

            $data = $response->json();

            if (!$response->successful() || !isset($data['request_id'])) {
                return response()->json([
                    'message' => 'Invalid OTP'
                ], 400);
            }

            // ✅ Mark as verified
            $otp->markAsVerified();

            return response()->json([
                'message' => 'OTP verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyRegistrationOTP(Request $request)
{
    $request->validate([
        'request_id' => 'required',
        'otp' => 'required',
        'user_id' => 'required'
    ]);

    $otp = OtpRequest::where('request_id', $request->request_id)->first();

    if (!$otp) {
        return response()->json(['message' => 'Invalid request'], 404);
    }

    if ($otp->isExpired()) {
        return response()->json(['message' => 'OTP expired'], 400);
    }

    if ($otp->is_verified) {
        return response()->json(['message' => 'Already verified'], 400);
    }

    // 📡 Verify from Movider
    $response = Http::asForm()->post('https://api.movider.co/v1/verify/acknowledge', [
        'request_id' => $otp->request_id,
        'code' => $request->otp,
        'api_key' => env('MOVIDER_API_KEY'),
        'api_secret' => env('MOVIDER_API_SECRET'),
    ]);

    $data = $response->json();

    if (!$response->successful() || !isset($data['request_id'])) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    // ✅ Mark OTP verified
    $otp->markAsVerified();

    // ✅ Activate user
    $user = \App\Models\User::find($request->user_id);
    $user->update([
        'phone_verified_at' => now()
    ]);

    return response()->json([
        'message' => 'Account verified! You can now login.'
    ]);
}
}