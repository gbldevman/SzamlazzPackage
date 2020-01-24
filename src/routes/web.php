<?php

Route::get('szamlazz', function () {
    return config('gbl.agent_key');
});

Route::group(['namespace' => 'Gbl\Szamlazz\App\Http\Controller'], function()
{
    Route::get('createinvoice', 'SzamlazzInvoiceController@createInvoice');
    Route::get('reverseinvoice', 'SzamlazzInvoiceController@reverseInvoice');
    Route::get('registercreditentries', 'SzamlazzInvoiceController@registerCreditEntries');
    Route::get('queryinvoicepdf', 'SzamlazzInvoiceController@queryInvoicePdf');
    Route::get('queryinvoicexml', 'SzamlazzInvoiceController@queryIncoiceXml');
    Route::get('deletingproformainvoice', 'SzamlazzInvoiceController@deletingProFormaInvoice');
});
