@extends('layouts.staff')

@section('title', 'Add branch')

@section('staff-content')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-brand" href="{{ route('staff.businesses.show', $business->id) }}">&larr; {{ $business->name }}</a>
        <p class="mt-8 text-xs font-semibold tracking-[0.18em] text-brand">NEW BRANCH</p>
        <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">Add a branch</h1>

        <form class="mt-8 grid gap-6 rounded-2xl border border-line bg-surface p-6 sm:p-8" method="POST" action="{{ route('staff.branches.store', $business->id) }}">
            @csrf

            @error('branch') <p class="border-l-3 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">{{ $message }}</p> @enderror

            <label class="grid gap-2 text-sm font-semibold" for="name">Branch name
                <input class="rounded-xl border border-line px-4 py-3 font-normal outline-none focus:border-brand focus:ring-3 focus:ring-brand/10" id="name" name="name" type="text" maxlength="150" required value="{{ old('name') }}">
                @error('name') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
            </label>

            <label class="grid gap-2 text-sm font-semibold" for="address">Address
                <textarea class="min-h-24 rounded-xl border border-line px-4 py-3 font-normal outline-none focus:border-brand focus:ring-3 focus:ring-brand/10" id="address" name="address" required>{{ old('address') }}</textarea>
                @error('address') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold" for="latitude">Latitude <span class="font-normal text-muted">(optional)</span>
                    <input class="rounded-xl border border-line px-4 py-3 font-normal outline-none focus:border-brand focus:ring-3 focus:ring-brand/10" id="latitude" name="latitude" type="number" min="-90" max="90" step="any" value="{{ old('latitude') }}">
                    @error('latitude') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-semibold" for="longitude">Longitude <span class="font-normal text-muted">(optional)</span>
                    <input class="rounded-xl border border-line px-4 py-3 font-normal outline-none focus:border-brand focus:ring-3 focus:ring-brand/10" id="longitude" name="longitude" type="number" min="-180" max="180" step="any" value="{{ old('longitude') }}">
                    @error('longitude') <span class="font-normal text-red-800">{{ $message }}</span> @enderror
                </label>
            </div>

            <button class="justify-self-start rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" type="submit">Create branch</button>
        </form>
    </div>
@endsection
