<?php

use Illuminate\Http\Request;

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

Route::namespace('Api')->group(function () {
    Route::get('/products', 'ProductsController@index');
    Route::get('/checkout', 'CheckoutController@index');
    Route::post('/placeOrder', 'PlaceOrderController@index');



//    Route::get('/products/images', 'ProductsController@images');
//    Route::get('/products/{posterId}/images', 'ProductsController@getImage');
//
//    Route::get('/categories/{spotSlug}', 'CategoriesController@index');
//    Route::get('/categories/{spotSlug}/{slug}', 'CategoriesController@show');
//    Route::get('/products/{spotSlug}', 'ProductsController@index');
//
//
//
//    Route::post('/orders/send', 'OrdersController@send');
//
//    Route::get('/spots/get', 'SpotsController@get');
//    Route::get('/spots/getOne/{slug}', 'SpotsController@getOne');
    //Route::get('/categories/showPoster}', 'CategoriesController@showPoster');
});
