@extends('layouts.staff')

@section('title', 'Add branch')
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[
        ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
        ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
        ['label' => 'Add branch'],
    ]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.businesses.show', $business->id)"
            :back-label="'Back to '.$business->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => 'Add branch'],
            ]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="branch" /></span>
            <div>
            <p class="staff-eyebrow">NEW BRANCH</p>
            <h1 class="staff-page-title">Add a branch</h1>
            <p class="staff-page-copy">Create a location for {{ $business->name }}. Coordinates are optional and can be added when they are useful.</p>
            </div>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.branches.store', $business->id) }}">
            @csrf

            @error('branch')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <p class="staff-field-help">All fields are required unless marked optional.</p>

            <div class="staff-field">
                <label class="staff-field-label" for="name">Branch name</label>
                <input class="staff-field-control @error('name') staff-field-control-error @enderror" id="name" name="name" type="text" maxlength="150" autocomplete="organization" required autofocus value="{{ old('name') }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="address">Address</label>
                <textarea class="staff-field-control min-h-24 resize-y @error('address') staff-field-control-error @enderror" id="address" name="address" autocomplete="street-address" required @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>{{ old('address') }}</textarea>
                @error('address')
                    <p class="staff-field-error" id="address-error">{{ $message }}</p>
                @enderror
            </div>

            <fieldset class="border-t border-staff-line pt-6">
                <legend class="staff-field-label">Coordinates <span class="staff-field-optional">(optional)</span></legend>
                <p class="staff-field-help mt-2">Use decimal latitude and longitude only when the precise branch location is known.</p>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div class="staff-field">
                        <label class="staff-field-label" for="latitude">Latitude</label>
                        <input class="staff-field-control @error('latitude') staff-field-control-error @enderror" id="latitude" name="latitude" type="number" min="-90" max="90" step="any" inputmode="decimal" value="{{ old('latitude') }}" @error('latitude') aria-invalid="true" aria-describedby="latitude-error" @enderror>
                        @error('latitude')
                            <p class="staff-field-error" id="latitude-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="staff-field">
                        <label class="staff-field-label" for="longitude">Longitude</label>
                        <input class="staff-field-control @error('longitude') staff-field-control-error @enderror" id="longitude" name="longitude" type="number" min="-180" max="180" step="any" inputmode="decimal" value="{{ old('longitude') }}" @error('longitude') aria-invalid="true" aria-describedby="longitude-error" @enderror>
                        @error('longitude')
                            <p class="staff-field-error" id="longitude-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            <div class="flex flex-col-reverse gap-3 border-t border-staff-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.businesses.show', $business->id) }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Create branch</button>
            </div>
        </form>
    </div>
@endsection
