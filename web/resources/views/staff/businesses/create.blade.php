@extends('layouts.staff')

@section('title', 'Create business')
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[
        ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
        ['label' => 'Create business'],
    ]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.businesses.index')"
            back-label="Back to businesses"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => 'Create business'],
            ]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="business" /></span>
            <div>
            <p class="staff-eyebrow">NEW BUSINESS</p>
            <h1 class="staff-page-title">Create a business</h1>
            <p class="staff-page-copy">Add the organization you manage. Your owner membership will be created securely with the business.</p>
            </div>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.businesses.store') }}">
            @csrf

            @error('business')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <p class="staff-field-help">All fields are required unless marked optional.</p>

            <div class="staff-field">
                <label class="staff-field-label" for="name">Business name</label>
                <input
                    class="staff-field-control @error('name') staff-field-control-error @enderror"
                    id="name"
                    name="name"
                    type="text"
                    maxlength="150"
                    autocomplete="organization"
                    required
                    autofocus
                    value="{{ old('name') }}"
                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                >
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="description">Description <span class="staff-field-optional">(optional)</span></label>
                <textarea
                    class="staff-field-control min-h-28 resize-y @error('description') staff-field-control-error @enderror"
                    id="description"
                    name="description"
                    aria-describedby="description-help @error('description') description-error @enderror"
                >{{ old('description') }}</textarea>
                <p class="staff-field-help" id="description-help">Briefly describe the organization or the services it provides.</p>
                @error('description')
                    <p class="staff-field-error" id="description-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-staff-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.businesses.index') }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Create business</button>
            </div>
        </form>
    </div>
@endsection
