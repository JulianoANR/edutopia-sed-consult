<?php

use App\Http\Controllers\ClassController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SedApiController;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// SED API - Public test route
Route::prefix('sed-api')->name('sed-api.')->group(function () {
    Route::get('/test-connection', [SedApiController::class, 'testConnection'])->name('test-connection');
});

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

Route::middleware('auth')->group(function () {
    
    // ----------------------------------------------------------------------------
    // PROFILE ROUTES
    // ----------------------------------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ----------------------------------------------------------------------------
    // SED API ROUTES
    // ----------------------------------------------------------------------------
    Route::prefix('sed-api')->name('sed-api.')->group(function () {
        // Token management
        Route::get('/token-status', [SedApiController::class, 'getTokenStatus'])->name('token-status');
        Route::delete('/clear-token', [SedApiController::class, 'clearToken'])->name('clear-token');
        
        // Data retrieval
        Route::get('/escolas-municipio', [SedApiController::class, 'getEscolasPorMunicipio'])->name('escolas-municipio');
        Route::get('/diretorias', [SedApiController::class, 'getDiretorias'])->name('diretorias');
        Route::get('/tipo-ensino', [SedApiController::class, 'getTipoEnsino'])->name('tipo-ensino');
    });
});

// ============================================================================
// AUTHENTICATED & VERIFIED ROUTES
// ============================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard redirect
    Route::get('/dashboard', function () {
        return redirect()->route('schools.index');
    })->name('dashboard');
    
    // ----------------------------------------------------------------------------
    // SCHOOL ROUTES
    // ----------------------------------------------------------------------------
    Route::prefix('schools')->name('schools.')->group(function () {
        // School selection and listing
        Route::get('/{redeEnsinoId?}', [SchoolController::class, 'index'])->name('index');
        Route::post('/select', [SchoolController::class, 'select'])->name('select');
        
        // School details and data
        Route::get('/view/{school}/{redeEnsinoId?}', [SchoolController::class, 'show'])->name('show');
        Route::get('/{school}/students', [SchoolController::class, 'students'])->name('students');
        Route::get('/{school}/classes', [SchoolController::class, 'classes'])->name('classes');
        
        // Export functionality
        Route::post('/export-students', [SchoolController::class, 'exportStudents'])->name('export-students');
        Route::post('/get-classes', [SchoolController::class, 'getSchoolClasses'])->name('get-classes');
        Route::post('/get-class-students', [SchoolController::class, 'getClassStudentsForExport'])->name('get-class-students');
        Route::post('/export-collected-students', [SchoolController::class, 'exportCollectedStudents'])->name('export-collected-students');
    });

    // ----------------------------------------------------------------------------
    // MANAGEMENT ROUTES (admin/gestor)
    // ----------------------------------------------------------------------------
    Route::middleware(['role:admin,gestor'])->group(function () {
        Route::get('/disciplines', [\App\Http\Controllers\DisciplineController::class, 'index'])->name('disciplines.index');
        Route::post('/disciplines', [\App\Http\Controllers\DisciplineController::class, 'store'])->name('disciplines.store');
        Route::put('/disciplines/{discipline}', [\App\Http\Controllers\DisciplineController::class, 'update'])->name('disciplines.update');
        Route::delete('/disciplines/{discipline}', [\App\Http\Controllers\DisciplineController::class, 'destroy'])->name('disciplines.destroy');

        Route::get('/teacher-links', [\App\Http\Controllers\TeacherLinkController::class, 'index'])->name('teacher_links.index');
        Route::post('/teacher-links', [\App\Http\Controllers\TeacherLinkController::class, 'store'])->name('teacher_links.store');
        Route::put('/teacher-links/{link}', [\App\Http\Controllers\TeacherLinkController::class, 'update'])->name('teacher_links.update');
        Route::delete('/teacher-links/{link}', [\App\Http\Controllers\TeacherLinkController::class, 'destroy'])->name('teacher_links.destroy');
    });
    
    // ----------------------------------------------------------------------------
    // CLASS ROUTES
    // ----------------------------------------------------------------------------
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/{classCode}', [ClassController::class, 'show'])->name('show');
        Route::post('/export-excel', [ClassController::class, 'exportExcel'])->name('export-excel');

        // Attendance routes
        Route::get('/{classCode}/attendance', [\App\Http\Controllers\AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/{classCode}/attendance/data', [\App\Http\Controllers\AttendanceController::class, 'getAttendance'])->name('attendance.data');
        Route::post('/{classCode}/attendance/save', [\App\Http\Controllers\AttendanceController::class, 'saveAttendance'])->name('attendance.save');


    });
    
    // ----------------------------------------------------------------------------
    // STUDENT ROUTES
    // ----------------------------------------------------------------------------
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/{studentRa}', [StudentController::class, 'show'])->name('show');
    });
    
    // ----------------------------------------------------------------------------
    // SED API ROUTES (Authenticated & Verified)
    // ----------------------------------------------------------------------------
    Route::prefix('sed-api')->name('sed-api.')->group(function () {
        Route::post('/consultar-turma', [SedApiController::class, 'consultarTurma'])->name('consultar-turma-auth');
        Route::get('/test-connection', [SchoolController::class, 'testConnection'])->name('test-connection-auth');
        Route::get('/classes', [SchoolController::class, 'getClasses'])->name('classes');
    });
});

// ============================================================================
// AUTH ROUTES
// ============================================================================

Route::middleware(['auth', 'verified', 'can:manage-tenants'])->prefix('tenants')->name('tenants.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TenantController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\TenantController::class, 'store'])->name('store');
    Route::put('/{tenant}', [\App\Http\Controllers\TenantController::class, 'update'])->name('update');
    Route::delete('/{tenant}', [\App\Http\Controllers\TenantController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/auth.php';
