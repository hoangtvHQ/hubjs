<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['namespace' => 'Api', 'middleware' => 'auth.apikey'], function () {
    // api create contact
    Route::post('contact', 'HubJSController@createContact');

    // api create association
    Route::post('association', 'HubJSController@createAssociation');

    // api update ticket
    Route::post('update-ticket', 'HubJSController@updateTicket');

    // api delete ticket
    Route::post('delete-ticket', 'HubJSController@deleteTicket');
});