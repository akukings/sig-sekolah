@extends('layouts.app')
@section('title', 'Ubah Kata Sandi')
@section('content')
<div class="max-w-xl mx-auto px-4 py-8">

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Ubah Kata Sandi
        </h1>
        <p class="text-gray-600 mb-6">
            Masukkan kata sandi lama dan kata sandi baru Anda.
        </p>
        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block font-medium mb-2">
                    Kata Sandi Lama
                </label>
                <input type="password" name="current_password" class="w-full border rounded-lg px-3 py-2"required>
                @error('current_password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">
                    Kata Sandi Baru
                </label>
                <input type="password" name="password" class="w-full border rounded-lg px-3 py-2"required>
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-2">
                    Konfirmasi Kata Sandi Baru
                </label>
                <input type="password" name="password_confirmation" class="w-full border rounded-lg px-3 py-2"required>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg cursor-pointer">
                    Simpan Kata Sandi
                </button>

                <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-800">
                    Kembali
                </a>
            </div>
        </form>

    </div>
</div>
@endsection
