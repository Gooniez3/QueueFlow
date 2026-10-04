@extends('layouts.staff')

@section('title', 'Edit branch')
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[
        ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
        ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
        ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
        ['label' => 'Edit branch'],
    ]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.branches.show', [$business->id, $branch->id])"
            :back-label="'Back to '.$branch->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
                ['label' => 'Edit branch'],
            ]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="edit" /></span>
            <div>
            <p class="staff-eyebrow">BRANCH SETTINGS</p>
            <h1 class="staff-page-title">Edit branch</h1>
            <p class="staff-page-copy">Update the location details for {{ $branch->name }} without changing its parent business.</p>
            </div>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.branches.update', [$business->id, $branch->id]) }}">
            @csrf
            @method('PUT')

            @error('branch')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="staff-field">
                <label class="staff-field-label" for="name">Branch name</label>
                <input class="staff-field-control @error('name') staff-field-control-error @enderror" id="name" name="name" type="text" maxlength="150" autocomplete="organization" required autofocus value="{{ old('name', $branch->name) }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="address">Address</label>
                <textarea class="staff-field-control min-h-24 resize-y @error('address') staff-field-control-error @enderror" id="address" name="address" autocomplete="street-address" required @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>{{ old('address', $branch->address) }}</textarea>
                @error('address')
                    <p class="staff-field-error" id="address-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="timezone">Timezone</label>
                <input class="staff-field-control @error('timezone') staff-field-control-error @enderror" id="timezone" name="timezone" type="text" maxlength="100" required value="{{ old('timezone', $branch->timezone) }}" aria-describedby="timezone-help @error('timezone') timezone-error @enderror" @error('timezone') aria-invalid="true" @enderror>
                <p class="staff-field-help" id="timezone-help">Use a valid timezone such as Asia/Singapore.</p>
                @error('timezone')
                    <p class="staff-field-error" id="timezone-error">{{ $message }}</p>
                @enderror
            </div>

            <fieldset class="border-t border-staff-line pt-6">
                <legend class="staff-field-label">Coordinates <span class="staff-field-optional">(optional)</span></legend>
                <p class="staff-field-help mt-2">Use decimal latitude and longitude only when the precise branch location is known.</p>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div class="staff-field">
                        <label class="staff-field-label" for="latitude">Latitude</label>
                        <input class="staff-field-control @error('latitude') staff-field-control-error @enderror" id="latitude" name="latitude" type="number" min="-90" max="90" step="any" inputmode="decimal" value="{{ old('latitude', $branch->latitude) }}" @error('latitude') aria-invalid="true" aria-describedby="latitude-error" @enderror>
                        @error('latitude')
                            <p class="staff-field-error" id="latitude-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="staff-field">
                        <label class="staff-field-label" for="longitude">Longitude</label>
                        <input class="staff-field-control @error('longitude') staff-field-control-error @enderror" id="longitude" name="longitude" type="number" min="-180" max="180" step="any" inputmode="decimal" value="{{ old('longitude', $branch->longitude) }}" @error('longitude') aria-invalid="true" aria-describedby="longitude-error" @enderror>
                        @error('longitude')
                            <p class="staff-field-error" id="longitude-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            <div class="flex flex-col-reverse gap-3 border-t border-staff-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endsection
