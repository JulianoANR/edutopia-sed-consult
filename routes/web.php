<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SedApiController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('schools.index');
    })->name('dashboard');
    
    // Rotas do sistema educacional
    Route::get('/schools', [App\Http\Controllers\SchoolController::class, 'index'])->name('schools.index');
    Route::get('/schools/{school}', [App\Http\Controllers\SchoolController::class, 'show'])->name('schools.show');
    Route::post('/schools/select', [App\Http\Controllers\SchoolController::class, 'select'])->name('schools.select');
    Route::get('/schools/{school}/students', [App\Http\Controllers\SchoolController::class, 'students'])->name('schools.students');
    Route::get('/schools/{school}/classes', [App\Http\Controllers\SchoolController::class, 'classes'])->name('schools.classes');
    
    // Rotas das turmas
    Route::get('/classes/{classCode}', [App\Http\Controllers\ClassController::class, 'show'])->name('classes.show');
    
    // Rotas dos alunos
    Route::get('/students/{studentRa}', [App\Http\Controllers\StudentController::class, 'show'])->name('students.show');
    
    // Rotas da API SED
    Route::get('/sed-api/test-connection', [App\Http\Controllers\SchoolController::class, 'testConnection'])->name('sed-api.test-connection');
    Route::get('/sed-api/classes', [App\Http\Controllers\SchoolController::class, 'getClasses'])->name('sed-api.classes');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// SED API Routes (Test route without auth)
Route::prefix('sed-api')->name('sed-api.')->group(function () {
    Route::get('/test-connection', [SedApiController::class, 'testConnection'])->name('test-connection');
    Route::post('/consultar-turma', [SedApiController::class, 'consultarTurma'])->name('consultar-turma');
});

// SED API Routes (Authenticated)
Route::middleware(['auth', 'verified'])->prefix('sed-api')->name('sed-api.')->group(function () {
    Route::post('/consultar-turma', [SedApiController::class, 'consultarTurma'])->name('consultar-turma-auth');
});

// SED API Routes (Protected routes)
Route::middleware('auth')->prefix('sed-api')->name('sed-api.')->group(function () {
    Route::get('/token-status', [SedApiController::class, 'getTokenStatus'])->name('token-status');
    Route::delete('/clear-token', [SedApiController::class, 'clearToken'])->name('clear-token');
    Route::get('/escolas-municipio', [SedApiController::class, 'getEscolasPorMunicipio'])->name('escolas-municipio');
});
