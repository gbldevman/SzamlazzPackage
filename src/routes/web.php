<?php

Route::get('szamlazz', function () {
    return config('gbl.agent_key');
});

Route::group(['namespace' => 'Gbl\Szamlazz\App\Http\Controller'], function()
{
    Route::get('createinvoice', 'SzamlazzController@createInvoice');
    Route::get('reverseinvoice', 'SzamlazzController@reverseInvoice');
    Route::get('registercreditentries', 'SzamlazzController@registerCreditEntries');
    Route::get('queryinvoicepdf', 'SzamlazzController@queryInvoicePdf');
    Route::get('queryinvoicexml', 'SzamlazzController@queryIncoiceXml');
    Route::get('deletingproformainvoice', 'SzamlazzController@deletingProFormaInvoice');
});
