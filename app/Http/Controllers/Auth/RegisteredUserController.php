<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Show registration form
     */
    public function create()
    {
        // Your existing code to load roles, provinces, etc.
        return view('auth.register', compact('roles', 'provinces'));
    }

    /**
     * Step 1: Send OTP for phone verification
     */
    public function sendRegistrationOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^[0-9]{11}$/',
            'email' => 'required|email|unique:users,email',
        ]);

        $phone = $request->phone;

        // Check if phone already exists
        if (User::where('contact_number', $phone)->exists()) {
            return response()->json([
                'message' => 'Phone number already registered'
            ], 400);
        }

        // Rate limiting: 3 requests per minute
        $recentRequests = OtpRequest::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            return response()->json([
                'message' => 'Too many OTP requests. Please wait.'
            ], 429);
        }

        try {
            // Send OTP via Movider
            $response = Http::asForm()->post('https://api.movider.co/v1/verify', [
                'to' => $phone,
                'api_key' => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
                'code_length' => 6,
                'pin_expire' => 300, // 5 minutes
            ]);

            if (!$response->successful()) {
                \Log::error('Movider send error: ' . $response->body());
                return response()->json([
                    'message' => 'Failed to send OTP. Please try again.'
                ], 500);
            }

            $data = $response->json();

            if (!isset($data['request_id'])) {
                return response()->json([
                    'message' => 'Invalid API response'
                ], 500);
            }

            // Store OTP request temporarily (without user_id since user not created yet)
            $otp = OtpRequest::create([
                'user_id' => null, // Will be updated after registration
                'phone' => $phone,
                'request_id' => $data['request_id'],
                'expires_at' => now()->addMinutes(5),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Store registration data in session temporarily
            $request->session()->put('pending_registration', [
                'form_data' => $request->except(['_token']),
                'otp_request_id' => $otp->id,
                'phone' => $phone,
            ]);

            return response()->json([
                'message' => 'OTP sent successfully',
                'otp_request_id' => $otp->id,
                'request_id' => $data['request_id']
            ]);

        } catch (\Exception $e) {
            \Log::error('OTP send exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error. Please try again.'
            ], 500);
        }
    }

    /**
     * Step 2: Verify OTP and complete registration
     */
    public function verifyOTPAndRegister(Request $request)
    {
        $request->validate([
            'otp_request_id' => 'required|exists:otp_requests,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $otpRequest = OtpRequest::find($request->otp_request_id);

        // Check if OTP is expired
        if ($otpRequest->isExpired()) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }

        // Check if already verified
        if ($otpRequest->is_verified) {
            return response()->json([
                'message' => 'This OTP has already been used.'
            ], 400);
        }

        try {
            // Verify OTP with Movider
            $response = Http::asForm()->post('https://api.movider.co/v1/verify/acknowledge', [
                'request_id' => $otpRequest->request_id,
                'code' => $request->otp_code,
                'api_key' => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
            ]);

            $data = $response->json();

            if (!$response->successful() || !isset($data['request_id'])) {
                return response()->json([
                    'message' => 'Invalid OTP code. Please try again.'
                ], 400);
            }

            // Get pending registration data from session
            $pendingData = $request->session()->get('pending_registration');
            
            if (!$pendingData || $pendingData['otp_request_id'] != $otpRequest->id) {
                return response()->json([
                    'message' => 'Registration session expired. Please start over.'
                ], 400);
            }

            $formData = $pendingData['form_data'];

            // Begin transaction to create user
            DB::beginTransaction();

            try {
                // Create the user
                $user = User::create([
                    'first_name' => $formData['first_name'],
                    'middle_name' => $formData['middle_name'] ?? null,
                    'last_name' => $formData['last_name'],
                    'email' => $formData['email'],
                    'contact_number' => $formData['contact_number'],
                    'password' => Hash::make($formData['password']),
                    'role_id' => $formData['role_id'],
                    'province_id' => $formData['province_id'] ?? null,
                    'city_id' => $formData['city_id'] ?? null,
                    'barangay_id' => $formData['barangay_id'] ?? null,
                    'postal_id' => $formData['postal_id'] ?? null,
                    'street' => $formData['street'] ?? null,
                    'phone_verified_at' => now(), // Mark phone as verified
                ]);

                // Update OTP request with user_id
                $otpRequest->update([
                    'user_id' => $user->user_id,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]);

                // Clear the session data
                $request->session()->forget('pending_registration');

                DB::commit();

                // Optionally log the user in
                auth()->login($user);

                return response()->json([
                    'message' => 'Account created and verified successfully!',
                    // 'redirect' => route('dashboard') // or wherever you want to redirect
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('User creation error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Failed to create account. Please try again.'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('OTP verification error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Verification failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $pendingData = $request->session()->get('pending_registration');
        
        if (!$pendingData || $pendingData['phone'] !== $request->phone) {
            return response()->json([
                'message' => 'Invalid session. Please start over.'
            ], 400);
        }

        // Rate limiting check
        $recentRequests = OtpRequest::where('phone', $request->phone)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            return response()->json([
                'message' => 'Too many OTP requests. Please wait a minute.'
            ], 429);
        }

        try {
            $response = Http::asForm()->post('https://api.movider.co/v1/verify', [
                'to' => $request->phone,
                'api_key' => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
                'code_length' => 6,
                'pin_expire' => 300,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Failed to resend OTP'
                ], 500);
            }

            $data = $response->json();

            // Create new OTP request
            $otp = OtpRequest::create([
                'user_id' => null,
                'phone' => $request->phone,
                'request_id' => $data['request_id'],
                'expires_at' => now()->addMinutes(5),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Update session with new OTP request ID
            $pendingData['otp_request_id'] = $otp->id;
            $request->session()->put('pending_registration', $pendingData);

            return response()->json([
                'message' => 'OTP resent successfully',
                'otp_request_id' => $otp->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend OTP'
            ], 500);
        }
    }
}