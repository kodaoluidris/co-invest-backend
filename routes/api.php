<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\Properties\MainPropertyController;
use App\Http\Controllers\Properties\PropertyController;
use App\Http\Controllers\Properties\PropertyGroupsController;
use App\Http\Controllers\Properties\PropertyTypesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/reset-password-request', [PasswordResetRequestController::class, 'sendPasswordResetEmail']);
Route::post('/change-password', [ChangePasswordController::class, 'passwordResetProcess']);

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('complete-profile', [AuthController::class, 'complete_profile'])->name('complete_profile');

    

});
Route::group(['middleware' => 'api'], function ($router) {
   // Property Route
   Route::prefix('properties')->name('properties.')->group(function() {
        Route::post('/all', [PropertyController::class, 'index'])->name('index');
        Route::get('/{id}', [PropertyController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [PropertyController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/', [PropertyController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('property_types')->name('property_types.')->group(function() {
        Route::post('/all', [PropertyTypesController::class, 'index'])->name('index');
        Route::get('/{id}', [PropertyTypesController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [PropertyTypesController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/', [PropertyTypesController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyTypesController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyTypesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('main_properties')->name('main_properties.')->group(function() {
        Route::post('/all', [MainPropertyController::class, 'index'])->name('index');
        Route::get('/{id}', [MainPropertyController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [MainPropertyController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/', [MainPropertyController::class, 'store'])->name('store');
        Route::post('/update/{id}', [MainPropertyController::class, 'update'])->name('update');
        Route::delete('/{id}', [MainPropertyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('property_groups')->name('property_groups.')->group(function() {
        Route::post('/all', [PropertyGroupsController::class, 'index'])->name('index');
        Route::get('/{id}', [PropertyGroupsController::class, 'show'])->name('show');
        Route::post('/', [PropertyGroupsController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyGroupsController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyGroupsController::class, 'destroy'])->name('destroy');
    });

    

});
