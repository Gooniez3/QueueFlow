@extends('layouts.staff-auth')

@section('title', 'Staff sign in - QueueFlow')
@section('auth-heading', 'Welcome back.')
@section('auth-description', 'Sign in to continue managing your QueueFlow businesses, branches, and daily operations.')

@section('auth-form')
    <div>
        <h2 class="text-xl font-semibold tracking-[-0.025em]">Sign in</h2>
        <p class="mt-2 text-sm leading-6 text-muted">Use the staff credentials connected to your QueueFlow account.</p>
    </div>

    <form class="mt-7 space-y-5" method="POST" action="{{ route('staff.login.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-semibold" for="email">Email address</label>
            <input
                class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition placeholder:text-muted/65 focus:border-brand focus:ring-3 focus:ring-brand/10 @error('email') border-red-700 @else border-line @enderror"
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
                <p class="mt-2 text-sm text-red-800" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold" for="password">Password</label>
            <input
                class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-base text-ink outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10 @error('password') border-red-700 @else border-line @enderror"
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
            >
            @error('password')
                <p class="mt-2 text-sm text-red-800" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="flex w-full items-center justify-center rounded-xl bg-brand px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" type="submit">
            Sign in
        </button>
    </form>

    <p class="mt-7 border-t border-line pt-6 text-center text-sm text-muted">
        New to QueueFlow?
        <a class="font-semibold text-brand underline decoration-brand/30 underline-offset-4 hover:decoration-brand" href="{{ route('staff.register') }}">Create a staff account</a>
    </p>
@endsection
