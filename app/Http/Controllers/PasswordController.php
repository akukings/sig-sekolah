<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'current_password.current_password' => 'Kata sandi lama tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with(
            'success',
            'Kata sandi berhasil diubah.'
        );
    }
}
