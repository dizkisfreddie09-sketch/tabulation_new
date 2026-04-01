<?php

use App\Http\Controllers\ContestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JudgeScoringController;
use App\Http\Controllers\TabulationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Contest CRUD
Route::get('/contests/create', [ContestController::class, 'create'])->name('contests.create');
Route::post('/contests', [ContestController::class, 'store'])->name('contests.store');
Route::get('/contests/{contest}/settings', [ContestController::class, 'settings'])->name('contests.settings');
Route::put('/contests/{contest}', [ContestController::class, 'update'])->name('contests.update');
Route::delete('/contests/{contest}', [ContestController::class, 'destroy'])->name('contests.destroy');

// Exposures
Route::post('/contests/{contest}/exposures', [ContestController::class, 'storeExposure'])->name('contests.exposures.store');
Route::delete('/contests/{contest}/exposures/{exposure}', [ContestController::class, 'destroyExposure'])->name('contests.exposures.destroy');
Route::post('/contests/{contest}/import-csv', [ContestController::class, 'importCsv'])->name('contests.import-csv');
Route::get('/contests/{contest}/csv-template', [ContestController::class, 'downloadCsvTemplate'])->name('contests.csv-template');

// Criteria (per exposure)
Route::post('/contests/{contest}/exposures/{exposure}/criteria', [ContestController::class, 'storeCriteria'])->name('contests.criteria.store');
Route::delete('/contests/{contest}/exposures/{exposure}/criteria/{criterion}', [ContestController::class, 'destroyCriteria'])->name('contests.criteria.destroy');

// Contestants
Route::post('/contests/{contest}/contestants', [ContestController::class, 'storeContestant'])->name('contests.contestants.store');
Route::delete('/contests/{contest}/contestants/{contestant}', [ContestController::class, 'destroyContestant'])->name('contests.contestants.destroy');

// Judges
Route::post('/contests/{contest}/judges', [ContestController::class, 'storeJudge'])->name('contests.judges.store');
Route::delete('/contests/{contest}/judges/{judge}', [ContestController::class, 'destroyJudge'])->name('contests.judges.destroy');

// Contest activation
Route::post('/contests/{contest}/activate', [ContestController::class, 'activate'])->name('contests.activate');

// Tabulation (per exposure)
Route::get('/contests/{contest}/tabulate', [TabulationController::class, 'tabulate'])->name('contests.tabulate');
Route::post('/contests/{contest}/exposures/{exposure}/scores', [TabulationController::class, 'storeScores'])->name('contests.scores.store');
Route::post('/contests/{contest}/exposures/{exposure}/lock', [TabulationController::class, 'lockExposure'])->name('contests.exposures.lock');
Route::post('/contests/{contest}/exposures/{exposure}/unlock', [TabulationController::class, 'unlockExposure'])->name('contests.exposures.unlock');

// Results
Route::get('/contests/{contest}/results', [TabulationController::class, 'results'])->name('contests.results');
Route::post('/contests/{contest}/complete', [TabulationController::class, 'complete'])->name('contests.complete');

// Printing
Route::get('/contests/{contest}/print-scores', [TabulationController::class, 'printScores'])->name('contests.print-scores');
Route::get('/contests/{contest}/print-results', [TabulationController::class, 'printResults'])->name('contests.print-results');
Route::get('/contests/{contest}/print-rankings', [TabulationController::class, 'printRankings'])->name('contests.print-rankings');

// Judge Scoring Portal (separate pages per judge)
Route::get('/judge', [JudgeScoringController::class, 'login'])->name('judge.login');
Route::post('/judge/login', [JudgeScoringController::class, 'authenticate'])->name('judge.authenticate');
Route::get('/judge/score', [JudgeScoringController::class, 'score'])->name('judge.score');
Route::post('/judge/score/{exposure}', [JudgeScoringController::class, 'storeScores'])->name('judge.storeScores');
Route::post('/judge/logout', [JudgeScoringController::class, 'logout'])->name('judge.logout');
