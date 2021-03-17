<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', 'HomeController@index')->name('index');

Route::get('/oauth/redirect', 'OauthController@redirect');
Route::get('/oauth/callback', 'OauthController@callback');

Route::get('/bigquery', 'HubspotController@bigQuery');

Route::get('/hubspot', 'HubspotController@hubspot');

Route::get('/create-ticket', 'HubspotController@createTicket');


Route::get('/refresh-token', 'HubspotController@refreshToken');

//Share Point
Route::get('/demo', 'HubspotController@sharePoint');

Route::get('/demo2', 'SharePointController@downloadFile');


//Api Key
Route::get('/apiKey', 'ApiKeyController@create');

Route::get('/testApi', 'HubspotController@testApi');