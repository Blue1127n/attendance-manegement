<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;


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

// 管理者ログイン画面(一般は不要)
Route::get('/admin/login', function () {
    return view('auth.admin.login');
})->name('admin.login');

Route::middleware('auth')->post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ログイン処理（Fortifyではなく自作ルートでフォームリクエストを使う）一般ユーザー
Route::post('/login', function (LoginRequest $request) {
    if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        return back()->withErrors(['email' => 'ログイン情報が登録されていません'])->withInput();
    }
    $request->session()->regenerate();
    return redirect()->intended('/attendance');
});

// ログイン処理（Fortifyではなく自作ルートでフォームリクエストを使う）管理者
Route::post('/admin/login', function (AdminLoginRequest $request) {
    if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        return back()->withErrors(['email' => 'ログイン情報が登録されていません'])->withInput();
    }
    $request->session()->regenerate();
    return redirect()->intended('/admin/attendance/list');
});

// 会員登録処理（Fortifyではなく自作ルートでフォームリクエストを使う）
Route::post('/register', function (RegisterRequest $request) {
    // フルネーム（姓＋名）を分割する
    $nameParts = preg_split('/\s+/u', trim($request->name));
    $lastName = $nameParts[0] ?? '';
    $firstName = $nameParts[1] ?? '';

    Log::info('【名前分割】', [
        '元の名前' => $request->name,
        '姓' => $lastName,
        '名' => $firstName,
    ]);

    // ユーザー作成（last_name と first_name に分けて保存）
    $user = User::create([
        'last_name' => $lastName,
        'first_name' => $firstName,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);

    // メール認証通知
    $user->sendEmailVerificationNotification();

    return redirect('/email/verify');
});

// メール認証画面
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('user.attendance');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('user.attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('user.attendance.clockOut');
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('user.attendance.startBreak');
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('user.attendance.endBreak');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('user.attendance.list');
    Route::post('/attendance/{id}/correction', [AttendanceController::class, 'correctionRequest'])->name('user.attendance.correction');
    Route::get('/attendances/{id}/detail', [AttendanceController::class, 'show'])->name('user.attendance.detail');
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

