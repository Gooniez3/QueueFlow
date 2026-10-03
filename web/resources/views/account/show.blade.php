@extends('layouts.app')

@section('title', 'Account - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10"><h1 class="customer-page-title">Account</h1></div></header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            <section class="rounded-[1.6rem] bg-white p-5 shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)]" aria-labelledby="guest-heading">
                <div class="flex items-center gap-4"><span class="grid size-16 place-items-center rounded-full border-4 border-customer-yellow bg-customer-canvas text-customer-indigo"><svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3" /><path d="M5.5 20c.5-4 2.67-6 6.5-6s6 2 6.5 6" /></svg></span><div><h1 id="guest-heading" class="text-xl font-bold">Guest</h1><p class="text-sm text-customer-muted">No account needed</p></div></div>
                <div class="mt-4 flex gap-3 rounded-2xl bg-customer-canvas p-4 text-sm text-customer-muted"><svg class="size-5 shrink-0 text-customer-indigo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 7 3v5c0 4.5-2.75 8-7 10-4.25-2-7-5.5-7-10V6l7-3Z" /><path d="m9 12 2 2 4-5" /></svg><p>Your tickets and favourites are saved on this device only.</p></div>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-2xl bg-customer-canvas p-3"><dd class="text-xl font-bold">{{ $account['queuesJoined'] }}</dd><dt class="text-[0.62rem] text-customer-muted">Queues joined</dt></div><div class="rounded-2xl bg-customer-canvas p-3"><dd class="text-xl font-bold">{{ $account['favourites'] }}</dd><dt class="text-[0.62rem] text-customer-muted">Favourites</dt></div><div class="rounded-2xl bg-customer-canvas p-3"><dd class="text-xl font-bold">{{ $account['served'] }}</dd><dt class="text-[0.62rem] text-customer-muted">Served</dt></div></dl>
                <p class="mt-3 text-center text-[0.65rem] text-customer-muted">Statistics are demonstration presentation values.</p>
            </section>

            <section class="mt-4 divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white" aria-label="Account options">
                <x-customer-menu-row label="Favourite places" icon="heart" />
                <x-customer-menu-row label="Queue history" icon="history" />
                <x-customer-menu-row label="Notification settings" icon="bell" />
            </section>
            <section class="mt-4 divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white" aria-label="Support options">
                <x-customer-menu-row label="Language" icon="globe" :value="$account['language']" />
                <x-customer-menu-row label="Help and support" icon="help-circle" />
                <x-customer-menu-row label="Clear data on this device" icon="trash" danger />
            </section>
            <p class="mt-5 text-center text-xs text-customer-muted">QueueFlow &middot; Version {{ $account['version'] }}</p>
        </main>
    </div>
@endsection
