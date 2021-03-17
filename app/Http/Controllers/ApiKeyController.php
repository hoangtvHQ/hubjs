<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiKey;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function create()
    {
        $model = ApiKey::first();
        if (empty($model)) {
            $model = new ApiKey;
        }
        $model->api_key = Str::random(60);
        $model->save();

        return redirect()->route('index')->with('success', 'Done');
    }
}
