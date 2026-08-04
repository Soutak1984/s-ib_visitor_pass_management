<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Auth\MeController;
use App\Http\Controllers\Api\v1\SettingController;
use App\Http\Controllers\Api\v1\VisitorController;
use App\Http\Controllers\Api\v1\EmployeeController;
use App\Http\Controllers\Api\v1\LanguageController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\Auth\LoginController;
use App\Http\Controllers\Api\v1\Auth\LogoutController;
use App\Http\Controllers\Api\v1\PreRegisterController;
use App\Http\Controllers\Api\v1\Auth\RegisterController;
use App\Http\Controllers\Api\v1\PushNotificationController;

Route::group(['prefix' => 'v1'], function () {

    Route::post('login', [LoginController::class, 'action']); //done

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [LogoutController::class, 'action']); //done
        Route::get('me', [MeController::class, 'action']); //done
        Route::get('dashboard', [DashboardController::class, 'index']); //done
        Route::post('profile-update', [MeController::class, 'update']); //done
        Route::post('change-password', [MeController::class, 'changePassword']); //done
        Route::get('refresh-token', [MeController::class, 'refresh']); //done
    });

    //Emloyee
    Route::get('employee', [EmployeeController::class, 'index']); //done
    Route::get('employee/{id}/show', [EmployeeController::class, 'show']); //done


    //Pre-Register
    Route::get('preregister/', [PreRegisterController::class, 'index']); //done
    Route::post('preregister', [PreRegisterController::class, 'store']); //done
    Route::get('preregister/{id}/show', [PreRegisterController::class, 'show']); //done
    Route::post('preregister/{id}/', [PreRegisterController::class, 'update']); //done
    Route::delete('preregister/{id}', [PreRegisterController::class, 'destroy']); //done
    Route::post('preregister/check-preregister/find', [PreRegisterController::class, 'checkPreRegister']); //done
    Route::get('preregister/search/{keyWord}', [PreRegisterController::class, 'search']);


    //attendance
    Route::get('attendance/{date?}', [AttendanceController::class, 'index']);
    Route::get('attendance/user/status', [AttendanceController::class, 'getStatus']);
    Route::post('attendance/user/clock-in', [AttendanceController::class, 'clockIn']);
    Route::get('attendance/user/clock-out', [AttendanceController::class, 'clockOut']);

    //visitor
    Route::get('visitors/', [VisitorController::class, 'index']);
    Route::get('visitors/search/{keyWord}', [VisitorController::class, 'search']);
    Route::get('visitors/show/{id}', [VisitorController::class, 'show']);
    Route::post('visitors/add', [VisitorController::class, 'store']);
    Route::post('visitors/edit/{id}', [VisitorController::class, 'update']);
    Route::delete('visitors/delete/{id}', [VisitorController::class, 'destroy']);
    Route::get('visitor/check-out/{id}', [VisitorController::class, 'checkout']);
    Route::post('visitor/check-in', [VisitorController::class, 'checkin']);
    Route::post('visitor/check-in/validator', [VisitorController::class, 'checkinCheck']);
    Route::get('visitor/change-status/{id}/{status}',  [VisitorController::class, 'changeStatus']);
    Route::post('visitor/find_visitor/', [VisitorController::class, 'findVisitor']); //done

    Route::get('settings/', [SettingController::class, 'index']);
    Route::get('languages/', [LanguageController::class, 'index']);

    //push notification
    Route::post('fcm-subscribe', [PushNotificationController::class, 'fcmSubscribe']);
    Route::post('fcm-unsubscribe', [PushNotificationController::class, 'fcmUnsubscribe']);
});
