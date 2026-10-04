@extends('layouts.staff-auth')

@section('title', 'Staff sign in - QueueFlow')
@section('auth-heading', 'Welcome back.')
@section('auth-description', 'Sign in to continue managing your QueueFlow businesses, branches, and daily operations.')

@section('auth-form')
    <div>
        <p class="staff-eyebrow">STAFF ACCESS</p>
        <h2 class="mt-2 font-staff-display text-2xl font-extrabold tracking-[-0.03em]">Sign in</h2>
        <p class="mt-2 text-sm leading-6 text-staff-muted">Use the staff credentials connected to your QueueFlow account.</p>
    </div>

    <form class="mt-7 space-y-5" method="POST" action="{{ route('staff.login.store') }}">
        @csrf

        <div>
            <label class="staff-form-label" for="email">Email address</label>
            <input
                class="staff-form-input @error('email') staff-form-input-error @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                inputmode="email"
                required
                autofocus
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
            @error('email')
                <p class="staff-form-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="staff-form-label" for="password">Password</label>
            <input
                class="staff-form-input @error('password') staff-form-input-error @enderror"
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
            >
            @error('password')
                <p class="staff-form-error" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="staff-primary-button min-h-12 w-full" type="submit">
            Sign in
        </button>
    </form>

    <p class="mt-7 border-t border-staff-line pt-6 text-center text-sm text-staff-muted">
        New to QueueFlow?
        <a class="font-bold text-staff-indigo underline decoration-staff-indigo/25 underline-offset-4 hover:decoration-staff-indigo" href="{{ route('staff.register') }}">Create a staff account</a>
    </p>
@endsection
