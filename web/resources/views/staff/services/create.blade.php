@extends('layouts.staff')

@section('title', 'Add service')

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.branches.show', [$business->id, $branch->id])"
            :back-label="'Back to '.$branch->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
                ['label' => 'Add service'],
            ]"
        />

        <header class="mt-6">
            <p class="staff-eyebrow">NEW SERVICE</p>
            <h1 class="staff-page-title">Add a service</h1>
            <p class="staff-page-copy">Define a service available at {{ $branch->name }}, including the typical time needed to provide it.</p>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.services.store', [$business->id, $branch->id]) }}">
            @csrf

            @error('service')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <p class="staff-field-help">All fields are required unless marked optional.</p>

            <div class="staff-field">
                <label class="staff-field-label" for="name">Service name</label>
                <input class="staff-field-control @error('name') staff-field-control-error @enderror" id="name" name="name" type="text" maxlength="150" required autofocus value="{{ old('name') }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="description">Description <span class="staff-field-optional">(optional)</span></label>
                <textarea class="staff-field-control min-h-24 resize-y @error('description') staff-field-control-error @enderror" id="description" name="description" aria-describedby="description-help @error('description') description-error @enderror">{{ old('description') }}</textarea>
                <p class="staff-field-help" id="description-help">Add a concise explanation staff can use to identify this service.</p>
                @error('description')
                    <p class="staff-field-error" id="description-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field max-w-xs">
                <label class="staff-field-label" for="durationMinutes">Duration</label>
                <div class="flex rounded-xl border border-line bg-white transition hover:border-brand/35 focus-within:border-brand focus-within:ring-3 focus-within:ring-brand/10 @error('durationMinutes') border-red-700 focus-within:border-red-700 focus-within:ring-red-700/10 @enderror">
                    <input class="min-w-0 flex-1 rounded-l-xl bg-transparent px-4 py-3 text-base text-ink outline-none" id="durationMinutes" name="durationMinutes" type="number" min="1" step="1" inputmode="numeric" required value="{{ old('durationMinutes') }}" aria-describedby="duration-unit @error('durationMinutes') durationMinutes-error @enderror" @error('durationMinutes') aria-invalid="true" @enderror>
                    <span class="flex items-center border-l border-line px-4 text-sm text-muted" id="duration-unit">minutes</span>
                </div>
                @error('durationMinutes')
                    <p class="staff-field-error" id="durationMinutes-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Create service</button>
            </div>
        </form>
    </div>
@endsection
