<?php

use App\Http\Controllers\InvueNotificationsDemoController;
use App\Http\Controllers\InvueTablesDemoController;
use App\Http\Controllers\ProfileController;
use App\Http\Requests\InvueDemoRequest;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/invue-demo', function () {
    return Inertia::render('InvueDemo');
})->name('invue.demo');

Route::post('/invue-demo', function (InvueDemoRequest $request) {
    $status = 'Formulario enviado com sucesso.';

    if ($request->hasFile('avatar')) {
        $status .= ' Arquivo recebido: '.$request->file('avatar')->getClientOriginalName();
    }

    $status .= ' Origem: '.$request->input('referral_source');

    return back()->with('status', $status);
})->name('invue.demo.store');

Route::get('/invue-tables-demo', InvueTablesDemoController::class)->name('invue.tables.demo');

Route::get('/invue-notifications-demo', [InvueNotificationsDemoController::class, 'show'])->name('invue.notifications.demo');
Route::post('/invue-notifications-demo', [InvueNotificationsDemoController::class, 'send'])->name('invue.notifications.demo.send');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
