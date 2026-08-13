@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div class="fw-bold text-truncate">{{ $user->name }}</div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary flex-shrink-0"><i class="bi bi-arrow-left"></i> Back to Users</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update User</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
