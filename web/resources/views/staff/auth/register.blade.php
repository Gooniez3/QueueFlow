@extends('layouts.staff-auth')

@section('title', 'Create staff account - QueueFlow')
@section('auth-heading', 'Start with your staff account.')
@section('auth-description', 'Create your QueueFlow identity now. Business and branch access will remain governed by your Spring-backed staff memberships.')

@section('auth-form')
    <div>
        <p class="staff-eyebrow">STAFF ACCESS</p>
        <h2 class="mt-2 font-staff-display text-2xl font-extrabold tracking-[-0.03em]">Create account</h2>
        <p class="mt-2 text-sm leading-6 text-staff-muted">Enter your details exactly as you want them associated with QueueFlow.</p>
    </div>

    <form class="mt-7 space-y-5" method="POST" action="{{ route('staff.register.store') }}">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="staff-form-label" for="firstName">First name</label>
                <input class="staff-form-input @error('firstName') staff-form-input-error @enderror" id="firstName" name="firstName" type="text" value="{{ old('firstName') }}" autocomplete="given-name" maxlength="100" required autofocus @error('firstName') aria-invalid="true" aria-describedby="firstName-error" @enderror>
                @error('firstName')
                    <p class="staff-form-error" id="firstName-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="staff-form-label" for="lastName">Last name</label>
                <input class="staff-form-input @error('lastName') staff-form-input-error @enderror" id="lastName" name="lastName" type="text" value="{{ old('lastName') }}" autocomplete="family-name" maxlength="100" required @error('lastName') aria-invalid="true" aria-describedby="lastName-error" @enderror>
                @error('lastName')
                    <p class="staff-form-error" id="lastName-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="staff-form-label" for="email">Email address</label>
            <input class="staff-form-input @error('email') staff-form-input-error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p class="staff-form-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="staff-form-label" for="phone">Phone <span class="font-normal text-staff-muted">(optional)</span></label>
            <input class="staff-form-input @error('phone') staff-form-input-error @enderror" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="50" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
            @error('phone')
                <p class="staff-form-error" id="phone-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="staff-form-label" for="password">Password</label>
            <input class="staff-form-input @error('password') staff-form-input-error @enderror" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            <p class="mt-2 text-xs leading-5 text-staff-muted" id="password-help">Use between 8 and 72 characters.</p>
            @error('password')
                <p class="staff-form-error" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="staff-primary-button min-h-12 w-full" type="submit">
            Create staff account
        </button>
    </form>

    <p class="mt-7 border-t border-staff-line pt-6 text-center text-sm text-staff-muted">
        Already have an account?
        <a class="font-bold text-staff-indigo underline decoration-staff-indigo/25 underline-offset-4 hover:decoration-staff-indigo" href="{{ route('staff.login') }}">Sign in</a>
    </p>
@endsection
