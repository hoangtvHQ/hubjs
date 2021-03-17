<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AccessToken;

class OauthController extends Controller
{
    public function redirect()
    {
        $query = http_build_query([
            'client_id' => config('services.hubspot.client_id'),
            'scope' => 'contacts tickets',
            'redirect_uri' => url('/oauth/callback')
        ]);
       
        return redirect(config('services.hubspot.url').'/oauth/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.hubspot.client_id'),
            'client_secret' => config('services.hubspot.client_secret'),
            'redirect_uri' => url('/oauth/callback'),
            'code' => $request->code
        ]);
        
        $body = $response->json();
       
        $model = AccessToken::first();
        if (empty($model)) {
            $model = new AccessToken;
        }
        $model->access_token = $body['access_token'];
        $model->refresh_token = $body['refresh_token'];
        $model->save();

        return redirect()->route('index')->with('success', 'Done');
    }
}
