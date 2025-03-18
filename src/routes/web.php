<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました！');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('user.attendance');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('user.attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'attendanceDetail'])->name('user.attendance.detail');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList'])->name('user.request.list');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminController::class, 'attendanceList'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AdminController::class, 'attendanceDetail'])->name('admin.attendance.detail');
    Route::get('/staff/list', [AdminController::class, 'staffList'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminController::class, 'staffAttendance'])->name('admin.staff.attendance');
    Route::get('/stamp_correction_request/list', [AdminController::class, 'requestList'])->name('admin.request.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminController::class, 'approveRequest'])->name('admin.request.approve');
});

