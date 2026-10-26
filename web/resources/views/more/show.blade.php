@extends('layouts.app')

@section('title', 'More - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10"><h1 class="customer-page-title">More</h1><p class="customer-page-copy">About QueueFlow and help</p></div></header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            <section class="divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white" aria-label="QueueFlow information">
                <x-customer-menu-row label="About QueueFlow" icon="info-circle" :href="route('more.information', 'about')" />
                <x-customer-menu-row label="How it works" icon="route" :href="route('more.information', 'how-it-works')" />
                <x-customer-menu-row label="FAQ" icon="message-question" :href="route('more.information', 'faq')" />
                <x-customer-menu-row label="Terms and Conditions" icon="document-text" :href="route('more.information', 'terms')" />
                <x-customer-menu-row label="Privacy Policy" icon="shield-lock" :href="route('more.information', 'privacy')" />
            </section>

            <section class="customer-ticket-card mt-4" aria-labelledby="help-heading"><div class="relative z-10"><h2 id="help-heading" class="text-xl font-bold">Need help?</h2><p class="mt-2 max-w-xs text-sm leading-5 text-white/65">Read <a class="font-semibold underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-customer-yellow" href="{{ route('more.information', 'help') }}">Help and Support</a> for answers. Support contact details are not available in this preview.</p><button class="mt-5 inline-flex min-h-11 cursor-not-allowed items-center rounded-full bg-customer-yellow px-5 text-sm font-bold text-customer-navy" type="button" aria-disabled="true" disabled>Contact us</button></div></section>
            <p class="mt-5 text-center text-xs text-customer-muted">QueueFlow &middot; Version {{ $more['version'] }}</p>
        </main>
    </div>
@endsection
