@extends('layouts.app')

@section('body-class', 'bg-staff-canvas text-staff-ink')

@section('content')
    <div class="staff-auth-shell">
        <aside class="staff-auth-brand-panel">
            <a class="staff-auth-brand" href="{{ route('home') }}" aria-label="QueueFlow home">
                <span class="staff-brand-mark"><x-staff.icon class="size-5.5" name="clock" /></span>
                <span>
                    <span class="staff-brand-name">QueueFlow</span>
                    <span class="staff-brand-caption">STAFF PORTAL</span>
                </span>
            </a>

            <div class="staff-auth-introduction">
                <p class="staff-auth-eyebrow">QUEUEFLOW FOR STAFF</p>
                <h1 id="staff-auth-heading" class="staff-auth-heading">@yield('auth-heading')</h1>
                <p class="staff-auth-description">@yield('auth-description')</p>
            </div>

            <p class="staff-auth-security-note">Secure access for managing QueueFlow businesses, branches, services, and daily operations.</p>
        </aside>

        <div class="staff-auth-form-panel">
            <div class="staff-auth-mobile-brand" aria-hidden="true">
                <span class="staff-brand-mark"><x-staff.icon name="clock" /></span>
                <span class="font-staff-display text-lg font-extrabold">QueueFlow</span>
                <span class="staff-auth-mobile-caption">STAFF PORTAL</span>
            </div>

            <section class="staff-auth-card" aria-labelledby="staff-auth-heading">
                @if (session('status'))
                    <div class="staff-alert-success mb-6" role="status">{{ session('status') }}</div>
                @endif

                @error('authentication')
                    <div class="staff-auth-error" role="alert">{{ $message }}</div>
                @enderror

                @error('registration')
                    <div class="staff-auth-error" role="alert">{{ $message }}</div>
                @enderror

                @yield('auth-form')

                <p class="mt-6 text-center text-xs leading-5 text-staff-muted">QueueFlow&rsquo;s secure staff experience keeps business access scoped to your memberships.</p>
            </section>
        </div>
    </div>
@endsection
