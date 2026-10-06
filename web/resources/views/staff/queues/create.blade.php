@extends('layouts.staff')

@section('title', 'Open queue')
@section('staff-area', 'Operations')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[
        ['label' => 'Live queues', 'url' => route('staff.live-queues.gateway')],
        ['label' => $branch->name, 'url' => route('staff.live-queues.index', [$business->id, $branch->id])],
        ['label' => 'Open queue'],
    ]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.live-queues.index', [$business->id, $branch->id])"
            back-label="Back to live queues"
            :items="[
                ['label' => 'Live queues', 'url' => route('staff.live-queues.gateway')],
                ['label' => $branch->name, 'url' => route('staff.live-queues.index', [$business->id, $branch->id])],
                ['label' => 'Open queue'],
            ]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="queues" /></span>
            <div>
                <p class="staff-eyebrow">QUEUE OPERATIONS</p>
                <h1 class="staff-page-title">Open a queue</h1>
                <p class="staff-page-copy">Open today’s operational queue for {{ $branch->name }}. Spring will use the branch’s local business date.</p>
            </div>
        </header>

        <form class="staff-form-card" method="POST" action="{{ route('staff.live-queues.store', [$business->id, $branch->id]) }}">
            @csrf

            @error('queue')
                <p class="staff-alert-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="staff-field">
                <span class="staff-field-label">Queue type</span>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-16 cursor-pointer items-start gap-3 rounded-xl border border-staff-line bg-white p-4 hover:border-staff-indigo/35">
                        <input class="mt-1 accent-staff-indigo" name="queueType" type="radio" value="service" @checked(old('queueType', 'service') === 'service')>
                        <span><strong class="block text-sm">Service-specific queue</strong><span class="mt-1 block text-xs leading-5 text-staff-muted">Customers join for one selected service.</span></span>
                    </label>
                    <label class="flex min-h-16 cursor-pointer items-start gap-3 rounded-xl border border-staff-line bg-white p-4 hover:border-staff-indigo/35">
                        <input class="mt-1 accent-staff-indigo" name="queueType" type="radio" value="shared" @checked(old('queueType') === 'shared')>
                        <span><strong class="block text-sm">Shared branch queue</strong><span class="mt-1 block text-xs leading-5 text-staff-muted">One queue can accept customers for branch services.</span></span>
                    </label>
                </div>
                @error('queueType')
                    <p class="staff-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="serviceId">Service <span class="staff-field-optional">(required for service-specific queues)</span></label>
                <select class="staff-field-control @error('serviceId') staff-field-control-error @enderror" id="serviceId" name="serviceId" aria-describedby="service-help @error('serviceId') service-error @enderror">
                    <option value="">Choose a service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected((string) old('serviceId') === (string) $service->id)>{{ $service->name }}{{ $service->active ? '' : ' (inactive)' }}</option>
                    @endforeach
                </select>
                <p class="staff-field-help" id="service-help">For a shared branch queue, leave this unselected.</p>
                @error('serviceId')
                    <p class="staff-field-error" id="service-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field">
                <label class="staff-field-label" for="name">Queue name</label>
                <input class="staff-field-control @error('name') staff-field-control-error @enderror" id="name" name="name" type="text" maxlength="150" required value="{{ old('name') }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="staff-field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="staff-field max-w-xs">
                <label class="staff-field-label" for="ticketPrefix">Ticket prefix</label>
                <input class="staff-field-control @error('ticketPrefix') staff-field-control-error @enderror" id="ticketPrefix" name="ticketPrefix" type="text" maxlength="10" required value="{{ old('ticketPrefix') }}" aria-describedby="prefix-help @error('ticketPrefix') prefix-error @enderror" @error('ticketPrefix') aria-invalid="true" @enderror>
                <p class="staff-field-help" id="prefix-help">Spring trims and uppercases this prefix when the queue opens.</p>
                @error('ticketPrefix')
                    <p class="staff-field-error" id="prefix-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-staff-line pt-6 sm:flex-row sm:items-center">
                <a class="staff-secondary-button" href="{{ route('staff.live-queues.index', [$business->id, $branch->id]) }}">Cancel</a>
                <button class="staff-primary-button" type="submit">Open queue</button>
            </div>
        </form>
    </div>
@endsection
