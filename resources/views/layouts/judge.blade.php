<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Judge Scoring')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f0f4ff; color: #1f2937; line-height: 1.5; }
        a { color: inherit; text-decoration: none; }

        .navbar { background: #1e40af; color: #fff; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 60px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
        .navbar .brand { font-size: 1.25rem; font-weight: 700; }
        .navbar .brand span { color: #93c5fd; }
        .navbar .judge-info { font-size: 0.875rem; display: flex; align-items: center; gap: 1rem; }
        .navbar .judge-info .badge { background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; }

        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .container-full { max-width: none; width: 100%; padding-inline: clamp(1rem, 2.5vw, 2rem); }

        h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; }
        h2 { font-size: 1.35rem; font-weight: 600; margin-bottom: 0.5rem; }
        h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }

        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .btn-outline:hover { background: #f9fafb; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .btn-lg { padding: 0.75rem 2rem; font-size: 1rem; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; color: #374151; }
        .form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; font-family: inherit; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-4 { gap: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1rem; }
        .text-center { text-align: center; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-muted { color: #6b7280; }
        .text-success { color: #059669; }

        .badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.6rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; }
        th { background: #f9fafb; font-weight: 600; color: #374151; white-space: nowrap; }

        .score-input { width: 80px; padding: 0.5rem 0.5rem; border: 2px solid #d1d5db; border-radius: 8px; text-align: center; font-size: 1rem; font-weight: 600; transition: border-color 0.2s; }
        .score-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .tab-bar { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; overflow-x: auto; }
        .tab-bar a { padding: 0.75rem 1.25rem; font-size: 0.875rem; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; white-space: nowrap; }
        .tab-bar a:hover { color: #1f2937; }
        .tab-bar a.active { color: #1e40af; border-bottom-color: #1e40af; }
        .tab-bar a.locked { opacity: 0.5; }

        .login-card { max-width: 420px; margin: 4rem auto; }
        .login-card h1 { text-align: center; margin-bottom: 1.5rem; }
        .login-card .emoji { font-size: 3rem; text-align: center; display: block; margin-bottom: 1rem; }

        .contestant-row { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 0.75rem; }
        .contestant-row:hover { border-color: #93c5fd; }
        .contestant-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; align-items: start; }
        .contestant-column { min-width: 0; }
        .contestant-column-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            color: #1e3a8a;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .contestant-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem; }
        .contestant-identity { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .contestant-name { font-weight: 600; font-size: clamp(1rem, 1.2vw + 0.85rem, 1.2rem); line-height: 1.2; }
        .contestant-number {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: #fff;
            width: clamp(3.5rem, 7vw, 5rem);
            height: clamp(3.5rem, 7vw, 5rem);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.3rem, 2.6vw, 2rem);
            line-height: 1;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }
        .contestant-score-summary { min-width: 110px; text-align: center; flex-shrink: 0; }
        .criteria-grid { display: grid; gap: 0.75rem; margin-top: 0.75rem; }

        @media (max-width: 640px) {
            .container { padding: 1rem; }
            .container-full { padding-inline: 1rem; }
            .contestant-columns { grid-template-columns: 1fr; }
            .contestant-row { padding: 1rem; }
            .contestant-header { align-items: flex-start; flex-direction: column; }
            .contestant-identity { width: 100%; }
            .contestant-score-summary {
                width: 100%;
                text-align: left;
                padding-left: calc(clamp(3.5rem, 7vw, 5rem) + 1rem);
            }
            .score-input { width: 100%; }
        }
    </style>
</head>
<body>
    @hasSection('navbar')
        @yield('navbar')
    @endif

    @if(session('success'))
        <div class="container" style="padding-bottom:0">
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="container" style="padding-bottom:0">
            <div class="alert alert-error">✗ {{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</body>
</html>
