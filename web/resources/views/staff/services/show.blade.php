@extends('layouts.staff')

@section('title', $service->name)

@section('staff-content')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-brand" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">&larr; {{ $branch->name }}</a>

        <section class="mt-8 rounded-2xl border border-line bg-surface p-7 sm:p-9">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-brand">SERVICE</p>
                    <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">{{ $service->name }}</h1>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $service->active ? 'border-brand/20 bg-brand/5 text-brand' : 'border-line text-muted' }}">{{ $service->active ? 'Active' : 'Inactive' }}</span>
            </div>

            <dl class="mt-8 grid gap-6 border-t border-line pt-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold tracking-[0.12em] text-muted">DURATION</dt>
                    <dd class="mt-2 text-lg font-semibold">{{ $service->durationMinutes }} minutes</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-[0.12em] text-muted">BRANCH</dt>
                    <dd class="mt-2 text-lg font-semibold">{{ $branch->name }}</dd>
                </div>
            </dl>

            <div class="mt-7 border-t border-line pt-6">
                <h2 class="text-sm font-semibold">Description</h2>
                <p class="mt-2 text-sm leading-6 text-muted">{{ $service->description ?: 'No description provided.' }}</p>
            </div>
        </section>
    </div>
@endsection
