<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\FullAddress;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\BarangayOfficer;
use App\Models\PostalCode;
use App\Models\OtpRequest;
use App\Models\StationOfficer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /**
     * Role code mappings
     */
    private $roleCodes = [
        'citizen' => 'CIT',
        'admin' => 'ADM',
        'firefighter' => 'BFP',
        'chief' => 'FMC',
        'dispatcher' => 'DSP',
        'paramedic' => 'PAR',
        'investigator' => 'INV',
        'barangay officer' => 'BRG',
        'barangay_officer' => 'BRG',
    ];

    private array $stationOfficerRanks = [
        'Fire Officer 1',
        'Fire Officer 2',
        'Fire Officer 3',
        'Senior Officer 1',
        'Senior Officer 2',
        'Senior Officer 3',
        'Senior Fire Officer 4',
        'Fire Inspector',
        'Fire Senior Inspector',
        'Fire Chief Inspector',
        'Fire Superintendent',
    ];

    private array $barangayOfficerPositions = [
        'captain' => 'Captain',
        'kagawad' => 'Kagawad',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'tanod' => 'Tanod',
        'officer' => 'Officer',
    ];

    private function getCitizenRoleId(): int
    {
        $citizenRoleId = Role::where('role', 'citizen')->value('role_id');

        if (! $citizenRoleId) {
            throw new \RuntimeException('Citizen role is not configured in the roles table.');
        }

        return (int) $citizenRoleId;
    }

    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        $citizenRoleId = $this->getCitizenRoleId();
        $provinces = Province::orderBy('province_name')->get();
        
        return view('auth.register', compact('provinces', 'citizenRoleId'));
    }

    /**
     * Direct registration endpoint used by admin/chief resource management.
     */
    public function register(Request $request)
    {
        if (! $request->filled('role_id')) {
            $request->merge(['role_id' => $this->getCitizenRoleId()]);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|regex:/^[0-9]{11}$/|unique:users,contact_number',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,role_id',
            'rank' => ['nullable', 'string', 'max:50', Rule::in($this->stationOfficerRanks)],
            'barangay_officer_position' => ['nullable', 'string', Rule::in(array_keys($this->barangayOfficerPositions))],
            'barangay_officer_status' => 'nullable|in:active,inactive',
            'password' => 'nullable|string|min:8|confirmed',
            'province_id' => 'nullable|exists:provinces,province_id',
            'city_id' => 'nullable|exists:cities,city_id',
            'barangay_id' => 'nullable|exists:barangays,barangay_id',
            'street' => 'nullable|string|max:255',
            'terms' => 'required',
            'emergency_agreement' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $role = Role::findOrFail($validated['role_id']);
            $roleName = strtolower($role->role ?? '');
            $bfpRoles = ['firefighter', 'chief', 'station admin'];
            $barangayOfficerRoles = ['barangay officer', 'barangay_officer'];

            if (in_array($roleName, $bfpRoles, true) && empty($validated['rank'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rank is required for firefighter, chief, and station admin accounts.',
                ], 422);
            }

            if (in_array($roleName, $barangayOfficerRoles, true)) {
                if (empty($validated['barangay_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Barangay is required for barangay officer accounts.',
                    ], 422);
                }

                if (empty($validated['barangay_officer_position'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Position is required for barangay officer accounts.',
                    ], 422);
                }
            }

            $addressId = null;

            if (!empty($validated['street']) && !empty($validated['barangay_id'])) {
                $postalId = null;

                if (!empty($validated['city_id'])) {
                    $postalId = PostalCode::where('city_id', $validated['city_id'])
                        ->orderBy('postal_code')
                        ->value('postal_id');
                }

                $existingAddress = FullAddress::where('street', $validated['street'])
                    ->where('barangay_id', $validated['barangay_id'])
                    ->where('postal_id', $postalId)
                    ->first();

                if ($existingAddress) {
                    $addressId = $existingAddress->address_id;
                } else {
                    $address = FullAddress::create([
                        'street' => $validated['street'],
                        'barangay_id' => $validated['barangay_id'],
                        'postal_id' => $postalId,
                    ]);
                    $addressId = $address->address_id;
                }
            }

            $plainPassword = $validated['password'] ?: $this->generateTemporaryPassword();
            $accountCode = $this->generateAccountCode($role->role);

            $user = User::create([
                'account_code' => $accountCode,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'role_id' => $validated['role_id'],
                'address_id' => $addressId,
                'is_verified' => true,
                'password' => Hash::make($plainPassword),
            ]);

            if (in_array($roleName, $bfpRoles, true)) {
                $loggedStationOfficer = Auth::check()
                    ? StationOfficer::where('user_id', Auth::user()->user_id)->first()
                    : null;

                if (!$loggedStationOfficer?->station_id) {
                    throw new \RuntimeException('Unable to assign the new BFP user to a station because the current user has no station assignment.');
                }

                $stationOfficerData = [
                    'station_id' => $loggedStationOfficer->station_id,
                    'user_id' => $user->user_id,
                    'rank' => $validated['rank'],
                ];

                if (Schema::hasColumn('station_officers', 'status')) {
                    $stationOfficerData['status'] = 'available';
                }

                StationOfficer::create($stationOfficerData);
            }

            if (in_array($roleName, $barangayOfficerRoles, true)) {
                $barangayOfficerData = [
                    'barangay_id' => $validated['barangay_id'],
                    'user_id' => $user->user_id,
                    'position' => $this->barangayOfficerPositions[$validated['barangay_officer_position']] ?? 'Officer',
                ];

                if (Schema::hasColumn('barangay_officers', 'status')) {
                    $barangayOfficerData['status'] = $validated['barangay_officer_status'] ?? 'active';
                }

                BarangayOfficer::create($barangayOfficerData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'user' => [
                    'user_id' => $user->user_id,
                    'account_code' => $user->account_code,
                    'full_name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'contact_number' => $user->contact_number,
                ],
                'generated_password' => $validated['password'] ? null : $plainPassword,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Direct register failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cities by province (AJAX)
     */
    public function getCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)
            ->orderBy('city_name')
            ->get();
        
        return response()->json($cities);
    }

    /**
     * Get barangays by city (AJAX)
     */
    public function getBarangays($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)
            ->orderBy('barangay_name')
            ->get();
        
        return response()->json($barangays);
    }

    /**
     * Get postal codes by city (AJAX)
     */
    public function getPostalCodes($cityId)
    {
        $postalCodes = PostalCode::where('city_id', $cityId)
            ->orderBy('postal_code')
            ->get();
        
        return response()->json($postalCodes);
    }

    /**
     * Get first postal code by city (for auto-assign)
     */
    public function getDefaultPostalCode($cityId)
    {
        $postalCode = PostalCode::where('city_id', $cityId)
            ->orderBy('postal_code')
            ->first();
        
        return response()->json($postalCode);
    }

    /**
     * Generate unique account code based on role
     */
    private function generateAccountCode($roleName)
    {
        // Get the role code prefix
        $roleCode = $this->roleCodes[strtolower($roleName)] ?? 'USR';
        
        // Get the last account code for this specific role
        $lastUser = User::where('account_code', 'LIKE', $roleCode . '-%')
            ->orderBy('user_id', 'desc')
            ->first();
        
        if ($lastUser) {
            // Extract the number from the last code
            $lastNumber = (int) substr($lastUser->account_code, strlen($roleCode) + 1);
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no existing users with this role
            $newNumber = 1;
        }
        
        // Format with leading zeros (4 digits)
        return $roleCode . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function generateTemporaryPassword(): string
    {
        return 'Pyro' . random_int(100000, 999999) . '!';
    }

    /**
     * Step 1: Send OTP for phone verification
     */
    // public function sendOTP(Request $request)
    // {
    //     $request->validate([
    //         'phone' => 'required|string|regex:/^[0-9]{11}$/',
    //         'email' => 'required|email|unique:users,email',
    //     ]);

    //     $phone = $request->phone;

    //     // Check if phone already exists
    //     if (User::where('contact_number', $phone)->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Phone number already registered'
    //         ], 400);
    //     }

    //     // Rate limiting: 3 requests per minute
    //     $recentRequests = OtpRequest::where('phone', $phone)
    //         ->where('created_at', '>=', now()->subMinute())
    //         ->count();

    //     if ($recentRequests >= 3) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Too many OTP requests. Please wait a minute.'
    //         ], 429);
    //     }

    //     try {
    //         // Send OTP via Movider
    //         $response = Http::asForm()->post('https://api.movider.co/v1/verify', [
    //             'to' => $phone,
    //             'api_key' => env('MOVIDER_API_KEY'),
    //             'api_secret' => env('MOVIDER_API_SECRET'),
    //             'code_length' => 6,
    //             'pin_expire' => 300, // 5 minutes
    //         ]);

    //         if (!$response->successful()) {
    //             Log::error('Movider send error: ' . $response->body());
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Failed to send OTP. Please try again.'
    //             ], 500);
    //         }

    //         $data = $response->json();

    //         if (!isset($data['request_id'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid API response'
    //             ], 500);
    //         }

    //         // Store OTP request temporarily (without user_id since user not created yet)
    //         $otp = OtpRequest::create([
    //             'user_id' => null, // Will be updated after registration
    //             'phone' => $phone,
    //             'request_id' => $data['request_id'],
    //             'expires_at' => now()->addMinutes(5),
    //             'ip_address' => $request->ip(),
    //             'user_agent' => $request->userAgent(),
    //         ]);

    //         // Store registration data in session temporarily
    //         $request->session()->put('pending_registration', [
    //             'form_data' => $request->except(['_token']),
    //             'otp_request_id' => $otp->id,
    //             'phone' => $phone,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'OTP sent successfully',
    //             'otp_request_id' => $otp->id,
    //             'request_id' => $data['request_id']
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('OTP send exception: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Server error. Please try again.'
    //         ], 500);
    //     }
    // }
/**
 * Step 1: Send OTP for phone verification
 */
public function sendOTP(Request $request)
{
    try {
        if (! $request->filled('role_id')) {
            $request->merge(['role_id' => $this->getCitizenRoleId()]);
        }

        $validated = $request->validate($this->otpRegistrationRules());

        $email = $validated['email'];

        $recentRequests = OtpRequest::where('email', $email)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification requests. Please wait a minute.'
            ], 429);
        }

        $otpCode = $this->generateOtpCode();
        $requestId = 'email-otp-' . uniqid();

        Mail::raw(
            "Your PyroSense verification code is {$otpCode}. This code will expire in 10 minutes.",
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('PyroSense Email Verification Code');
            }
        );

        $otp = OtpRequest::create([
            'user_id' => null,
            'phone' => $validated['contact_number'],
            'email' => $email,
            'request_id' => $requestId,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

$request->session()->put('pending_registration', [
            'form_data' => $validated,
            'otp_request_id' => $otp->id,
            'email' => $email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent successfully',
            'otp_request_id' => $otp->id,
            'email' => $email,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Email OTP send exception: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Step 2: Verify OTP and complete registration
     */
    // public function verifyOTPAndRegister(Request $request)
    // {
    //     $request->validate([
    //         'otp_request_id' => 'required|exists:otp_requests,id',
    //         'otp_code' => 'required|string|size:6',
    //     ]);

    //     $otpRequest = OtpRequest::find($request->otp_request_id);

    //     // Check if OTP is expired
    //     if ($otpRequest->isExpired()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'OTP has expired. Please request a new one.'
    //         ], 400);
    //     }

    //     // Check if already verified
    //     if ($otpRequest->is_verified) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'This OTP has already been used.'
    //         ], 400);
    //     }

    //     try {
    //         // Verify OTP with Movider
    //         $response = Http::asForm()->post('https://api.movider.co/v1/verify/acknowledge', [
    //             'request_id' => $otpRequest->request_id,
    //             'code' => $request->otp_code,
    //             'api_key' => env('MOVIDER_API_KEY'),
    //             'api_secret' => env('MOVIDER_API_SECRET'),
    //         ]);

    //         $data = $response->json();

    //         if (!$response->successful() || !isset($data['request_id'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid OTP code. Please try again.'
    //             ], 400);
    //         }

    //         // Get pending registration data from session
    //         $pendingData = $request->session()->get('pending_registration');
            
    //         if (!$pendingData || $pendingData['otp_request_id'] != $otpRequest->id) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Registration session expired. Please start over.'
    //             ], 400);
    //         }

    //         $formData = $pendingData['form_data'];

    //         // Begin transaction to create user
    //         DB::beginTransaction();

    //         try {
    //             // Get the role to determine account code prefix
    //             $role = Role::find($formData['role_id']);
                
    //             if (!$role) {
    //                 throw new \Exception('Role not found.');
    //             }

    //             // Generate unique account code based on role
    //             $accountCode = $this->generateAccountCode($role->role);

    //             // Handle address creation if address data is provided
    //             $addressId = null;
                
    //             if (!empty($formData['street']) && !empty($formData['barangay_id'])) {
    //                 // Check if address already exists
    //                 $existingAddress = FullAddress::where('street', $formData['street'])
    //                     ->where('barangay_id', $formData['barangay_id'])
    //                     ->where('postal_id', $formData['postal_id'] ?? null)
    //                     ->first();
                    
    //                 if ($existingAddress) {
    //                     $addressId = $existingAddress->address_id;
    //                 } else {
    //                     // Create new address
    //                     $address = FullAddress::create([
    //                         'street' => $formData['street'],
    //                         'barangay_id' => $formData['barangay_id'],
    //                         'postal_id' => $formData['postal_id'] ?? null,
    //                     ]);
    //                     $addressId = $address->address_id;
    //                 }
    //             }

    //             // Create the user
    //             $user = User::create([
    //                 'account_code' => $accountCode,
    //                 'first_name' => $formData['first_name'],
    //                 'middle_name' => $formData['middle_name'] ?? null,
    //                 'last_name' => $formData['last_name'],
    //                 'email' => $formData['email'],
    //                 'contact_number' => $formData['contact_number'],
    //                 'role_id' => $formData['role_id'],
    //                 'address_id' => $addressId,
    //                 'password' => Hash::make($formData['password']),
    //                 'phone_verified_at' => now(), // Mark phone as verified
    //             ]);

    //             // Update OTP request with user_id
    //             $otpRequest->update([
    //                 'user_id' => $user->user_id,
    //                 'is_verified' => true,
    //                 'verified_at' => now(),
    //             ]);

    //             // Clear the session data
    //             $request->session()->forget('pending_registration');

    //             DB::commit();

    //             // Log the user in after registration
    //             Auth::login($user);

    //             // Determine redirect based on role
    //             $roleName = strtolower($user->role->role ?? '');
    //             $redirectUrl = route('citizen.dashboard'); // default
                
    //             switch ($roleName) {
    //                 case 'admin':
    //                     $redirectUrl = route('admin.dashboard');
    //                     break;
    //                 case 'firefighter':
    //                     $redirectUrl = route('firefighter.dashboard');
    //                     break;
    //                 case 'chief':
    //                     $redirectUrl = route('station-admin.dashboard');
    //                     break;
    //                 case 'station admin':
    //                     $redirectUrl = route('station-admin.dashboard');
    //                     break;
    //                 default:
    //                     $redirectUrl = route('citizen.dashboard');
    //             }

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Registration successful! Your account code is: ' . $accountCode,
    //                 'redirect' => $redirectUrl,
    //                 'account_code' => $accountCode
    //             ]);

    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             Log::error('User creation error: ' . $e->getMessage());
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Failed to create account: ' . $e->getMessage()
    //             ], 500);
    //         }

    //     } catch (\Exception $e) {
    //         Log::error('OTP verification error: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Verification failed. Please try again.'
    //         ], 500);
    //     }
    // }
/**
 * Step 2: Verify OTP and complete registration
 */
public function verifyOTPAndRegister(Request $request)
{
    try {
        $request->validate([
            'otp_request_id' => 'required|exists:otp_requests,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $otpRequest = OtpRequest::find($request->otp_request_id);

        if (!$otpRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP request'
            ], 404);
        }

        // Check if OTP is expired
        if ($otpRequest->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }

        // Check if already verified
        if ($otpRequest->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'This OTP has already been used.'
            ], 400);
        }

        if (!hash_equals((string) $otpRequest->otp_code, (string) $request->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please try again.'
            ], 400);
        }

        $pendingData = $request->session()->get('pending_registration');

        if (!$pendingData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please start over.'
            ], 400);
        }

        $formData = $pendingData['form_data'];

        if (($pendingData['otp_request_id'] ?? null) != $otpRequest->id || ($pendingData['email'] ?? null) !== $otpRequest->email) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please start over.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $role = Role::find($formData['role_id']);
            
            if (!$role) {
                throw new \Exception('Role not found.');
            }

            $accountCode = $this->generateAccountCode($role->role);

            $addressId = null;
            
            if (!empty($formData['street']) && !empty($formData['barangay_id'])) {
                $existingAddress = FullAddress::where('street', $formData['street'])
                    ->where('barangay_id', $formData['barangay_id'])
                    ->where('postal_id', $formData['postal_id'] ?? null)
                    ->first();
                
                if ($existingAddress) {
                    $addressId = $existingAddress->address_id;
                } else {
                    $address = FullAddress::create([
                        'street' => $formData['street'],
                        'barangay_id' => $formData['barangay_id'],
                        'postal_id' => $formData['postal_id'] ?? null,
                    ]);
                    $addressId = $address->address_id;
                }
            }

            $userData = [
                'account_code' => $accountCode,
                'first_name' => $formData['first_name'],
                'middle_name' => $formData['middle_name'] ?? null,
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'contact_number' => $formData['contact_number'],
                'role_id' => $formData['role_id'],
                'address_id' => $addressId,
                'password' => Hash::make($formData['password']),
                'email_verified_at' => now(),
                'is_verified' => true,
            ];

            $user = User::create($userData);

            $otpRequest->update([
                'user_id' => $user->user_id,
                'is_verified' => true,
                'verified_at' => now(),
                'otp_code' => null,
            ]);

            $request->session()->forget('pending_registration');

            DB::commit();

            Auth::login($user);

            // Determine redirect based on role
            $roleName = strtolower($user->role->role ?? '');
            $redirectUrl = route('citizen.dashboard');
            
            switch ($roleName) {
                case 'admin':
                    $redirectUrl = route('admin.dashboard');
                    break;
                case 'firefighter':
                    $redirectUrl = route('firefighter.dashboard');
                    break;
                case 'chief':
                    $redirectUrl = route('station-admin.dashboard');
                    break;
                case 'station admin':
                    $redirectUrl = route('station-admin.dashboard');
                    break;
                default:
                    $redirectUrl = route('citizen.dashboard');
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Your account code is: ' . $accountCode,
                'redirect' => $redirectUrl,
                'account_code' => $accountCode
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User creation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account: ' . $e->getMessage()
            ], 500);
        }

    } catch (\Exception $e) {
        \Log::error('Verification error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Verification failed: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Resend OTP
     */
public function resendOTP(Request $request)
{
        $request->validate([
            'email' => 'required|email'
        ]);

        $pendingData = $request->session()->get('pending_registration');
        
        if (!$pendingData || $pendingData['email'] !== $request->email) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Please start over.'
            ], 400);
        }

        $recentRequests = OtpRequest::where('email', $request->email)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Too many OTP requests. Please wait a minute.'
            ], 429);
        }

        try {
            $otpCode = $this->generateOtpCode();
            $requestId = 'email-otp-' . uniqid();

            Mail::raw(
                "Your PyroSense verification code is {$otpCode}. This code will expire in 10 minutes.",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('PyroSense Email Verification Code');
                }
            );

            $otp = OtpRequest::create([
                'user_id' => null,
                'phone' => $pendingData['form_data']['contact_number'] ?? null,
                'email' => $request->email,
                'request_id' => $requestId,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(10),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $pendingData['otp_request_id'] = $otp->id;
            $request->session()->put('pending_registration', $pendingData);

            return response()->json([
                'success' => true,
                'message' => 'Verification code resent successfully',
                'otp_request_id' => $otp->id
            ]);

        } catch (\Exception $e) {
            Log::error('Email OTP resend exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification code'
            ], 500);
        }
    }

    private function otpRegistrationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|regex:/^[0-9]{11}$/|unique:users,contact_number',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,role_id',
            'password' => 'required|string|min:8|confirmed',
            'province_id' => 'nullable|exists:provinces,province_id',
            'city_id' => 'nullable|exists:cities,city_id',
            'barangay_id' => 'nullable|exists:barangays,barangay_id',
            'postal_id' => 'nullable',
            'street' => 'nullable|string|max:255',
            'terms' => 'required',
            'emergency_agreement' => 'required',
        ];
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get account code preview based on selected role (for AJAX)
     */
    public function previewAccountCode(Request $request)
    {
        $roleId = $request->role_id;
        
        if (!$roleId) {
            return response()->json(['account_code' => '']);
        }
        
        $role = Role::find($roleId);
        
        if (!$role) {
            return response()->json(['account_code' => '']);
        }
        
        $accountCode = $this->generateAccountCode($role->role);
        
        return response()->json(['account_code' => $accountCode]);
    }
}
