<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessToken;
use Illuminate\Support\Facades\Http;
use Google\Cloud\BigQuery\BigQueryClient;

class HubJSController extends Controller
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


    public function createContact(Request $request)
    {
        if(isset($request->phone)) {
            $arr = [
                'properties' => [
                    "email" => $request->email ?? $request->phone.'@gmail.com',
                    "firstname" => $request->first_name ?? "",
                    "lastname" => $request->last_name ?? "",
                    "phone" => $request->phone ?? "",
                ]
            ];
            
            $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/objects/contacts', $arr);
            $body = $response->json();
    
            return $this->createContactBigQuery($body);
        } else {
            return response()->json('Something wrong, please try again!');
        }
    }
    
    public function createContactBigQuery($body)
    {
        if(array_key_exists('id', $body)) {
            $tableId = 'list_contact';
            $datasetId = 'vht';
            $dataset = $this->bigQuery->dataset($datasetId);
            $table = $dataset->table($tableId);

            $id_ticket = $this->createAssociation($body['id']);

            $data = [];

            $data['id_contact'] = $body['id'] ?? "";
            $data['id_ticket'] = $id_ticket ?? "";
            $data['first_name'] = $body['properties']['first_name'] ?? "";
            $data['last_name'] = $body['properties']['last_name'] ?? "";
            $data['email'] = $body['properties']['email'] ?? "";
            $data['phone'] = $body['properties']['phone'] ?? "";

            $table->insertRows([
                ['data' => $data],
            ]);
        }

        return response()->json($body);
    }

    /**
     *
     */
    public function createAssociation(Request $request)
    {
        if(isset($request->contact_id)) {
            $ticket_id = $this->createTicket($request);

            $data = [
                "inputs" => [
                    [
                        "from" => [
                            "id" => $request->contact_id
                        ],
                        "to" => [
                            "id" => $ticket_id
                        ],
                        "type" => "contact_to_ticket"
                    ]
                ]
            ];

            $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/associations/contact/ticket/batch/create', $data)->json();

            return response()->json($response);
        } else {
            return response()->json('Something wrong, please try again!');
        }
    }

    /**
     *
     */
    public function createTicket($request)
    {
        $data = [
            'properties' => [
                "hs_pipeline" => "11561288",
                "hs_pipeline_stage" => "11561289",
                "hs_ticket_priority" => "HIGH",
                "hubspot_owner_id" => "54760307",
                "subject" => $request->subject ?? $request->contact_id
            ]
        ];

        $response = Http::withHeaders($this->paramsApiHubspot)->post('https://api.hubapi.com/crm/v3/objects/tickets', $data)->json();

        return $response['id'];
    }

    /**
     * update Ticket
     */
    public function updateTicket(Request $request)
    {
        if(isset($request->ticket_id, $request->content)) {
            $data = [
                'properties' => [
                    "content" => $request->content
                ]
            ];
            
            if(isset($request->subject)){
                $data['properties']['subject'] =  $request->subject;
            }

            $response = Http::withHeaders($this->paramsApiHubspot)->patch('https://api.hubapi.com/crm/v3/objects/tickets/'.$request->ticket_id, $data)->json();
            return response()->json($response);
        } else {
            return response()->json('Something wrong, please try again!');
        }
    }

    public function deleteTicket(Request $request)
    {
        if(isset($request->ticket_id)) {
            $response = Http::withHeaders($this->paramsApiHubspot)->delete('https://api.hubapi.com/crm/v3/objects/tickets/'.$request->ticket_id);
            return response()->json("Deleted ticket successfully");
        } else {
            return response()->json('Something wrong, please try again!');
        }
    }

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
