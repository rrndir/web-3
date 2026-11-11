<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('v_customer.auth.login', [
            'redirect' => $this->redirectPath($request->query('redirect')),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials + ['role' => '2', 'status' => 1])) {
            return back()
                ->withInput($request->only('email', 'redirect'))
                ->with('error', 'Email atau password salah, atau akun tidak aktif.');
        }

        $request->session()->regenerate();

        return redirect()->to($this->redirectPath($request->input('redirect')));
    }

    public function showRegistration(Request $request)
    {
        return view('v_customer.auth.register', [
            'redirect' => $this->redirectPath($request->query('redirect')),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'nama' => $validated['nama'],
                'email' => $validated['email'],
                'role' => '2',
                'status' => 1,
                'password' => Hash::make($validated['password']),
            ]);

            Customer::create(['user_id' => $user->id]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->redirectPath($request->input('redirect')));
    }

    private function redirectPath(mixed $path): string
    {
        if (
            is_string($path)
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && !preg_match('/[\r\n]/', $path)
        ) {
            return $path;
        }

        return route('order.cart');
    }
}
