@extends('layouts.staff')

@section('title', 'Create business')

@section('staff-content')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-brand" href="{{ route('staff.businesses.index') }}">&larr; Your businesses</a>
        <p class="mt-8 text-xs font-semibold tracking-[0.18em] text-brand">NEW BUSINESS</p>
        <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">Create a business</h1>
        <p class="mt-3 text-sm leading-6 text-muted">QueueFlow will create the business and establish your owner membership in one secure operation.</p>

        <form class="mt-8 grid gap-6 rounded-2xl border border-line bg-surface p-6 sm:p-8" method="POST" action="{{ route('staff.businesses.store') }}">
            @csrf

            @error('business')
                <p class="border-l-3 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">{{ $message }}</p>
            @enderror

            <label class="grid gap-2 text-sm font-semibold" for="name">
                Business name
                <input class="rounded-xl border border-line bg-white px-4 py-3 font-normal outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10" id="name" name="name" type="text" maxlength="150" required value="{{ old('name') }}">
                @error('name') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
            </label>

            <label class="grid gap-2 text-sm font-semibold" for="description">
                Description <span class="font-normal text-muted">(optional)</span>
                <textarea class="min-h-28 rounded-xl border border-line bg-white px-4 py-3 font-normal outline-none transition focus:border-brand focus:ring-3 focus:ring-brand/10" id="description" name="description">{{ old('description') }}</textarea>
                @error('description') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
            </label>

            <button class="justify-self-start rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" type="submit">Create business</button>
        </form>
    </div>
@endsection
