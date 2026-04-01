@extends('layouts.judge')

@section('title', 'Judge Login')

@section('content')
<div class="container">
    <div class="login-card card">
        <span class="emoji">🧑‍⚖️</span>
        <h1>Judge Login</h1>
        <p class="text-center text-muted mb-6">Enter your access code to start scoring.</p>

        <form action="{{ route('judge.authenticate') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="access_code">Access Code</label>
                <input
                    type="text"
                    name="access_code"
                    id="access_code"
                    class="form-control"
                    placeholder="Enter your judge access code"
                    value="{{ old('access_code') }}"
                    required
                    autofocus
                    style="font-size:1.1rem; padding:0.75rem; text-align:center; letter-spacing:2px; text-transform:uppercase"
                >
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center; margin-top:0.5rem">
                🔐 Enter Scoring Room
            </button>
        </form>
    </div>
</div>
@endsection
