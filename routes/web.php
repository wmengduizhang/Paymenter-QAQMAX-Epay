<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\QAQMAX\QAQMAX;

// QAQMAX notify_url (doc says GET, but we accept GET/POST)
Route::match(['GET', 'POST'],
    '/extensions/gateways/qaqmax/webhook',
    [QAQMAX::class, 'webhook']
)->name('extensions.gateways.qaqmax.webhook');

// return_url: after payment, redirect back to invoice page
Route::get('/extensions/gateways/qaqmax/return/{invoiceId}', function ($invoiceId) {
    return redirect()->route('invoices.show', ['invoice' => $invoiceId]);
})->name('extensions.gateways.qaqmax.return');

// optional cancel route (not used by QAQMAX, kept for consistency)
Route::get('/extensions/gateways/qaqmax/cancel/{invoiceId}', function ($invoiceId) {
    return redirect()->route('invoices.show', ['invoice' => $invoiceId]);
})->name('extensions.gateways.qaqmax.cancel');
