@extends('layouts.app')

@section('title', 'Settings: ' . $contest->name . ' - Tabulation System')

@section('content')
<div class="container">
    <style>
        .exposure-item { border: 1px solid #d1d5db; box-shadow: none; margin-bottom: 1rem; }
        .exposure-item.exposure-final { border-color: #f59e0b; }
    </style>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1>{{ $contest->name }}</h1>
            <p class="text-muted text-sm">{{ $contest->getTypeLabel() }} · {{ $contest->judge_count }} judges · Status: {{ ucfirst($contest->status) }}</p>
        </div>
        <div class="flex gap-2">
            @if($contest->status === 'setup')
                <form action="{{ route('contests.activate', $contest) }}" method="POST" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Start this contest?')">🚀 Start Contest</button>
                </form>
            @elseif($contest->status === 'active')
                <a href="{{ route('contests.tabulate', $contest) }}" class="btn btn-success">Go to Tabulation</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline">← Dashboard</a>
        </div>
    </div>

    {{-- Contest Info --}}
    <div class="card">
        <div class="card-header">
            <h2 style="margin-bottom:0">Contest Information</h2>
        </div>
        <form action="{{ route('contests.update', $contest) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-3">
                <div class="form-group">
                    <label for="name">Contest Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $contest->name) }}" required>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="single" {{ $contest->type === 'single' ? 'selected' : '' }}>Single Contestant</option>
                        <option value="double" {{ $contest->type === 'double' ? 'selected' : '' }}>Double Contestant</option>
                        <option value="group" {{ $contest->type === 'group' ? 'selected' : '' }}>Group Contestant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="judge_count">Max Judges</label>
                    <input type="number" name="judge_count" id="judge_count" class="form-control" value="{{ old('judge_count', $contest->judge_count) }}" min="1" max="20" required>
                </div>
                <div class="form-group" id="contestant-count-field">
                    <label for="contestant_count">Number of Contestants</label>
                    <input type="number" name="contestant_count" id="contestant_count" class="form-control" value="{{ old('contestant_count', $contest->contestant_count) }}" min="0" max="200">
                </div>
                <div class="form-group" id="male-count-field" style="display:none;">
                    <label for="male_count">Male Contestants (double only)</label>
                    <input type="number" name="male_count" id="male_count" class="form-control" value="{{ old('male_count', $contest->male_count) }}" min="0" max="200">
                </div>
                <div class="form-group" id="female-count-field" style="display:none;">
                    <label for="female_count">Female Contestants (double only)</label>
                    <input type="number" name="female_count" id="female_count" class="form-control" value="{{ old('female_count', $contest->female_count) }}" min="0" max="200">
                </div>
            </div>
            <div class="grid grid-3" style="margin-top:8px;">
                <div class="form-group">
                    <label for="tabulator_name">Official Tabulator Name</label>
                    <input type="text" name="tabulator_name" id="tabulator_name" class="form-control" value="{{ old('tabulator_name', $contest->tabulator_name) }}" placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label for="logo">Tabulator Logo</label>
                    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                    @if($contest->logo)
                        <div style="margin-top:8px; display:flex; align-items:center; gap:10px;">
                            <img src="{{ asset('storage/' . $contest->logo) }}" alt="Logo" style="max-height:50px; border-radius:4px;">
                            <label style="font-size:12px; color:#666;"><input type="checkbox" name="remove_logo" value="1"> Remove</label>
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="pageant_logo">Pageant Logo</label>
                    <input type="file" name="pageant_logo" id="pageant_logo" class="form-control" accept="image/*">
                    @if($contest->pageant_logo)
                        <div style="margin-top:8px; display:flex; align-items:center; gap:10px;">
                            <img src="{{ asset('storage/' . $contest->pageant_logo) }}" alt="Pageant Logo" style="max-height:50px; border-radius:4px;">
                            <label style="font-size:12px; color:#666;"><input type="checkbox" name="remove_pageant_logo" value="1"> Remove</label>
                        </div>
                    @endif
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Contest</button>
        </form>
    </div>

    {{-- ======== EXPOSURES ======== --}}
    <div class="card">
        <div class="card-header">
            <h2 style="margin-bottom:0">📋 Exposures (Rounds / Segments)</h2>
            <span class="badge badge-blue">{{ $contest->exposures->count() }} exposure(s)</span>
        </div>

        <p class="text-sm text-muted mb-4">Each exposure has its own set of criteria that must total 100%. For final rounds, you can carry over a percentage of prior exposure totals.</p>

        <div id="exposure-list">
            @foreach($contest->exposures as $exposure)
                <div class="card exposure-item {{ $exposure->is_final ? 'exposure-final' : '' }}" data-exposure-index="{{ $loop->index }}">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 style="margin-bottom:2px">
                                {{ $loop->iteration }}. {{ $exposure->name }}
                                @if($exposure->is_final)
                                    <span class="badge badge-yellow">FINAL ROUND</span>
                                @endif
                                @if($exposure->is_locked)
                                    <span class="badge badge-green">🔒 Locked</span>
                                @endif
                            </h3>
                            @if($exposure->is_final && $exposure->carry_over_percentage > 0)
                                <span class="text-xs text-muted">Carries over {{ $exposure->carry_over_percentage }}% from prior exposures · Top {{ $exposure->top_n ?? 'All' }} contestants</span>
                            @endif
                        </div>
                        <form action="{{ route('contests.exposures.destroy', [$contest, $exposure]) }}" method="POST" class="inline-form" onsubmit="return confirm('Remove this exposure and all its criteria/scores?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                        </form>
                    </div>

                    {{-- Criteria for this exposure --}}
                    @php $totalPct = $exposure->criteria->sum('percentage'); @endphp
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm" style="font-weight:600">Criteria</span>
                        <span class="badge {{ abs($totalPct - 100) < 0.01 ? 'badge-green' : 'badge-red' }}">{{ $totalPct }}%</span>
                    </div>

                    @if($exposure->criteria->count())
                        <table>
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th>%</th>
                                    <th>Range</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exposure->criteria as $criterion)
                                    <tr>
                                        <td>{{ $criterion->name }}</td>
                                        <td>{{ $criterion->percentage }}%</td>
                                        <td>{{ $criterion->min_score }} – {{ $criterion->max_score }}</td>
                                        <td class="text-right">
                                            <form action="{{ route('contests.criteria.destroy', [$contest, $exposure, $criterion]) }}" method="POST" class="inline-form" onsubmit="return confirm('Remove this criteria?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted text-sm text-center" style="padding:0.5rem">No criteria yet.</p>
                    @endif

                    @if(!$exposure->is_final || $exposure->criteria->count() > 0 || true)
                    <form action="{{ route('contests.criteria.store', [$contest, $exposure]) }}" method="POST" style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid #e5e7eb">
                        @csrf
                        <div class="flex gap-2 flex-wrap items-center">
                            <input type="text" name="name" class="form-control" placeholder="Criteria name" style="flex:2; min-width:140px" required>
                            <input type="number" name="percentage" class="form-control" placeholder="%" step="0.01" min="0.01" max="100" style="width:80px" required>
                            <input type="number" name="min_score" class="form-control" placeholder="Min" step="0.01" value="1" style="width:70px" required>
                            <input type="number" name="max_score" class="form-control" placeholder="Max" step="0.01" value="100" style="width:70px" required>
                            <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                        </div>
                    </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if($contest->exposures->count() > 3)
            <div id="exposure-pagination" class="flex justify-between items-center" style="padding:0.75rem 0; border-top:1px solid #e5e7eb; margin-bottom:0.5rem;">
                <button type="button" id="exp-prev" class="btn btn-outline btn-sm" onclick="changeExposurePage(-1)">← Previous</button>
                <span id="exp-page-info" class="text-sm text-muted"></span>
                <button type="button" id="exp-next" class="btn btn-outline btn-sm" onclick="changeExposurePage(1)">Next →</button>
            </div>
        @endif

        {{-- Add Exposure Form --}}
        <form action="{{ route('contests.exposures.store', $contest) }}" method="POST" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb">
            @csrf
            <p class="text-sm" style="font-weight:600; margin-bottom:0.5rem">Add Exposure</p>
            <div class="flex gap-2 flex-wrap items-center">
                <input type="text" name="name" class="form-control" placeholder="e.g. Best in Professional Attire" style="flex:2; min-width:200px" required>
                <label class="flex items-center gap-2 text-sm" style="white-space:nowrap">
                    <input type="checkbox" name="is_final" value="1" id="is_final_check" onchange="document.getElementById('final_opts').style.display = this.checked ? 'flex' : 'none'"> Final Round
                </label>
                <div id="final_opts" style="display:none" class="flex gap-2 flex-wrap items-center">
                    <select name="top_n" class="form-control" style="width:130px">
                        <option value="">All advance</option>
                        <option value="3">Top 3</option>
                        <option value="5">Top 5</option>
                        <option value="8">Top 8</option>
                        <option value="10">Top 10</option>
                    </select>
                    <input type="number" name="carry_over_percentage" class="form-control" placeholder="Carry-over %" step="0.01" min="0" max="100" style="width:120px" value="0">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">+ Add Exposure</button>
            </div>
            <p class="text-xs text-muted mt-2">For final rounds, check "Final Round", choose how many contestants advance (Top 3, Top 5, etc.), and set the carry-over %.</p>
        </form>

        {{-- CSV Import --}}
        <div style="margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid #e5e7eb">
            <div class="flex justify-between items-center mb-2">
                <p class="text-sm" style="font-weight:600">📥 Import from CSV</p>
                <a href="{{ route('contests.csv-template', $contest) }}" class="btn btn-outline btn-sm">⬇ Download Template</a>
            </div>
            <form action="{{ route('contests.import-csv', $contest) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="flex gap-2 items-center">
                    <input type="file" name="csv_file" accept=".csv,.txt" class="form-control" style="flex:1" required>
                    <button type="submit" class="btn btn-primary btn-sm">📤 Import</button>
                </div>
                <p class="text-xs text-muted mt-2">CSV columns: <code style="background:#f3f4f6; padding:1px 4px; border-radius:3px; font-size:0.7rem">exposure_name, is_final, carry_over_percentage, top_n, criteria_name, percentage, min_score, max_score</code>. Rows with the same exposure name are grouped. Download the template for an example.</p>
            </form>
        </div>
    </div>

    <div class="grid grid-2">
        {{-- Judges --}}
        <div class="card">
            <div class="card-header">
                <h2 style="margin-bottom:0">Judges</h2>
                <span class="badge badge-blue">{{ $contest->judges->count() }} / {{ $contest->judge_count }}</span>
            </div>

            @if($contest->judges->count())
                <table>
                    <thead><tr><th>#</th><th>Name</th><th>Access Code</th><th>Scoring Link</th><th></th></tr></thead>
                    <tbody>
                        @foreach($contest->judges as $judge)
                            <tr>
                                <td>{{ $judge->number }}</td>
                                <td>{{ $judge->name }}</td>
                                <td><code style="background:#f3f4f6; padding:2px 8px; border-radius:4px; font-size:0.9rem; letter-spacing:1px">{{ $judge->access_code ?? '—' }}</code></td>
                                <td>
                                    @if($judge->access_code)
                                        <a href="{{ route('judge.login') }}" class="text-sm" style="color:#3b82f6" target="_blank">Open Portal →</a>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <form action="{{ route('contests.judges.destroy', [$contest, $judge]) }}" method="POST" class="inline-form" onsubmit="return confirm('Remove this judge?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">✕</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($contest->status === 'active')
                    <div style="margin-top:0.75rem; padding:0.75rem; background:#eff6ff; border-radius:8px; border:1px solid #bfdbfe">
                        <p class="text-sm" style="font-weight:600; color:#1e40af">📱 Judge Scoring Portal</p>
                        <p class="text-xs text-muted">Each judge can score from their own device at: <strong>{{ url('/judge') }}</strong></p>
                        <p class="text-xs text-muted">They enter their access code to log in. Scores appear on the main tabulation automatically.</p>
                    </div>
                @endif
            @else
                <p class="text-muted text-sm text-center" style="padding:1rem">No judges added yet.</p>
            @endif

            @if($contest->judges->count() < $contest->judge_count)
                <form action="{{ route('contests.judges.store', $contest) }}" method="POST" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb">
                    @csrf
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">+ Add Judge</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Contestants --}}
        <div class="card">
            <div class="card-header">
                <h2 style="margin-bottom:0">Contestants</h2>
                <span class="badge badge-blue">{{ $contest->contestants->count() }}</span>
                @if($contest->type === 'double')
                    <span class="badge badge-green">Males: {{ $contest->maleContestantCount() }}</span>
                    <span class="badge badge-yellow">Females: {{ $contest->femaleContestantCount() }}</span>
                @endif
            </div>

            @if($contest->contestants->count())
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            @if($contest->type === 'double')<th>Gender</th>@endif
                            @if($contest->type === 'group')<th>Team</th>@endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contest->contestants as $contestant)
                            <tr>
                                <td>{{ $contestant->number }}</td>
                                <td>{{ $contestant->name }}</td>
                                @if($contest->type === 'double')<td>{{ str_starts_with($contestant->name, 'Mr ') ? 'Male' : (str_starts_with($contestant->name, 'Ms ') ? 'Female' : 'Unknown') }}</td>@endif
                                @if($contest->type === 'group')<td>{{ $contestant->team_name }}</td>@endif
                                <td class="text-right">
                                    <form action="{{ route('contests.contestants.destroy', [$contest, $contestant]) }}" method="POST" class="inline-form" onsubmit="return confirm('Remove this contestant?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">✕</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted text-sm text-center" style="padding:1rem">No contestants added yet.</p>
            @endif

            <form action="{{ route('contests.contestants.store', $contest) }}" method="POST" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb">
                @csrf
                <div class="flex gap-2 flex-wrap items-center">
                    <div class="form-control" style="width:70px; display:flex; align-items:center; justify-content:center; background:#f3f4f6; border:1px solid #d1d5db; border-radius:8px; color:#6b7280;">
                        #{{ ($contest->contestants->max('number') ?? 0) + 1 }}
                    </div>
                    @if($contest->type === 'double')
                        <select name="gender" class="form-control" style="width:140px">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <input type="text" name="name" class="form-control" placeholder="Optional name" style="flex:1; min-width:120px">
                    @elseif($contest->type === 'group')
                        <input type="text" name="name" class="form-control" placeholder="Representative name" style="flex:1; min-width:120px" required>
                        <input type="text" name="team_name" class="form-control" placeholder="Team name" style="flex:1; min-width:120px" required>
                    @else
                        <input type="text" name="name" class="form-control" placeholder="Name" style="flex:1; min-width:120px" required>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">+ Add Contestant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var perPage = 3;
    var items = document.querySelectorAll('.exposure-item');
    var total = items.length;
    if (total <= perPage) return;

    var currentPage = 0;
    var totalPages = Math.ceil(total / perPage);

    function render() {
        var start = currentPage * perPage;
        var end = start + perPage;
        items.forEach(function(el, i) {
            el.style.display = (i >= start && i < end) ? '' : 'none';
        });
        document.getElementById('exp-page-info').textContent =
            'Page ' + (currentPage + 1) + ' of ' + totalPages + ' (' + total + ' exposures)';
        document.getElementById('exp-prev').disabled = currentPage === 0;
        document.getElementById('exp-next').disabled = currentPage >= totalPages - 1;
    }

    window.changeExposurePage = function(dir) {
        currentPage = Math.max(0, Math.min(totalPages - 1, currentPage + dir));
        render();
    };

    render();
})();
</script>
@endsection