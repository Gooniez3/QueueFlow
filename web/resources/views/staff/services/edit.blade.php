@extends('layouts.staff')

@section('title', 'Edit service')
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[
        ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
        ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
        ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
        ['label' => $service->name, 'url' => route('staff.services.show', [$business->id, $branch->id, $service->id])],
        ['label' => 'Edit service'],
    ]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.services.show', [$business->id, $branch->id, $service->id])"
            :back-label="'Back to '.$service->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
                ['label' => $service->name, 'url' => route('staff.services.show', [$business->id, $branch->id, $service->id])],
                ['label' => 'Edit service'],
            ]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="edit" /></span>
            <div>
            <p class="staff-eyebrow">SERVICE SETTINGS</p>
            <h1 class="staff-page-title">Edit service</h1>
            <p class="staff-page-copy">Update the service details and availability for {{ $branch->name }}.</p>
            </div>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.services.update', [$business->id, $branch->id, $service->id]) }}">
            @csrf
            @method('PUT')

            @error('service')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="staff-field">
                <label class="staff-field-label" for="name">Service name</label>
                <input class="staff-field-control @error('name') staff-field-control-error @enderror" id="name" name="name" type="text" maxlength="150" required autofocus value="{{ old('name', $service->name) }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="description">Description <span class="staff-field-optional">(optional)</span></label>
                <textarea class="staff-field-control min-h-24 resize-y @error('description') staff-field-control-error @enderror" id="description" name="description" aria-describedby="description-help @error('description') description-error @enderror">{{ old('description', $service->description) }}</textarea>
                <p class="staff-field-help" id="description-help">Add a concise explanation staff can use to identify this service.</p>
                @error('description')
                    <p class="staff-field-error" id="description-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="staff-field">
                    <label class="staff-field-label" for="durationMinutes">Duration</label>
                    <div class="flex rounded-xl border border-staff-line bg-white transition hover:border-staff-indigo/35 focus-within:border-staff-indigo focus-within:ring-3 focus-within:ring-staff-indigo/10 @error('durationMinutes') border-red-700 focus-within:border-red-700 focus-within:ring-red-700/10 @enderror">
                        <input class="min-w-0 flex-1 rounded-l-xl bg-transparent px-4 py-3 text-base text-staff-ink outline-none" id="durationMinutes" name="durationMinutes" type="number" min="1" step="1" inputmode="numeric" required value="{{ old('durationMinutes', $service->durationMinutes) }}" aria-describedby="duration-unit @error('durationMinutes') durationMinutes-error @enderror" @error('durationMinutes') aria-invalid="true" @enderror>
                        <span class="flex items-center border-l border-staff-line px-4 text-sm text-staff-muted" id="duration-unit">minutes</span>
                    </div>
                    @error('durationMinutes')
                        <p class="staff-field-error" id="durationMinutes-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="staff-field">
                    <label class="staff-field-label" for="active">Status</label>
                    <select class="staff-field-control @error('active') staff-field-control-error @enderror" id="active" name="active" required @error('active') aria-invalid="true" aria-describedby="active-error" @enderror>
                        <option value="1" @selected((string) old('active', $service->active ? '1' : '0') === '1')>Active</option>
                        <option value="0" @selected((string) old('active', $service->active ? '1' : '0') === '0')>Inactive</option>
                    </select>
                    @error('active')
                        <p class="staff-field-error" id="active-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-staff-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.services.show', [$business->id, $branch->id, $service->id]) }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endsection
