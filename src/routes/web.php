<?php
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
require 'admin.php';

Route::match(['get', 'post'],'/', 'Site\IndexController@index');


Route::match(['get', 'post'], '/category/{slug}', 'Site\CategoryController@show')->name('category.show');


Route::get('/cart/get', 'Site\CartController@getCart')->name('checkout.cart.get');
Route::get('/cart/item/{id}/remove', 'Site\CartController@removeItem')->name('checkout.cart.remove');
Route::post('/cart/manipulate', 'Site\CartController@manipulate')->name('cart.manipulate');
Route::get('/cart/clear', 'Site\CartController@clearCart')->name('checkout.cart.clear');

Route::group(['prefix' => 'order'], function(){
    Route::get('/', 'Site\OrderController@index')->name('order.index');

    Route::post('/send', 'Site\OrderController@send')->name('order.send');
    Route::post( '/handle', 'Site\OrderController@handle');
});
Route::get('/{page}','Site\PageController@index');

Route::post('/poster-webhook', 'PosterWebhookController')->name('webhook.poster');

//Route::get('/migrate-salesbox', 'SalesboxMigrationController');









Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
