<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        return view('auth.login');
    }

    /**
     * Handle login authentication with session-based attempt tracking
     */
    public function authenticate(Request $request)
    {
        // Check if account is locked
        if ($this->isAccountLocked($request)) {
            $lockoutTime = session('login_lockout_time');
            $minutesLeft = now()->diffInMinutes($lockoutTime);
            
            throw ValidationException::withMessages([
                'email' => "Too many failed attempts. Your account is locked for {$minutesLeft} minutes."
            ]);
        }

        // Validate credentials
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
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
            $this->storeUserLog($user?->user_id, 'logged in', $request);
            $role = strtolower($user->role->role ?? '');

            if ($postLoginAction === 'quick-report' && $role === 'citizen') {
                session([
                    'open_section' => 'overview',
                    'open_incident_modal' => true,
                ]);
            }

            return $this->redirectBasedOnRole($role);
        }

        // Failed login - increment attempts
        $this->incrementLoginAttempts($request);
        
        $attemptsLeft = $this->maxAttempts - $this->getLoginAttempts($request);
        
        throw ValidationException::withMessages([
            'email' => "Invalid email or password. You have {$attemptsLeft} attempt(s) remaining."
        ]);
    }

    /**
     * Redirect the user to Google for authentication.
     */
    public function redirectToGoogle(Request $request)
    {
        if ($request->filled('post_login_action')) {
            $request->session()->put('post_login_action', $request->input('post_login_action'));
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
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
                'password' => Hash::make($this->generateRandomPassword()),
                'image' => $googleUser->getAvatar(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
                'image' => $googleUser->getAvatar() ?: $user->image,
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->storeUserLog($user->user_id, 'logged in with Google', $request);

        $postLoginAction = $request->session()->pull('post_login_action');
        $role = strtolower($user->role->role ?? '');

        if ($postLoginAction === 'quick-report' && $role === 'citizen') {
            session([
                'open_section' => 'overview',
                'open_incident_modal' => true,
            ]);
        }

        return $this->redirectBasedOnRole($role);
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
            $minutesLeft = now()->diffInMinutes($lockoutTime);
            
            return back()->withErrors([
                'station_code' => "Too many failed attempts. Station account is locked for {$minutesLeft} minutes."
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
        ];

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
}
