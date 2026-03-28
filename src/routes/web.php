<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\AdminAuthController;

Route::get('/', function () {
    return view('welcome');
});

// 管理者ログイン (authグループの外)
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // 一般ユーザー: 勤怠
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendance.breakIn');
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendance.breakOut');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'requestCorrection'])->name('attendance.requestCorrection');

    // 申請一覧
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
      ->name('stamp_correction_request.list');
    
    // 修正申請承認画面（管理者）
    Route::get(
      '/stamp_correction_request/approve/{stampCorrectionRequest}',
      [StampCorrectionRequestController::class, 'show']
    )->name('stamp_correction_request.show');
    
    Route::post(
      '/stamp_correction_request/approve/{stampCorrectionRequest}',
      [StampCorrectionRequestController::class, 'approve']
    )->name('stamp_correction_request.approve');

    // 管理者用 勤怠一覧・詳細
    Route::get('/admin/attendance/list', [AttendanceController::class, 'list'])
       ->name('admin.attendance.list');

    Route::get('/admin/attendance/{id}', [AttendanceController::class, 'detail'])
       ->name('admin.attendance.detail');

    Route::post('/admin/attendance/{id}', [AttendanceController::class, 'adminRequestCorrection'])
       ->name('admin.attendance.requestCorrection');

    // スタッフ一覧
    Route::get('/admin/staff/list', [StaffController::class, 'index'])
       ->name('staff.list');

    // スタッフ別勤怠一覧（管理者）
    Route::get('/admin/attendance/staff/{user}', [StaffAttendanceController::class, 'show'])
       ->name('staff.attendance');
    Route::get('/admin/attendance/staff/{user}/csv', [StaffAttendanceController::class, 'exportCsv'])
       ->name('staff.attendance.csv');
});