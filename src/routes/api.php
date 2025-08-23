<?php

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

use Illuminate\Support\Facades\Auth;

Route::namespace('Api')->group(function () {
    Route::get('/products', 'ProductsController@index');
    Route::get('/data', 'DataController@index');
    Route::get('/checkout', 'CheckoutController@index');
    Route::post('/placeOrder', 'PlaceOrderController@index');

    Route::get('/bonuses/options', function() {
        return [
            'bonus_enabled' => true,
            'bonus_rate' => 0.05,
            'max_bonus' => 50,
            'get_bonus_from_used_bonus' => false
        ];
    });

    Route::get('/app-version', function() {
        return [
            'android' => '0.0.1',
            'ios'=> '0.0.1',
            'android_link'=> 'https://youtube.com',
            'ios_link'=> 'https://youtube.com',
        ];
    });

    Route::get('/contacts', function() {
        return [
            'phones' => ['066 98 98 095', '098 98 98 095'],
            'instagram_display_text' => '',
            'instagram_app' =>  '',
            'instagram_web' => ''
        ];
    });

    Route::prefix('auth')->group(function () {
        Route::middleware('auth:api')->group(function () {
            Route::get('me', 'AuthController@me');
            Route::post('logout', 'AuthController@logout');
        });

        Route::post('login', 'AuthController@login');
        Route::post('register', 'AuthController@register');
        Route::post('refresh', 'AuthController@refresh');
        Route::post('restore-password', 'AuthController@restorePassword');

    });
});

