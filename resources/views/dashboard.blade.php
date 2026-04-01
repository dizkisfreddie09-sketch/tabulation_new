@extends('layouts.app')

@section('title', 'Dashboard - Tabulation System')

@section('content')
<div class="container">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1>Dashboard</h1>
            <p class="text-muted text-sm">Manage all your pageants and contests</p>
        </div>
        <a href="{{ route('contests.create') }}" class="btn btn-primary btn-lg">+ New Contest</a>
    </div>

    @if($contests->count())
        <div class="grid grid-3">
            @foreach($contests as $contest)
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h3 style="margin-bottom:0">{{ $contest->name }}</h3>
                        @if($contest->status === 'setup')
                            <span class="badge badge-yellow">Setup</span>
                        @elseif($contest->status === 'active')
                            <span class="badge badge-blue">Active</span>
                        @else
                            <span class="badge badge-green">Completed</span>
                        @endif
                    </div>

                    <div class="text-sm text-muted mb-4">
                        <p>📋 {{ $contest->getTypeLabel() }}</p>
                        <p>👥 {{ $contest->contestants_count }} contestants</p>
                        <p>📂 {{ $contest->exposures_count }} exposures</p>
                        <p>🧑‍⚖️ {{ $contest->judges_count }} / {{ $contest->judge_count }} judges</p>
                        <p style="margin-top:0.5rem" class="text-xs">Updated {{ $contest->updated_at->diffForHumans() }}</p>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        @if($contest->status === 'setup')
                            <a href="{{ route('contests.settings', $contest) }}" class="btn btn-primary btn-sm">Settings</a>
                        @elseif($contest->status === 'active')
                            <a href="{{ route('contests.tabulate', $contest) }}" class="btn btn-success btn-sm">Tabulate</a>
                            <a href="{{ route('contests.results', $contest) }}" class="btn btn-outline btn-sm">Results</a>
                        @else
                            <a href="{{ route('contests.results', $contest) }}" class="btn btn-primary btn-sm">View Results</a>
                        @endif

                        <form action="{{ route('contests.destroy', $contest) }}" method="POST" class="inline-form" onsubmit="return confirm('Delete this contest? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <p>No contests yet. Create your first one!</p>
                <a href="{{ route('contests.create') }}" class="btn btn-primary">+ Create Contest</a>
            </div>
        </div>
    @endif
</div>
@endsection