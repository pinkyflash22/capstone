<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StationLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:station')->except('logout');
    }

    // Show login form
    public function showLoginForm()
    {
        return view('auth.station-login');
    }

    // Handle login
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'station_id' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt login using the 'station' guard
        if (Auth::guard('station')->attempt([
            'station_id' => $request->station_id, 
            'password' => $request->password
        ], $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Success - update last login info
            $station = Auth::guard('station')->user();
            $station->last_login_at = now();
            $station->last_login_ip = $request->ip();
            $station->save();
            
            return redirect()->intended(route('station.dashboard'));
        }

        // Failed - track attempt
        $station = \App\Models\StationAccount::where('station_id', $request->station_id)->first();
        if ($station) {
            $station->increment('login_attempts');
            
            // Lock after 3 attempts
            if ($station->login_attempts >= 3) {
                $station->locked_until = now()->addMinutes(15);
                $station->save();
            }
        }

        throw ValidationException::withMessages([
            'station_id' => [trans('auth.failed')],
        ]);
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::guard('station')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
