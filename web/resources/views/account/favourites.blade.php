@extends('layouts.app')

@section('title', 'Favourite places - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center gap-3">
                <a class="customer-back-link" href="{{ route('account.show') }}" aria-label="Back to account"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg></a>
                <h1 class="customer-page-title">Favourite places</h1>
            </div>
        </header>
        <main class="relative z-10 -mt-7 px-5 pb-7">
            @if ($errors->has('favourite')) <p class="mb-3 rounded-xl bg-white p-3 text-sm text-red-700" role="alert">{{ $errors->first('favourite') }}</p> @endif
            @if (session('status')) <p class="mb-3 rounded-xl bg-white p-3 text-sm text-customer-indigo" role="status">{{ session('status') }}</p> @endif
            <section class="customer-resource-list" aria-label="Saved favourite places">
                @forelse ($favourites as $favourite)
                    <article class="customer-resource-row">
                        @if ($favourite['business'])
                            <a class="min-w-0 grow" href="{{ route('businesses.show', $favourite['id']) }}"><span class="block truncate text-sm font-bold">{{ $favourite['business']->name }}</span><span class="mt-1 block text-xs text-customer-muted">View place details</span></a>
                        @elseif ($favourite['availability'] === 'unavailable')
                            <div class="min-w-0 grow"><p class="text-sm font-semibold">Saved place could not be loaded</p><p class="mt-1 text-xs text-customer-muted">Please try again later. You can remove this saved place.</p></div>
                        @else
                            <div class="min-w-0 grow"><p class="text-sm font-semibold">Saved place unavailable</p><p class="mt-1 text-xs text-customer-muted">This place may have been removed.</p></div>
                        @endif
                        <form method="post" action="{{ route('businesses.favourites.remove', $favourite['id']) }}">
                            @csrf @method('delete')
                            <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold text-customer-indigo focus-visible:outline-2 focus-visible:outline-customer-indigo" aria-label="Remove {{ $favourite['business']?->name ?? 'unavailable place' }} from favourites">Remove</button>
                        </form>
                    </article>
                @empty
                    <div class="customer-empty-state text-center"><p class="text-xl font-bold">No favourite places yet.</p><p class="mt-2 text-sm text-customer-muted">Save a place from its details page to find it here.</p><a class="mt-4 inline-flex rounded-xl bg-customer-indigo px-4 py-2 text-sm font-semibold text-white" href="{{ route('places.index') }}">Browse places</a></div>
                @endforelse
            </section>
        </main>
    </div>
@endsection
