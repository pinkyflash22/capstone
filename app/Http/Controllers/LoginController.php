<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\OtpRequest;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Maximum number of login attempts
     */
    private $maxAttempts = 5;
    
    /**
     * Lockout time in minutes
     */
    private $lockoutMinutes = 15;

    /**
     * Role code mappings for generated account codes.
     */
    private array $roleCodes = [
        'citizen' => 'CIT',
        'admin' => 'ADM',
        'firefighter' => 'BFP',
        'chief' => 'FMC',
        'dispatcher' => 'DSP',
        'paramedic' => 'PAR',
        'investigator' => 'INV',
        'barangay' => 'BRG',
        'barangay captain' => 'BRC',
        'station admin' => 'STA',
        'provincial admin' => 'PAD',
    ];

    /**
     * Show the login form
     */
    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $role = strtolower($user->role->role ?? '');

            return $this->redirectBasedOnRole($role);

        }

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Handle login authentication with session-based attempt tracking
     */
    public function authenticate(Request $request)
    {
        // Check if account is locked
        if ($this->isAccountLocked($request)) {
            $lockoutTime = session('login_lockout_time');
            
            throw ValidationException::withMessages([
                'email' => $this->formatLockoutMessage($lockoutTime)
            ]);
        }

        // Validate credentials
        $credentials = $request->validate([
            'email' => ['required', 'email', 'regex:/^[A-Za-z0-9.]+@[A-Za-z0-9.]+\.[A-Za-z]{2,}$/'],
            'password' => ['required', 'string'],
        ]);
        $postLoginAction = $request->input('post_login_action');

        // Attempt to authenticate
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Reset attempts on successful login
            $this->resetLoginAttempts($request);
            
            // Regenerate session
            $request->session()->regenerate();

            // Get user and role
            $user = Auth::user();

            if (! $user?->is_verified) {
                Auth::logout();

                if ($user?->google_id && $user?->email) {
                    try {
                        $this->sendGoogleVerificationOtp($user, $request);
                    } catch (ValidationException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        report($e);

                        throw ValidationException::withMessages([
                            'email' => 'Unable to send a verification code right now. Please try again.',
                        ]);
                    }

                    return redirect()->route('auth.google.verify')
                        ->with('success', 'Verification code sent to your email. Please complete verification to continue.');
                }

                throw ValidationException::withMessages([
                    'email' => 'Your account is not verified yet. Please complete OTP verification first.',
                ]);
            }

            $this->storeUserLog($user?->user_id, 'logged in', $request);
            $role = strtolower($user->role->role ?? '');

            $this->applyPostLoginAction($postLoginAction, $role);

            return $this->redirectBasedOnRole($role);
        }

        // Failed login - increment attempts
        $this->incrementLoginAttempts($request);
        
        $attemptsLeft = $this->maxAttempts - $this->getLoginAttempts($request);
        if ($attemptsLeft <= 0) {
            throw ValidationException::withMessages([
                'email' => $this->formatLockoutMessage(session('login_lockout_time'))
            ]);
        }
        
        throw ValidationException::withMessages([
            'email' => "Invalid email or password. You have {$attemptsLeft} attempt(s) remaining."
        ]);
    }

    public function showForgotPasswordForm(Request $request)
    {
        if ($request->boolean('clear_lookup')) {
            $request->session()->forget('password_recovery_lookup');
        }

        return response()
            ->view('auth.forgot-password', [
                'recoveryLookup' => $request->session()->get('password_recovery_lookup'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function searchPasswordRecoveryAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'search_mode' => ['required', 'in:identity,mobile'],
            'account_identifier' => ['required', 'string', 'max:255'],
        ], [
            'search_mode.required' => 'Select how you want to search for your account.',
            'account_identifier.required' => 'Enter your account details.',
        ]);

        $searchMode = $validated['search_mode'];
        $identifier = trim($validated['account_identifier']);

        if ($searchMode === 'mobile') {
            if (! preg_match('/^09\d{9}$/', $identifier)) {
                throw ValidationException::withMessages([
                    'account_identifier' => 'Mobile number must be 11 digits and start with 09.',
                ]);
            }
        } else {
            $isEmail = str_contains($identifier, '@');

            if ($isEmail) {
                if (! preg_match('/^[A-Za-z0-9.]+@[A-Za-z0-9.]+\.[A-Za-z]{2,}$/', $identifier)) {
                    throw ValidationException::withMessages([
                        'account_identifier' => 'Enter a valid email address using letters, numbers, @, and dots only.',
                    ]);
                }
            } else {
                if (! preg_match('/^[A-Za-z ]+$/', $identifier)) {
                    throw ValidationException::withMessages([
                        'account_identifier' => 'Name search only allows letters and spaces.',
                    ]);
                }
            }
        }

        $user = $this->findRecoveryUserByIdentifier($identifier, $searchMode);

        if (! $user) {
            throw ValidationException::withMessages([
                'account_identifier' => $searchMode === 'mobile'
                    ? 'No PyroSense account matched that mobile number.'
                    : 'No PyroSense account matched that name or email address.',
            ]);
        }

        $channels = $this->buildRecoveryChannels($user);

        if (empty($channels)) {
            throw ValidationException::withMessages([
                'account_identifier' => 'This account does not have a supported recovery destination yet.',
            ]);
        }

        $request->session()->put('password_recovery_lookup', [
            'user_id' => $user->user_id,
            'account_identifier' => $identifier,
            'search_mode' => $searchMode,
            'channels' => $channels,
        ]);

        return redirect()
            ->route('password.request')
            ->with('success', 'We found a matching PyroSense account. Confirm where you want the reset code sent.');
    }

    public function sendPasswordResetOtp(Request $request): RedirectResponse
    {
        $lookup = $request->session()->get('password_recovery_lookup');

        if (! $lookup) {
            return redirect()
                ->route('password.request')
                ->withErrors([
                    'account_identifier' => 'Search for your account first before sending a reset code.',
                ]);
        }

        $validated = $request->validate([
            'delivery_channel' => ['required', 'in:email,phone'],
        ], [
            'delivery_channel.required' => 'Select where you want to receive the reset code.',
        ]);

        $user = User::find($lookup['user_id'] ?? null);

        if (! $user) {
            $request->session()->forget('password_recovery_lookup');

            return redirect()
                ->route('password.request')
                ->withErrors([
                    'account_identifier' => 'The selected account is no longer available. Please search again.',
                ]);
        }

        $channels = collect($this->buildRecoveryChannels($user))->keyBy('type');
        $selectedChannel = $channels->get($validated['delivery_channel']);

        if (! $selectedChannel) {
            throw ValidationException::withMessages([
                'delivery_channel' => 'The selected recovery destination is not available for this account.',
            ]);
        }

        $maskedDestination = $selectedChannel['masked'];
        $channelType = $selectedChannel['type'];

        if ($channelType === 'email') {
            $recentRequests = OtpRequest::where('email', $user->email)
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($recentRequests >= 3) {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'Too many recovery requests. Please wait a minute before trying again.',
                ]);
            }

            $otpCode = $this->generateOtpCode();
            $requestId = 'password-reset-otp-' . uniqid();

            Mail::raw(
                "Your PyroSense password reset code is {$otpCode}. This code will expire in 10 minutes. If you did not request this, you can ignore this email.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('PyroSense Password Reset Code');
                }
            );

            $otp = OtpRequest::create([
                'user_id' => $user->user_id,
                'phone' => (string) ($user->contact_number ?? 'N/A'),
                'email' => $user->email,
                'request_id' => $requestId,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(10),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } else {
            $phone = preg_replace('/\D+/', '', (string) $user->contact_number);

            if ($phone === '') {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'This account does not have a valid mobile number for OTP delivery.',
                ]);
            }

            $recentRequests = OtpRequest::where('phone', $phone)
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($recentRequests >= 3) {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'Too many recovery requests. Please wait a minute before trying again.',
                ]);
            }

            $apiKey = env('MOVIDER_API_KEY');
            $apiSecret = env('MOVIDER_API_SECRET');

            if (! $apiKey || ! $apiSecret) {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'Mobile OTP delivery is not configured on this server right now.',
                ]);
            }

            $response = Http::asForm()->post('https://api.movider.co/v1/verify', [
                'to' => $phone,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ]);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'Unable to send the reset code to your mobile number right now. Please try again.',
                ]);
            }

            $responseData = $response->json();
            $requestId = $responseData['request_id'] ?? null;

            if (! $requestId) {
                throw ValidationException::withMessages([
                    'delivery_channel' => 'The mobile OTP provider returned an invalid response. Please try again.',
                ]);
            }

            $otp = OtpRequest::create([
                'user_id' => $user->user_id,
                'phone' => $phone,
                'email' => $user->email,
                'request_id' => $requestId,
                'otp_code' => null,
                'expires_at' => now()->addMinutes(5),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $request->session()->put('pending_password_reset', [
            'user_id' => $user->user_id,
            'otp_request_id' => $otp->id,
            'email' => $user->email,
            'channel' => $channelType,
            'masked_destination' => $maskedDestination,
            'channel_label' => $selectedChannel['label'],
        ]);

        $request->session()->forget('password_recovery_lookup');

        return redirect()
            ->route('password.reset.form')
            ->with('success', 'We sent a reset code to ' . $maskedDestination . '.');
    }

    public function showResetPasswordForm(Request $request)
    {
        if (! $request->session()->has('pending_password_reset')) {
            return redirect()->route('password.request');
        }

        return response()
            ->view('auth.forgot-password', [
                'resetMode' => true,
                'maskedEmail' => $this->maskEmail((string) $request->session()->get('pending_password_reset.email')),
                'maskedDestination' => $request->session()->get('pending_password_reset.masked_destination'),
                'channelLabel' => $request->session()->get('pending_password_reset.channel_label'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function resetPasswordWithOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('pending_password_reset');

        if (! $pending) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@*.\-]+$/'],
        ]);

        $otpRequest = OtpRequest::find($pending['otp_request_id'] ?? null);
        $user = User::find($pending['user_id'] ?? null);

        if (! $otpRequest || ! $user || (int) $otpRequest->user_id !== (int) $user->user_id) {
            $request->session()->forget('pending_password_reset');

            throw ValidationException::withMessages([
                'otp_code' => 'The password reset request is no longer valid. Please start again.',
            ]);
        }

        if ($otpRequest->isExpired()) {
            throw ValidationException::withMessages([
                'otp_code' => 'The reset code has expired. Please request a new code.',
            ]);
        }

        if ($otpRequest->is_verified) {
            throw ValidationException::withMessages([
                'otp_code' => 'This reset code has already been used.',
            ]);
        }

        $channel = $pending['channel'] ?? 'email';

        if ($channel === 'phone') {
            $apiKey = env('MOVIDER_API_KEY');
            $apiSecret = env('MOVIDER_API_SECRET');

            if (! $apiKey || ! $apiSecret) {
                throw ValidationException::withMessages([
                    'otp_code' => 'Mobile OTP verification is not configured on this server right now.',
                ]);
            }

            $response = Http::asForm()->post('https://api.movider.co/v1/verify/acknowledge', [
                'request_id' => $otpRequest->request_id,
                'code' => $validated['otp_code'],
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ]);

            $responseData = $response->json();

            if (! $response->successful() || ! isset($responseData['request_id'])) {
                throw ValidationException::withMessages([
                    'otp_code' => 'Invalid reset code.',
                ]);
            }
        } elseif (! hash_equals((string) $otpRequest->otp_code, (string) $validated['otp_code'])) {
            throw ValidationException::withMessages([
                'otp_code' => 'Invalid reset code.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        $otpRequest->markAsVerified();
        $request->session()->forget('pending_password_reset');
        $this->storeUserLog($user->user_id, 'reset password', $request);

        return redirect()
            ->route('auth.login')
            ->with('success', 'Password updated. You can now sign in with your new password.');
    }

    /**
     * Redirect the user to Google for authentication.
     */
    public function redirectToGoogle(Request $request)
    {
        $postLoginAction = $request->input('post_login_action');

        if ($request->filled('post_login_action')) {
            $request->session()->put('post_login_action', $postLoginAction);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect()
            ->cookie($this->makePostLoginActionCookie($postLoginAction));
    }

    /**
     * Handle the Google authentication callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('auth.login')->withErrors([
                'email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('auth.login')->withErrors([
                'email' => 'Google did not return an email address for this account.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $citizenRoleId = Role::where('role', 'citizen')->value('role_id');

            if (! $citizenRoleId) {
                return redirect()->route('auth.login')->withErrors([
                    'email' => 'Citizen role is not configured. Please contact the administrator.',
                ]);
            }

            [$firstName, $middleName, $lastName] = $this->splitDisplayName(
                $googleUser->getName() ?: $email
            );

            $user = User::create([
                'account_code' => $this->generateAccountCode('citizen'),
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'role_id' => $citizenRoleId,
                'contact_number' => $this->generatePlaceholderContactNumber(),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'is_verified' => true,
                'password' => Hash::make($this->generateRandomPassword()),
                'image' => $googleUser->getAvatar(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
                'is_verified' => true,
                'image' => $googleUser->getAvatar() ?: $user->image,
            ])->save();
        }

        $request->session()->forget('pending_google_verification');

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->storeUserLog($user->user_id, 'logged in with Google', $request);

        $postLoginAction = $this->consumePostLoginAction($request);
        $role = strtolower($user->role->role ?? '');

        $this->applyPostLoginAction($postLoginAction, $role);

        return $this->redirectBasedOnRole($role)
            ->withCookie($this->forgetPostLoginActionCookie());
    }

    public function showGoogleVerificationForm(Request $request)
    {
        $pending = $request->session()->get('pending_google_verification');

        if (! $pending || empty($pending['email'])) {
            return redirect()->route('auth.login');
        }

        return view('auth.google-verify', [
            'email' => $pending['email'],
        ]);
    }

    public function verifyGoogleOtp(Request $request)
    {
        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $pending = $request->session()->get('pending_google_verification');

        if (! $pending) {
            return redirect()->route('auth.login')->withErrors([
                'email' => 'Verification session expired. Please sign in with Google again.',
            ]);
        }

        $otpRequest = OtpRequest::find($pending['otp_request_id'] ?? null);
        $user = User::find($pending['user_id'] ?? null);

        if (! $otpRequest || ! $user || $user->email !== ($pending['email'] ?? null)) {
            $request->session()->forget('pending_google_verification');

            return redirect()->route('auth.login')->withErrors([
                'email' => 'Verification session is invalid. Please sign in with Google again.',
            ]);
        }

        if ($otpRequest->isExpired()) {
            return back()->withErrors([
                'otp_code' => 'The verification code has expired. Please request a new code.',
            ]);
        }

        if ($otpRequest->is_verified) {
            return back()->withErrors([
                'otp_code' => 'This verification code has already been used.',
            ]);
        }

        if (! hash_equals((string) $otpRequest->otp_code, (string) $validated['otp_code'])) {
            return back()->withErrors([
                'otp_code' => 'Invalid verification code.',
            ]);
        }

        $otpRequest->update([
            'user_id' => $user->user_id,
            'is_verified' => true,
            'verified_at' => now(),
            'otp_code' => null,
        ]);

        $user->forceFill([
            'is_verified' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        $postLoginAction = $pending['post_login_action'] ?? $this->readPostLoginActionCookie($request);
        $role = strtolower($user->role->role ?? '');

        $request->session()->forget('pending_google_verification');

        $this->storeUserLog($user->user_id, 'completed Google OTP verification', $request);
        $this->applyPostLoginAction($postLoginAction, $role);

        return $this->redirectBasedOnRole($role)
            ->withCookie($this->forgetPostLoginActionCookie());
    }

    public function resendGoogleOtp(Request $request)
    {
        $pending = $request->session()->get('pending_google_verification');

        if (! $pending) {
            return redirect()->route('auth.login')->withErrors([
                'email' => 'Verification session expired. Please sign in with Google again.',
            ]);
        }

        $user = User::find($pending['user_id'] ?? null);

        if (! $user || ! $user->email) {
            $request->session()->forget('pending_google_verification');

            return redirect()->route('auth.login')->withErrors([
                'email' => 'Unable to resend verification code. Please sign in with Google again.',
            ]);
        }

        try {
            $this->sendGoogleVerificationOtp($user, $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'otp_code' => 'Unable to resend the verification code right now. Please try again.',
            ]);
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    /**
     * Handle station login with attempt tracking
     */
    public function stationLogin(Request $request)
    {
        $stationCode = $request->station_code;
        
        // Check if station account is locked
        if ($this->isStationAccountLocked($request)) {
            $lockoutTime = session('station_lockout_time_' . $stationCode);
            
            return back()->withErrors([
                'station_code' => $this->formatLockoutMessage($lockoutTime, 'Station account')
            ]);
        }

        $credentials = $request->validate([
            'station_code' => 'required|string',
            'password' => 'required|string'
        ]);

        // Attempt station authentication
        if (Auth::guard('station')->attempt([
            'station_code' => $stationCode,
            'password' => $request->password
        ], $request->boolean('remember'))) {
            
            // Reset attempts on successful login
            $this->resetStationLoginAttempts($request);
            
            $request->session()->regenerate();
            
            return redirect()->intended(route('station-admin.dashboard'));
        }
        
        // Failed login - increment attempts
        $this->incrementStationLoginAttempts($request);
        
        $attemptsLeft = $this->maxAttempts - $this->getStationLoginAttempts($request);
        if ($attemptsLeft <= 0) {
            return back()->withErrors([
                'station_code' => $this->formatLockoutMessage(session('station_lockout_time_' . $stationCode), 'Station account')
            ]);
        }
        
        return back()->withErrors([
            'station_code' => "Invalid station code or password. You have {$attemptsLeft} attempt(s) remaining."
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->storeUserLog($user?->user_id, 'logged out', $request);
        Auth::logout(); 

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Redirect based on user role
     */
    private function redirectBasedOnRole(string $role)
    {
        $messages = [
            'admin' => 'Welcome back, Admin!',
            'firefighter' => 'Welcome back, Firefighter!',
            'citizen' => 'Welcome back!',
            'chief' => 'Welcome back Chief!',
            'barangay' => 'Welcome back, Barangay Officer!',
            'barangay captain' => 'Welcome back, Barangay Captain!',
            'anonymous' => 'Welcome back!',
        ];

        $routes = [
             'admin' => 'admin.dashboard',
            'firefighter' => 'firefighter.dashboard',
            'citizen' => 'citizen.dashboard',
            'chief' => 'station-admin.dashboard',
            'station admin' => 'station-admin.dashboard',
            'barangay' => 'barangay.dashboard',
            'barangay captain' => 'barangay.dashboard',
            'dispatcher' => 'dispatcher.dashboard',
            'anonymous' => 'home',
        ];

        if ($role === 'citizen' && session()->pull('redirect_to_citizen_mobile_hub', false)) {
            return redirect()->route('citizen.mobile-hub')->with('success', $messages[$role]);
        }

        if (isset($routes[$role])) {
            return redirect()->route($routes[$role])->with('success', $messages[$role]);
        }

        // If role is invalid, logout
        Auth::logout();
        return redirect()->route('auth.login')->withErrors([
            'email' => 'Invalid user role. Please contact administrator.'
        ]);
    }

    /**
     * Check if account is locked
     */
    private function isAccountLocked(Request $request): bool
    {
        $lockoutTime = session('login_lockout_time');
        
        if ($lockoutTime && now()->lt($lockoutTime)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if station account is locked
     */
    private function isStationAccountLocked(Request $request): bool
    {
        $stationCode = $request->station_code;
        $lockoutTime = session('station_lockout_time_' . $stationCode);
        
        if ($lockoutTime && now()->lt($lockoutTime)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get login attempts count
     */
    private function getLoginAttempts(Request $request): int
    {
        $email = $request->email;
        return (int) session('login_attempts_' . $email, 0);
    }

    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts(Request $request): void
    {
        $email = $request->email;
        $attempts = $this->getLoginAttempts($request) + 1;
        
        session(['login_attempts_' . $email => $attempts]);
        
        // Lock account if max attempts reached
        if ($attempts >= $this->maxAttempts) {
            session(['login_lockout_time' => now()->addMinutes($this->lockoutMinutes)]);
        }
    }

    /**
     * Reset login attempts
     */
    private function resetLoginAttempts(Request $request): void
    {
        $email = $request->email;
        session()->forget('login_attempts_' . $email);
        session()->forget('login_lockout_time');
    }

    /**
     * Get station login attempts count
     */
    private function getStationLoginAttempts(Request $request): int
    {
        $stationCode = $request->station_code;
        return (int) session('station_attempts_' . $stationCode, 0);
    }

    /**
     * Increment station login attempts
     */
    private function incrementStationLoginAttempts(Request $request): void
    {
        $stationCode = $request->station_code;
        $attempts = $this->getStationLoginAttempts($request) + 1;
        
        session(['station_attempts_' . $stationCode => $attempts]);
        
        // Lock station account if max attempts reached
        if ($attempts >= $this->maxAttempts) {
            session(['station_lockout_time_' . $stationCode => now()->addMinutes($this->lockoutMinutes)]);
        }
    }

    /**
     * Reset station login attempts
     */
    private function resetStationLoginAttempts(Request $request): void
    {
        $stationCode = $request->station_code;
        session()->forget('station_attempts_' . $stationCode);
        session()->forget('station_lockout_time_' . $stationCode);
    }

    private function formatLockoutMessage($lockoutTime, string $subject = 'Your account'): string
    {
        $lockoutAt = $lockoutTime ? \Illuminate\Support\Carbon::parse($lockoutTime) : now()->addMinutes($this->lockoutMinutes);
        $secondsLeft = max(1, now()->diffInSeconds($lockoutAt, false));
        $minutesLeft = max(1, (int) ceil($secondsLeft / 60));
        $retryAt = $lockoutAt->timezone(config('app.timezone'))->format('g:i A');

        return "{$subject} is temporarily locked. Try again in {$minutesLeft} minute(s), around {$retryAt}.";
    }

    private function storeUserLog(?int $userId, string $action, Request $request): void
    {
        if (!$userId || !Schema::hasTable('user_logs')) {
            return;
        }

        try {
            UserLog::create([
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function generateAccountCode(string $roleName): string
    {
        $roleCode = $this->roleCodes[strtolower($roleName)] ?? 'USR';

        $lastUser = User::where('account_code', 'LIKE', $roleCode . '-%')
            ->orderByDesc('user_id')
            ->first();

        $nextNumber = 1;

        if ($lastUser?->account_code) {
            $nextNumber = ((int) substr($lastUser->account_code, strlen($roleCode) + 1)) + 1;
        }

        return $roleCode . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function splitDisplayName(string $displayName): array
    {
        $parts = preg_split('/\s+/', trim($displayName)) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if (count($parts) === 0) {
            return ['Google', '', 'User'];
        }

        if (count($parts) === 1) {
            return [$parts[0], '', 'User'];
        }

        if (count($parts) === 2) {
            return [$parts[0], '', $parts[1]];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = implode(' ', $parts);

        return [$firstName, $middleName, $lastName];
    }

    private function generatePlaceholderContactNumber(): string
    {
        do {
            $number = '09' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (User::where('contact_number', $number)->exists());

        return $number;
    }

    private function generateRandomPassword(): string
    {
        return 'GoogleAuth!' . random_int(100000, 999999);
    }

    private function applyPostLoginAction(?string $postLoginAction, string $role): void
    {
        if ($postLoginAction === 'citizen-mobile-hub' && $role === 'citizen') {
            session([
                'redirect_to_citizen_mobile_hub' => true,
            ]);

            return;
        }

        if (in_array($postLoginAction, ['quick-report', 'citizen-mobile-hub'], true) && $role === 'citizen') {
            session([
                'redirect_to_citizen_mobile_hub' => true,
            ]);

            return;
        }

        if ($postLoginAction === 'quick-report-legacy' && $role === 'citizen') {
            session([
                'open_section' => 'overview',
                'open_incident_modal' => true,
            ]);
        }
    }

    private function consumePostLoginAction(Request $request): ?string
    {
        $fromSession = $request->session()->pull('post_login_action');
        if (! empty($fromSession)) {
            return $fromSession;
        }

        return $this->readPostLoginActionCookie($request);
    }

    private function readPostLoginActionCookie(Request $request): ?string
    {
        $value = $request->cookie('post_login_action');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function makePostLoginActionCookie(?string $postLoginAction)
    {
        return cookie(
            'post_login_action',
            (string) ($postLoginAction ?? ''),
            15,
            null,
            null,
            false,
            false,
            false,
            'lax'
        );
    }

    private function forgetPostLoginActionCookie()
    {
        return Cookie::forget('post_login_action');
    }

    private function sendGoogleVerificationOtp(User $user, Request $request): OtpRequest
    {
        $recentRequests = OtpRequest::where('email', $user->email)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests >= 3) {
            throw ValidationException::withMessages([
                'email' => 'Too many verification requests. Please wait a minute before trying again.',
            ]);
        }

        $otpCode = $this->generateOtpCode();
        $requestId = 'google-email-otp-' . uniqid();

        Mail::raw(
            "Your PyroSense verification code is {$otpCode}. This code will expire in 10 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('PyroSense Google Sign-In Verification Code');
            }
        );

        $otp = OtpRequest::create([
            'user_id' => $user->user_id,
            'phone' => (string) ($user->contact_number ?? 'N/A'),
            'email' => $user->email,
            'request_id' => $requestId,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $postLoginAction = $request->session()->get('post_login_action') ?: $this->readPostLoginActionCookie($request);

        if (! empty($postLoginAction)) {
            $request->session()->put('post_login_action', $postLoginAction);
        }

        $request->session()->put('pending_google_verification', [
            'user_id' => $user->user_id,
            'otp_request_id' => $otp->id,
            'email' => $user->email,
            'post_login_action' => $postLoginAction,
        ]);

        return $otp;
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function findRecoveryUserByIdentifier(string $identifier, string $searchMode = 'identity'): ?User
    {
        if ($searchMode === 'mobile') {
            $digits = preg_replace('/\D+/', '', $identifier);

            return User::query()
                ->where('contact_number', $digits)
                ->orWhere('contact_number', $identifier)
                ->first();
        }

        $normalized = preg_replace('/\s+/', ' ', trim(mb_strtolower($identifier)));

        if (str_contains($normalized, '@')) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [$normalized])
                ->first();
        }

        return User::query()
            ->whereRaw('LOWER(first_name) = ?', [$normalized])
            ->orWhereRaw('LOWER(last_name) = ?', [$normalized])
            ->orWhereRaw(
                "LOWER(TRIM(CONCAT_WS(' ', COALESCE(first_name, ''), COALESCE(last_name, '')))) = ?",
                [$normalized]
            )
            ->orWhereRaw(
                "LOWER(TRIM(CONCAT_WS(' ', COALESCE(first_name, ''), COALESCE(middle_name, ''), COALESCE(last_name, '')))) = ?",
                [$normalized]
            )
            ->first();
    }

    private function buildRecoveryChannels(User $user): array
    {
        $channels = [];

        if ($user->contact_number) {
            $channels[] = [
                'type' => 'phone',
                'label' => 'Mobile number',
                'masked' => $this->maskPhone((string) $user->contact_number),
            ];
        }

        if ($user->email) {
            $channels[] = [
                'type' => 'email',
                'label' => $user->google_id ? 'Google account' : 'Email account',
                'masked' => $this->maskRecoveryEmail((string) $user->email),
            ];
        }

        return $channels;
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, 2);

        return $visible . str_repeat('*', max(2, mb_strlen($name) - 2)) . '@' . $domain;
    }

    private function maskRecoveryEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$name] = explode('@', $email, 2);
        $nameLength = mb_strlen($name);

        if ($nameLength <= 2) {
            return mb_substr($name, 0, 1) . str_repeat('*', max(1, $nameLength - 1));
        }

        return mb_substr($name, 0, 1)
            . str_repeat('*', max(6, $nameLength - 2))
            . mb_substr($name, -1);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return $phone;
        }

        return substr($digits, 0, 3) . str_repeat('*', max(3, strlen($digits) - 3));
    }
}
