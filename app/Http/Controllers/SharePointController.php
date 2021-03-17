<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SharePointController extends Controller
{

    public function sharePoint()
    {

//         $ECM_URL = "https://pvcombankcomvn.sharepoint.com";
//
//        echo $ECM_URL.'/sites/ECM-PoC/Shared Documents/DMP/test_5h13.avi';
//
//        die();

        //get all contact
        $get_all_contact = app('App\Http\Controllers\HubspotController')->getContactVHT();
        dd($get_all_contact);

//        foreach ($get_all_contact['sessions'] as $value) {

//            get access token
        $response = Http::asForm()->post('https://accounts.accesscontrol.windows.net/be74c413-5fb5-4590-829d-8f82890ad219/tokens/OAuth/2', [
            'grant_type' => 'client_credentials',
            'client_id' => '5c040aa3-2c27-4fef-bc5f-dfc26871d2d6@be74c413-5fb5-4590-829d-8f82890ad219',
            'client_secret' => 'h9gnnOW5PppAo3wOmzln631fwQc/kU4CI+1uJ0P7jZ4=',
            'resource' => '00000003-0000-0ff1-ce00-000000000000/pvcombankcomvn.sharepoint.com@be74c413-5fb5-4590-829d-8f82890ad219',
        ])->json();

        //download file voice, video
        $filename = $this->downloadFile('https://sip.etelecom.vn:8883/filedown/2,91aa6ed4f1a9?filename=1168466670_pv73T11EaJk0TRX8r0s3WQ_20210309-115448.avi');

        $file_upload = $this->uploadFileSharePoint($response['access_token'], file_get_contents(realpath(base_path() . '/public/video/' . $filename)));

        if (!isset($file_upload['error'])) {
            $this->removeFileDownload('video/' . $filename);
        }

        print_r('<pre>');
        print_r($file_upload);
//        }

    }

    public function uploadFileSharePoint($access_token, $path_file)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pvcombankcomvn.sharepoint.com/sites/ECM-PoC/_api/Web/GetFolderByServerRelativePath(decodedurl='/sites/ECM-PoC/Shared%20Documents/DMP')/Files/add(overwrite=true,url='test_5h13.avi')",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $path_file,
            CURLOPT_HTTPHEADER => array(
                "accept: application/json;odata=verbose",
                "authorization: Bearer " . $access_token,
//                "content-type: application/json",
            ),
        ));
        $result = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            var_dump("cURL Error #:" . $err);
        }

        return json_decode($result, true);

    }

    public function downloadFile($url = '')
    {
        if ($url == '?filename=' || $url == '') {
            dd(123);
        }
        $my_save_dir = 'video/';
        if (!empty(parse_url($url)['query'])) {
            $parts = parse_url($url);
            parse_str($parts['query'], $query);
            $filename = $my_save_dir . $query['filename'];
        } else {
            $filename = $my_save_dir . basename($url);

        }

        file_put_contents($filename, file_get_contents($url));
        return basename($filename);

    }

    //remove file
    public function removeFileDownload($filename_path)
    {
        if (file_exists($filename_path)) {
            @unlink($filename_path);
        }

    }
}
