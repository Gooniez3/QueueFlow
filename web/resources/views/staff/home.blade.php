@extends('layouts.staff-auth')

@section('title', 'Staff')

@section('content')
    <div>
        <p>Signed in successfully.</p>
        <p>The QueueFlow staff workspace will be available here.</p>

        <form method="POST" action="{{ route('staff.logout') }}">
            @csrf

            <button type="submit">
                Sign out
            </button>
        </form>
    </div>
@endsection
