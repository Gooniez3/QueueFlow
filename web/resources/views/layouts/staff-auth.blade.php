@extends('layouts.app')

@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="min-h-screen">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-6 sm:py-7 lg:px-8">
            <a class="inline-flex items-center gap-3 text-ink" href="{{ route('home') }}" aria-label="QueueFlow home">
                <span class="grid size-9 place-items-center rounded-lg bg-brand text-sm font-bold text-white">Q</span>
                <span class="text-lg font-semibold tracking-[-0.02em]">QueueFlow</span>
            </a>

            <span class="text-xs font-medium tracking-[0.08em] text-muted">STAFF ACCESS</span>
        </header>

        <main class="mx-auto grid max-w-6xl gap-10 px-5 pt-8 pb-12 sm:px-6 sm:pt-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(25rem,0.72fr)] lg:items-center lg:gap-20 lg:px-8 lg:pt-16">
            <section class="max-w-xl" aria-labelledby="staff-auth-heading">
                <p class="text-xs font-semibold tracking-[0.2em] text-brand">QUEUEFLOW FOR STAFF</p>
                <h1 id="staff-auth-heading" class="mt-5 font-editorial text-4xl leading-[1.05] tracking-[-0.035em] sm:text-5xl">
                    @yield('auth-heading')
                </h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-muted">
                    @yield('auth-description')
                </p>

                <div class="mt-8 border-l-2 border-brand/20 pl-5 text-sm leading-6 text-muted">
                    Manage queue operations through QueueFlow&rsquo;s secure staff experience. Business permissions remain scoped to each membership.
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-[0_24px_70px_-50px_rgba(7,23,19,0.45)] sm:p-7 lg:p-8">
                @if (session('status'))
                    <div class="mb-6 border-l-3 border-brand bg-brand/5 px-4 py-3 text-sm leading-6 text-ink" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @error('authentication')
                    <div class="mb-6 border-l-3 border-red-700 bg-red-50 px-4 py-3 text-sm leading-6 text-red-900" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                @error('registration')
                    <div class="mb-6 border-l-3 border-red-700 bg-red-50 px-4 py-3 text-sm leading-6 text-red-900" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                @yield('auth-form')
            </section>
        </main>
    </div>
@endsection
