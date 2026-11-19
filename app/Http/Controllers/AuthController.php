<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('v2.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Ensure the configured Main Admin and Editor accounts exist with their exact credentials
        $this->ensureConfiguredAccountsExist();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('v2.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function ensureConfiguredAccountsExist(): void
    {
        try {
            // Main Admin Login: kalabara@gmail.com / kalabara@edge
            $mainUser = User::where('email', 'kalabara@gmail.com')->first();
            if (!$mainUser) {
                User::create([
                    'name' => 'Kalabara Admin',
                    'email' => 'kalabara@gmail.com',
                    'password' => Hash::make('kalabara@edge'),
                ]);
            }

            // Editor Login: editor2026.kalabara@gmail.com / kalabara@2026
            $editorUser = User::where('email', 'editor2026.kalabara@gmail.com')->first();
            if (!$editorUser) {
                User::create([
                    'name' => 'Kalabara Editor',
                    'email' => 'editor2026.kalabara@gmail.com',
                    'password' => Hash::make('kalabara@2026'),
                ]);
            }

            // Volunteer Login: volunteer.kalabara@gmail.com / volunteer@2026 (QR Scanner & Seat Map Check only)
            $volunteerUser = User::where('email', 'volunteer.kalabara@gmail.com')->first();
            if (!$volunteerUser) {
                User::create([
                    'name' => 'Kalabara Volunteer',
                    'email' => 'volunteer.kalabara@gmail.com',
                    'password' => Hash::make('volunteer@2026'),
                ]);
            } else {
                $volunteerUser->password = Hash::make('volunteer@2026');
                $volunteerUser->save();
            }
        } catch (\Throwable $e) {
            // Ignore if DB is not migrated yet
        }
    }
}
