@extends('layouts.app')

@section('title', 'Create Contest - Tabulation System')

@section('content')
<div class="container" style="max-width:600px">
    <h1 class="mb-4">Create New Contest</h1>

    <div class="card">
        <form action="{{ route('contests.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Contest / Pageant Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Miss Universe 2026" value="{{ old('name') }}" required>
                @error('name')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="type">Contest Type</label>
                <select name="type" id="type" class="form-control" required>
                    <option value="single" {{ old('type') === 'single' ? 'selected' : '' }}>Single Contestant — One person per entry</option>
                    <option value="double" {{ old('type') === 'double' ? 'selected' : '' }}>Double Contestant — Pair per entry (e.g. Mr. & Ms.)</option>
                    <option value="group" {{ old('type') === 'group' ? 'selected' : '' }}>Group Contestant — Team/group per entry</option>
                </select>
                @error('type')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="judge_count">Number of Judges</label>
                <input type="number" name="judge_count" id="judge_count" class="form-control" value="{{ old('judge_count', 5) }}" min="1" max="20" required>
                @error('judge_count')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="contestant-count-field">
                <label for="contestant_count">Number of Contestants</label>
                <input type="number" name="contestant_count" id="contestant_count" class="form-control" value="{{ old('contestant_count', 5) }}" min="0" max="200">
                @error('contestant_count')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="male-count-field" style="display:none;">
                <label for="male_count">Male Contestants (double only)</label>
                <input type="number" name="male_count" id="male_count" class="form-control" value="{{ old('male_count', 0) }}" min="0" max="200">
                @error('male_count')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="female-count-field" style="display:none;">
                <label for="female_count">Female Contestants (double only)</label>
                <input type="number" name="female_count" id="female_count" class="form-control" value="{{ old('female_count', 0) }}" min="0" max="200">
                @error('female_count')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Create Contest</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function updateContestantCountFields(type) {
        const contestantField = document.getElementById('contestant-count-field');
        const maleField = document.getElementById('male-count-field');
        const femaleField = document.getElementById('female-count-field');
        const contestantInput = document.getElementById('contestant_count');
        const maleInput = document.getElementById('male_count');
        const femaleInput = document.getElementById('female_count');

        if (!contestantField || !maleField || !femaleField || !contestantInput || !maleInput || !femaleInput) {
            return;
        }

        if (type === 'double') {
            contestantField.style.display = 'none';
            contestantInput.disabled = true;
            maleField.style.display = '';
            femaleField.style.display = '';
            maleInput.disabled = false;
            femaleInput.disabled = false;
        } else {
            contestantField.style.display = '';
            contestantInput.disabled = false;
            maleField.style.display = 'none';
            femaleField.style.display = 'none';
            maleInput.disabled = true;
            femaleInput.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        if (!typeSelect) {
            return;
        }

        updateContestantCountFields(typeSelect.value);
        typeSelect.addEventListener('change', function() {
            updateContestantCountFields(this.value);
        });
    });
</script>
@endsection