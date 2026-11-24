<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login form (supports both Guest / Tamu and Staff / Admin).
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $activeRole = $request->query('role', 'user'); // 'user' or 'staff'
        return view('auth.login', compact('activeRole'));
    }

    /**
     * Handle login submission.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Show guest registration form.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.register');
    }

    /**
     * Handle guest registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('chat')->with('success', 'Selamat datang di Aegora Assist, ' . $user->name . '! Akun tamu Anda telah aktif.');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    /**
     * Helper to route user according to role.
     */
    protected function redirectBasedOnRole(User $user)
    {
        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('chat'));
    }
}
