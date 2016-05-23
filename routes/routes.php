<?php

Route::group(['prefix' => 'billplz'], function () {
    Route::any('webhook', '\Klsandbox\BillplzRoute\Http\Controllers\BillplzController@postWebhook');
});

?>