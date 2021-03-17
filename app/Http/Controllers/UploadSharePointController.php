<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UploadSharePointController extends Controller
{
    //
    public function index() {

        $response = Http::asForm()->post('https://accounts.accesscontrol.windows.net/be74c413-5fb5-4590-829d-8f82890ad219/tokens/OAuth/2', [
            'grant_type' => 'client_credentials',
            'client_id' => '5c040aa3-2c27-4fef-bc5f-dfc26871d2d6@be74c413-5fb5-4590-829d-8f82890ad219',
            'client_secret' => 'h9gnnOW5PppAo3wOmzln631fwQc/kU4CI+1uJ0P7jZ4=',
            'resource' => '00000003-0000-0ff1-ce00-000000000000/pvcombankcomvn.sharepoint.com@be74c413-5fb5-4590-829d-8f82890ad219',
        ])->json();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pvcombankcomvn.sharepoint.com/sites/ECM-PoC/_api/Web/GetFolderByServerRelativePath(decodedurl='/sites/ECM-PoC/Shared%20Documents/DMP')/Files/add(overwrite=true,url='test1.mp4')",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => file_get_contents(realpath(base_path().'/public/video/mov_bbb.mp4')),
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "authorization: Bearer " . $response['access_token'],
//                "content-type: application/json",
            ),
        ));
        $result = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        print_r('<pre>');
        print_r($result);
        if ($err) {
            var_dump("cURL Error #:" . $err);
        }
    }
}
