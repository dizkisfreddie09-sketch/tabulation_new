<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tabulation System')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f3f4f6; color: #1f2937; line-height: 1.5; }
        a { color: inherit; text-decoration: none; }

        .navbar { background: #1e293b; color: #fff; padding: 0 2rem; display: flex; align-items: center; height: 60px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar .brand { font-size: 1.25rem; font-weight: 700; margin-right: 2rem; }
        .navbar .brand span { color: #60a5fa; }
        .navbar nav a { padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
        .navbar nav a:hover { background: rgba(255,255,255,0.1); }
        .navbar nav a.active { background: rgba(255,255,255,0.15); }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .container-wide { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; }
        h2 { font-size: 1.35rem; font-weight: 600; margin-bottom: 0.5rem; }
        h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }

        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-warning:hover { background: #d97706; }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .btn-outline:hover { background: #f9fafb; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .btn-lg { padding: 0.75rem 2rem; font-size: 1rem; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; color: #374151; }
        .form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; font-family: inherit; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        select.form-control { appearance: auto; }

        .grid { display: grid; gap: 1.5rem; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        @media (max-width: 768px) { .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; } }

        .flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 0.5rem; }
        .gap-4 { gap: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mr-2 { margin-right: 0.5rem; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-muted { color: #6b7280; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }

        .badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.6rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; }
        th { background: #f9fafb; font-weight: 600; color: #374151; white-space: nowrap; }
        tbody tr:hover { background: #f9fafb; }
        .score-input { width: 70px; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; text-align: center; font-size: 0.85rem; }
        .score-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }

        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .stat-card { text-align: center; padding: 1.5rem; }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-card .stat-label { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: #9ca3af; }
        .empty-state svg { width: 64px; height: 64px; margin: 0 auto 1rem; opacity: 0.5; }
        .empty-state p { font-size: 1rem; margin-bottom: 1rem; }

        .inline-form { display: inline; }

        .rank-1 { background: linear-gradient(135deg, #fef9c3, #fde68a) !important; }
        .rank-2 { background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important; }
        .rank-3 { background: linear-gradient(135deg, #fed7aa, #fdba74) !important; }

        .tab-bar { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
        .tab-bar a { padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-bar a:hover { color: #1f2937; }
        .tab-bar a.active { color: #3b82f6; border-bottom-color: #3b82f6; }

        @media print {
            .navbar, .no-print { display: none !important; }
            body { background: #fff; }
            .container, .container-wide { max-width: 100%; padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="navbar no-print">
        <a href="{{ route('dashboard') }}" class="brand">📊 <span>Tabulation</span>System</a>
        <nav class="flex gap-2">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('contests.create') }}" class="{{ request()->routeIs('contests.create') ? 'active' : '' }}">+ New Contest</a>
            <a href="{{ route('judge.login') }}" target="_blank" style="opacity:0.7">🧑‍⚖️ Judge Portal</a>
        </nav>
    </div>

    @if(session('success'))
        <div class="container no-print" style="padding-bottom:0">
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="container no-print" style="padding-bottom:0">
            <div class="alert alert-error">✗ {{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</body>
</html>