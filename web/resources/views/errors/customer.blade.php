@extends('layouts.app')

@section('title', $title.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page customer-page-narrow">
        <x-customer-navigation />

        <header class="customer-hero pb-16">
            <div class="relative z-10">
                <p class="text-sm font-semibold text-white/75">QueueFlow</p>
                <h1 class="mt-3 text-[2rem] leading-[1.03] font-bold tracking-[-0.045em]">We couldn&rsquo;t open that.</h1>
            </div>
        </header>

        <section class="relative z-10 -mt-7 px-5 pb-7" aria-labelledby="customer-error-heading">
            <div class="customer-surface text-center">
                <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-customer-yellow text-2xl font-bold" aria-hidden="true">!</span>
                <h2 id="customer-error-heading" class="mt-5 text-2xl font-bold tracking-[-0.035em]">{{ $title }}</h2>
                <p class="mt-3 text-sm leading-5 text-customer-muted">{{ $message }}</p>
                <a class="customer-secondary-button mt-6" href="{{ $backUrl }}">{{ $backLabel }}</a>
            </div>
        </section>
    </div>
@endsection
