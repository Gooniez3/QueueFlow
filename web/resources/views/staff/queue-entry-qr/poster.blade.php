@extends('layouts.app')

@section('title', $service->name.' queue poster')
@section('body-class', 'queue-entry-poster-body')

@section('content')
    <div class="queue-entry-poster-screen">
        @include('staff.queue-entry-qr._poster')
    </div>
@endsection

@if ($printOnLoad)
    @push('scripts')
        <script>window.addEventListener('load', () => window.print(), { once: true });</script>
    @endpush
@endif
