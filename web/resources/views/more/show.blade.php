@extends('layouts.app')

@section('title', 'More - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10"><h1 class="customer-page-title">More</h1><p class="customer-page-copy">About QueueFlow and help</p></div></header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            <section class="divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white" aria-label="QueueFlow information">
                <x-customer-menu-row label="About QueueFlow" icon="info-circle" />
                <x-customer-menu-row label="How it works" icon="route" />
                <x-customer-menu-row label="FAQ" icon="message-question" />
                <x-customer-menu-row label="Terms and Conditions" icon="document-text" />
                <x-customer-menu-row label="Privacy Policy" icon="shield-lock" />
            </section>

            <section class="customer-ticket-card mt-4" aria-labelledby="help-heading"><div class="relative z-10"><h2 id="help-heading" class="text-xl font-bold">Need help?</h2><p class="mt-2 max-w-xs text-sm leading-5 text-white/65">Our team can help with your ticket or your place in the queue.</p><button class="mt-5 inline-flex min-h-11 cursor-not-allowed items-center rounded-full bg-customer-yellow px-5 text-sm font-bold text-customer-navy" type="button" aria-disabled="true">Contact us</button></div></section>
            <p class="mt-5 text-center text-xs text-customer-muted">QueueFlow &middot; Version {{ $more['version'] }}</p>
        </main>
    </div>
@endsection
