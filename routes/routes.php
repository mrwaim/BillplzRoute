<?php

Route::group(['prefix' => 'billplz'], function () {
    Route::any('webhook', '\Klsandbox\BillplzRoute\Http\Controllers\BillplzController@postWebhook');

    Route::group(['middleware' => ['auth.admin']], function () {
        Route::get('new-collection', 'Klsandbox\BillplzRoute\Http\Controllers\BillplzController@getNewCollection');
    });
});

?>