<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $contests = Contest::withCount(['contestants', 'judges', 'exposures', 'scores'])
            ->orderByDesc('updated_at')
            ->get();

        return view('dashboard', compact('contests'));
    }
}
