@extends('layouts.staff-auth')

@section('title', 'Create staff account - QueueFlow')
@section('auth-heading', 'Start with your staff account.')
@section('auth-description', 'Create your QueueFlow identity now. Business and branch access will remain governed by your Spring-backed staff memberships.')

@section('auth-form')
    <div>
        <h2 class="text-xl font-semibold tracking-[-0.025em]">Create account</h2>
        <p class="mt-2 text-sm leading-6 text-muted">Enter your details exactly as you want them associated with QueueFlow.</p>
    </div>

    <form class="mt-7 space-y-5" method="POST" action="{{ route('staff.register.store') }}">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold" for="firstName">First name</label>
                <input class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10 @error('firstName') border-red-700 @else border-line @enderror" id="firstName" name="firstName" type="text" value="{{ old('firstName') }}" autocomplete="given-name" maxlength="100" required autofocus @error('firstName') aria-invalid="true" aria-describedby="firstName-error" @enderror>
                @error('firstName')
                    <p class="mt-2 text-sm text-red-800" id="firstName-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold" for="lastName">Last name</label>
                <input class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10 @error('lastName') border-red-700 @else border-line @enderror" id="lastName" name="lastName" type="text" value="{{ old('lastName') }}" autocomplete="family-name" maxlength="100" required @error('lastName') aria-invalid="true" aria-describedby="lastName-error" @enderror>
                @error('lastName')
                    <p class="mt-2 text-sm text-red-800" id="lastName-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold" for="email">Email address</label>
            <input class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition placeholder:text-muted/65 focus:border-brand focus:ring-3 focus:ring-brand/10 @error('email') border-red-700 @else border-line @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p class="mt-2 text-sm text-red-800" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold" for="phone">Phone <span class="font-normal text-muted">(optional)</span></label>
            <input class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10 @error('phone') border-red-700 @else border-line @enderror" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="50" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
            @error('phone')
                <p class="mt-2 text-sm text-red-800" id="phone-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold" for="password">Password</label>
            <input class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10 @error('password') border-red-700 @else border-line @enderror" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            <p class="mt-2 text-xs leading-5 text-muted" id="password-help">Use between 8 and 72 characters.</p>
            @error('password')
                <p class="mt-2 text-sm text-red-800" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="flex w-full items-center justify-center rounded-xl bg-brand px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" type="submit">
            Create staff account
        </button>
    </form>

    <p class="mt-7 border-t border-line pt-6 text-center text-sm text-muted">
        Already have an account?
        <a class="font-semibold text-brand underline decoration-brand/30 underline-offset-4 hover:decoration-brand" href="{{ route('staff.login') }}">Sign in</a>
    </p>
@endsection
