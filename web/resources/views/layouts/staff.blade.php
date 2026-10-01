@extends('layouts.app')

@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="min-h-screen">
        <header class="border-b border-line bg-surface/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <a class="inline-flex items-center gap-3 text-ink" href="{{ route('staff.home') }}" aria-label="QueueFlow staff home">
                        <span class="grid size-9 place-items-center rounded-lg bg-brand text-sm font-bold text-white">Q</span>
                        <span class="text-lg font-semibold tracking-[-0.02em]">QueueFlow</span>
                    </a>

                    <nav class="hidden items-center gap-6 text-sm sm:flex" aria-label="Staff navigation">
                        <a class="font-medium text-muted transition hover:text-brand" href="{{ route('staff.businesses.index') }}">Businesses</a>
                    </nav>
                </div>

                <form method="POST" action="{{ route('staff.logout') }}">
                    @csrf
                    <button class="text-sm font-semibold text-muted transition hover:text-brand" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
            @if (session('status'))
                <div class="mb-8 border-l-3 border-brand bg-brand/5 px-4 py-3 text-sm leading-6 text-ink" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('staff-content')
        </main>
    </div>
@endsection
