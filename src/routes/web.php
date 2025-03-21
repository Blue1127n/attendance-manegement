<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/admin/login', function () {
    return view('auth.admin-login');
})->name('admin.login');

Route::middleware('auth')->post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('user.attendance');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('user.attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('user.attendance.clockOut');
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('user.attendance.startBreak');
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('user.attendance.endBreak');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('user.attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'attendanceDetail'])->name('user.attendance.detail');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList'])->name('user.request.list');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminController::class, 'attendanceList'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AdminController::class, 'attendanceDetail'])->name('admin.attendance.detail');
    Route::get('/staff/list', [AdminController::class, 'staffList'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminController::class, 'staffAttendance'])->name('admin.staff.attendance');
    Route::get('/stamp_correction_request/list', [AdminController::class, 'requestList'])->name('admin.request.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminController::class, 'approveRequest'])->name('admin.request.approve');
});

