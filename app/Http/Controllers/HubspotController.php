<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccessToken;
use Illuminate\Support\Facades\Http;
use Google\Cloud\BigQuery\BigQueryClient;
class HubspotController extends Controller
{
    public $accessToken;
    public $bigQuery;
    public $paramsApiHubspot;
    
    public function __construct()
    {
        $this->accessToken = AccessToken::first();

        $this->checkToken();

        $this->accessToken = AccessToken::first();

        $key_gg = config('services.gg_key');
        
        $params = [
            "type" => "service_account",
            "project_id" => $key_gg['project_id'],
            "private_key_id" => $key_gg['private_key_id'],
            "private_key" => $key_gg['private_key'],
            "client_email" => $key_gg['client_email'],
            "client_id" => $key_gg['client_id'],
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/vht-733%40dbvht-306709.iam.gserviceaccount.com"
        ];
        
        $this->bigQuery = new BigQueryClient([
            'keyFile' => $params
        ]);

        $this->paramsApiHubspot = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' .  $this->accessToken->access_token,
            'scope' => 'contacts',
        ];
    }

    /**
     * return data contact api vht
     */
    public function getContactVHT()
    {
        $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' .  '1175226112144590545:Zb92GaqTYGeMQhyAWtglsYNSzHWY6b1d'
                ])->get('https://api-dev.etelecom.vn/portsip-pbx/v1/cdr');

        $result = $response->json();
        
        return $result;
    }

    /**
     * return data contact hubspot
     */
    public function getAllContactHubspot()
    {
        $response = Http::withHeaders($this->paramsApiHubspot)->get('https://api.hubapi.com/crm/v3/objects/contacts');

        $result = $response->json();

        return $result;
    }

    /**
     * slect data from api vht and insert data to table history_contact bigQuery
     */
    public function bigQuery()
    {
        $result = $this->getContactVHT();
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $datasetId = 'vht';
        $tableId = 'history_contact';

        $dataset = $this->bigQuery->dataset($datasetId);
        $table = $dataset->table($tableId);

        $data = [];

        foreach($result['sessions'] as $item){

            $data['first_name'] = $item['first_name'] ?? "";
            $data['last_name'] = $item['last_name'] ?? "";
            $item['direction'] == 'out' ? $data['phone'] = $item['callee'] : $data['phone'] = $item['caller'];
            $data['email'] = $item['email'] ?? $data['phone'].'@gmail.com';
            isset($item['email']) ? $data['status'] = 1 : $data['status'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['recording_file_url'] = $item['recording_file_url'];
            
            $insertResponse = $table->insertRows([
                ['data' => $data],
            ]);

        }

        if ($insertResponse->isSuccessful()) {
            var_dump('Data streamed into BigQuery successfully' . PHP_EOL);
        } else {
            foreach ($insertResponse->failedRows() as $row) {
                foreach ($row['errors'] as $error) {
                    var_dump('%s: %s' . PHP_EOL, $error['reason'], $error['message']);
                }
            }
        }

        return $this->hubspot($dataset);
    }

    /**
     * select data from table history_contact (2 minute ago) bigQuery and insert to Hubspot and table list_contact BigQuery
     */
    public function hubspot($dataset)
    {
        $queryHistoryContact = $this->bigQuery->query("SELECT distinct phone, first_name, last_name, email, recording_file_url
                                                    FROM `dbvht-306709.vht.history_contact`
                                                    WHERE created_at >= DATETIME_SUB(CURRENT_DATETIME('Asia/Ho_Chi_Minh'), INTERVAL 2 MINUTE)");
        $queryResults = $this->bigQuery->runQuery($queryHistoryContact);

        $tableId = 'list_contact';
        $table = $dataset->table($tableId);

        if ($queryResults->isComplete()) {
            $rows = $queryResults->rows();

            foreach ($rows as $row) {
                $arr = [
                    'properties' => [
                        "email" => $row['email'] ?? "",
                        "firstname" => $row['first_name'] ?? "",
                        "lastname" => $row['last_name'] ?? "",
                        "phone" => $row['phone'] ?? "",
                    ]
                ];
                $this->listContactHubspotBigQuery($arr, $table, $row);
            }
        }
        // return $this->sharePoint();
    }

    /**
     *
     */
    public function listContactHubspotBigQuery($arr, $table, $row)
    {
        $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/objects/contacts', $arr);
        $body = $response->json();

        if(array_key_exists('id', $body)) {
            $id_ticket = $this->createAssociation($body['id']);

            $data = [];

            $data['id_contact'] = $body['id'] ?? "";
            $data['id_ticket'] = $id_ticket ?? "";
            $data['first_name'] = $body['properties']['first_name'] ?? "";
            $data['last_name'] = $body['properties']['last_name'] ?? "";
            $data['email'] = $body['properties']['email'] ?? "";
            $data['phone'] = $body['properties']['phone'] ?? "";
            $data['video_status'] = 0;
            $data['recording_file_url'] = $row['recording_file_url'];

            $table->insertRows([
                ['data' => $data],
            ]);
        }
    }

    /**
     *
     */
    public function createAssociation($contact_id)
    {
        $ticket_id = $this->createTicket($contact_id);

        $data = [
            "inputs" => [
                [
                    "from" => [
                        "id" => $contact_id
                    ],
                    "to" => [
                        "id" => $ticket_id
                    ],
                    "type" => "contact_to_ticket"
                ]
            ]
        ];

        $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/associations/contact/ticket/batch/create', $data);

        return $ticket_id;
    }

    /**
     *
     */
    public function createTicket($contact_id)
    {
        $data = [
            'properties' => [
                "hs_pipeline" => "11561288",
                "hs_pipeline_stage" => "11561289",
                "hs_ticket_priority" => "HIGH",
                "hubspot_owner_id" => "54760307",
                "subject" => $contact_id
            ]
        ];

        $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/objects/tickets', $data)->json();

        return $response['id'];
    }

    /**
     * update Ticket
     */
    public function updateTicket($ticket_id, $urlSharepoint)
    {
        $data = [
            'properties' => [
                "content" => $urlSharepoint
            ]
        ];

        $response = Http::withHeaders($this->paramsApiHubspot)->patch('https://api.hubapi.com/crm/v3/objects/tickets/'.$ticket_id, $data);
    }




    // hieulv

    public function sharePoint()
    {

        $ECM_URL = "https://pvcombankcomvn.sharepoint.com";

        //get all contact
        $queryHistoryContact = $this->bigQuery->query("SELECT id_contact, id_ticket, phone, email, recording_file_url
                                                    FROM `dbvht-306709.vht.list_contact`
                                                    WHERE video_status = 0");
        $queryResults = $this->bigQuery->runQuery($queryHistoryContact);
        if ($queryResults->isComplete()) {
            $rows = $queryResults->rows();
            foreach ($rows as $row) {
                $response = Http::asForm()->post('https://accounts.accesscontrol.windows.net/be74c413-5fb5-4590-829d-8f82890ad219/tokens/OAuth/2', [
                    'grant_type' => 'client_credentials',
                    'client_id' => '5c040aa3-2c27-4fef-bc5f-dfc26871d2d6@be74c413-5fb5-4590-829d-8f82890ad219',
                    'client_secret' => 'h9gnnOW5PppAo3wOmzln631fwQc/kU4CI+1uJ0P7jZ4=',
                    'resource' => '00000003-0000-0ff1-ce00-000000000000/pvcombankcomvn.sharepoint.com@be74c413-5fb5-4590-829d-8f82890ad219',
                ])->json();

                //download file voice, video
                // $filename = $this->downloadFile($row['recording_file_url']);
               $filename = $this->downloadFile('https://sip.etelecom.vn:8883/filedown/2,91aa6ed4f1a9?filename=1168466670_pv73T11EaJk0TRX8r0s3WQ_20210309-115448.avi');

                //    dd(($filename));
                if (!empty($filename)) {
                    $file_upload = $this->uploadFileSharePoint($response['access_token'], file_get_contents(realpath(base_path() . '/public/video/' . $filename)), $row, $filename);

                    if (!isset($file_upload['error'])) {
                        $this->removeFileDownload('video/' . $filename);
                        $urlSharepoint = $ECM_URL . $file_upload['d']['ServerRelativeUrl'];
                        $this->updateTicket($row['id_ticket'], $urlSharepoint);
                        
                        // $query  = 'UPDATE `dbvht-306709.vht.list_contact` SET video_status = 1 WHERE id_contact = @id_contact;';
                        // $queryJobConfig = $this->bigQuery->query($query)
                        //                 ->parameters([
                        //                     'id_contact' => (int)$row['id_contact']
                        //                 ]);
                        // $queryResults = $this->bigQuery->runQuery($queryJobConfig);
                    }
                }

                print_r('<pre>');
                print_r($file_upload);
                
            }
        }

    }

    public function uploadFileSharePoint($access_token, $path_file, $row, $filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pvcombankcomvn.sharepoint.com/sites/ECM-PoC/_api/Web/GetFolderByServerRelativePath(decodedurl='/sites/ECM-PoC/Shared%20Documents/DMP')/Files/add(overwrite=true,url='".$row['id_contact'].'-'.$row['phone'].'.'.$ext."')",
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

    public function downloadFile($url)
    { 
        if ($url == '?filename=' || $url == '') {
            return false;
        } else {
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
    }

    //remove file
    public function removeFileDownload($filename_path)
    {
        if (file_exists($filename_path)) {
            @unlink($filename_path);
        }

    }
    
    //check
    public function checkToken()
    {
        $token = $this->accessToken['access_token'];
        $response = Http::get('https://api.hubapi.com/oauth/v1/access-tokens/'.$token);
        $body = $response->json();
        if(array_key_exists('status', $body) && $body['status'] === 'error'){
            $this->refreshToken();
        }
    }

    public function refreshToken()
    {
        $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.hubspot.client_id'),
            'client_secret' => config('services.hubspot.client_secret'),
            'redirect_uri' => 'http://localhost:8000/oauth/callback',
            'refresh_token' => $this->accessToken['refresh_token']
        ]);
        
        $body = $response->json();
       
        $model = AccessToken::first();
        $model->access_token = $body['access_token'];
        $model->refresh_token = $body['refresh_token'];
        $model->save();
    }
}
