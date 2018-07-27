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
 
	// Dashboard - first page in backoffice
    Route::get('/', ['uses' => 'Deliveries\DeliveriesController@dashboard'])->name('dashboard');
    
	// Deliveries
    Route::any('deliveries', 'Deliveries\DeliveriesController@storeDriversAndTimes')->name('deliveries.store.driver-times');
    Route::resource('deliveries', 'Deliveries\DeliveriesController', ['except' => 'show']);
    Route::get('/deliveries/{id}', 'Deliveries\DeliveriesController@show')->name('deliveries.show')->where('id', '[0-9]+');
    Route::get('/deliveries/{id}/modal', 'Deliveries\DeliveriesController@modal')->name('deliveries.modal');
	Route::get('deliveries/approve', 'Deliveries\DeliveriesController@approve')->name('deliveries.approve');
	Route::post('deliveries/approve-save', 'Deliveries\DeliveriesController@approvesave')->name('deliveries.approvesave');
    Route::get('deliveries/print', 'Deliveries\DeliveriesController@print')->name('deliveries.print');
    Route::get('deliveries/sort', 'Deliveries\DeliveriesController@sort')->name('deliveries.sort');
    Route::get('deliveries/cancel', 'Deliveries\DeliveriesController@cancel')->name('deliveries.cancel');
    Route::post('deliveries/order', 'Deliveries\DeliveriesController@storeOrderedDeliveries')->name('deliveries.order');
    Route::get('deliveries/order/confirm/{date}', 'Deliveries\DeliveriesController@confirmOrderedDeliveries')->name('deliveries.order.confirm');
    Route::post('deliveries/order/confirm/{date}', 'Deliveries\DeliveriesController@confirmDeliveries')->name('deliveries.order.confirmed');
