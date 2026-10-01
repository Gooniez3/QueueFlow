@extends('layouts.staff')

@section('title', 'Staff workspace')

@section('staff-content')
    <section class="max-w-3xl">
        <p class="text-xs font-semibold tracking-[0.18em] text-brand">STAFF WORKSPACE</p>
        <h1 class="mt-4 font-editorial text-4xl tracking-[-0.035em] sm:text-5xl">Signed in successfully.</h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-muted">
            Manage the businesses, branches, and services connected to your QueueFlow memberships.
        </p>

        <a class="mt-8 inline-flex items-center justify-center rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" href="{{ route('staff.businesses.index') }}">
            Manage businesses
        </a>
    </section>
@endsection
