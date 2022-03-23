<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\QuickSaleController;
use App\Http\Controllers\Client\QuickSaleHistoriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\Properties\MainPropertyController;
use App\Http\Controllers\Properties\PropertyController;
use App\Http\Controllers\Properties\PropertyGroupsController;
use App\Http\Controllers\Properties\PropertyTypesController;
use App\Http\Controllers\UserController;
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
    Route::post('user_type', [AuthController::class, 'auth_user_type']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('complete-profile', [AuthController::class, 'complete_profile'])->name('complete_profile');
});

Route::group(['prefix' => 'analytics'], function ($router) {
   Route::get('/properties', [AnalyticsController::class, 'getPropertyCount']);
});

Route::group(['middleware' => 'api'], function ($router) {
   // Property Route
   Route::prefix('properties')->name('properties.')->group(function() {
        Route::post('/all', [PropertyController::class, 'index'])->name('index')->withoutMiddleware('api');
        Route::get('/{id}', [PropertyController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [PropertyController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/create', [PropertyController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('dashboard')->name('dashboard')->group(function() {
        Route::get('/stats', [DashboardController::class, 'fetch_dashboard_stats']);
        Route::get('/chart_data', [DashboardController::class, 'fetch_dashboard_chart_data']);
        Route::get('/table_data', [DashboardController::class, 'fetch_dashboard_table_data']);
    });

    Route::prefix('property_types')->name('property_types.')->group(function() {
        Route::post('/all', [PropertyTypesController::class, 'index'])->name('index');
        Route::get('/{id}', [PropertyTypesController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [PropertyTypesController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/create', [PropertyTypesController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyTypesController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyTypesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('main_properties')->name('main_properties.')->group(function() {
        Route::post('/all', [MainPropertyController::class, 'index'])->name('index');
        Route::get('/{id}', [MainPropertyController::class, 'show'])->name('show');
        Route::put('/toggle-status/{id}', [MainPropertyController::class, 'toggle_status'])->name('toggle_status');
        Route::post('/create', [MainPropertyController::class, 'store'])->name('store');
        Route::post('/update/{id}', [MainPropertyController::class, 'update'])->name('update');
        Route::delete('/{id}', [MainPropertyController::class, 'destroy'])->name('destroy');
        Route::post('/add-more', [MainPropertyController::class, 'add_more'])->name('add_more');
        Route::prefix('manage_groups')->name('manage_groups.')->group(function() {
            Route::post('/', [MainPropertyController::class, 'allocate_groups'])->name('allocate_groups');
            Route::post('/{id}', [MainPropertyController::class, 'edit_allocate_groups'])->name('edit_allocate_groups');
        });
    });

    Route::prefix('property_groups')->name('property_groups.')->group(function() {
        Route::post('/all', [PropertyGroupsController::class, 'index'])->name('index');
        Route::get('/{id}', [PropertyGroupsController::class, 'show'])->name('show');
        Route::post('/', [PropertyGroupsController::class, 'store'])->name('store');
        Route::post('/update/{id}', [PropertyGroupsController::class, 'update'])->name('update');
        Route::delete('/{id}', [PropertyGroupsController::class, 'destroy'])->name('destroy');
    });


    Route::prefix('users')->name('users.')->group(function() {
        Route::get('/fetch', [UserController::class, 'fetch_all_users'])->name('fetch');
    });


});


//Client

Route::prefix('client')->name('client')->group(function() {
    Route::post('/all-main-properties', [ClientController::class, 'index'])->name('all');
    Route::get('/single-main-property/{id}', [ClientController::class, 'show'])->name('single');
    Route::get('/main-property-group/{id}', [ClientController::class, 'single_group'])->name('single_group');
    Route::group(['middleware' => 'api'],function() {

        Route::post('/checkout', [ClientController::class, 'checkout'])->name('checkout')->middleware('api');
        Route::get('/callback/{transaction_id}', [ClientController::class, 'callback'])->name('callback')->middleware('api');

        Route::prefix('my-investments')->group(function() {
            Route::post('/', [ClientController::class, 'investment_index'])->name('investment_index');
            Route::post('/quick-sale', [QuickSaleController::class, 'sell_portion'])->name('sell_portion');
            Route::post('/quick-sale-notification', [QuickSaleHistoriesController::class, 'sale_notification'])->name('sale_notification');
            Route::post('/reply-sale-notification', [QuickSaleHistoriesController::class, 'reply_sale_notification'])->name('reply_sale_notification');
            Route::post('/{id}', [ClientController::class, 'single_investment'])->name('single_investment');
        });

        Route::prefix('market-place')->group(function() {
            Route::post('/all', [QuickSaleHistoriesController::class, 'market_place'])->name('marketplace');

        });
        Route::prefix('chat')->name('chat.')->group(function() {
            Route::get('/', [MessageController::class, 'index']);
            Route::get('messages', [MessageController::class, 'fetchMessages']);
            Route::post('messages', [MessageController::class, 'sendMessage']);
        });

    });


});
Route::get('analytics/{id}', [ClientController::class, 'get_analytics']);

