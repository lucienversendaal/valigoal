<?php

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'stats' => [
            'players' => User::whereNull('blocked_at')->count(),
            'matches' => GameMatch::count(),
            'points' => (int) Prediction::sum('points'),
        ],
    ])->with('title', 'ValiGOAL');
})->name('home');

Route::middleware(['auth', 'verified', 'not-blocked'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('voorspellen', 'pages::predictions')->name('predictions');
    Route::livewire('uitslagen', 'pages::results')->name('results');
    Route::livewire('klassement', 'pages::leaderboard')->name('leaderboard');
    Route::livewire('poules', 'pages::standings')->name('standings');
    Route::livewire('poules/{group}', 'pages::group')->name('group');
    Route::livewire('bracket', 'pages::bracket')->name('bracket');
    Route::livewire('bonus', 'pages::bonus')->name('bonus');
    Route::livewire('historie', 'pages::history')->name('history');
});

require __DIR__.'/settings.php';
