<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckApiContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharepoint:call_api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call API Upload File Share Point';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        //get access token
        $response = Http::asForm()->post('https://accounts.accesscontrol.windows.net/be74c413-5fb5-4590-829d-8f82890ad219/tokens/OAuth/2', [
            'grant_type' => 'client_credentials',
            'client_id' => '5c040aa3-2c27-4fef-bc5f-dfc26871d2d6@be74c413-5fb5-4590-829d-8f82890ad219',
            'client_secret' => 'h9gnnOW5PppAo3wOmzln631fwQc/kU4CI+1uJ0P7jZ4=',
            'resource' => '00000003-0000-0ff1-ce00-000000000000/pvcombankcomvn.sharepoint.com@be74c413-5fb5-4590-829d-8f82890ad219',
        ])->json();


        // upload file sharepoint
        $demo = Http::attach('file','https://sip.vht.com.vn:8883/filedown/2,8fc12d534d5e?filename=1900780491_SA0FlBOAyQnvYPkafR3P-A_20210305-152135.wav')
            ->withHeaders([
                'Authorization' => 'Bearer ' . $response['access_token'],
                'Accept' => 'application/json;odata=verbose'
            ])->post("https://pvcombankcomvn.sharepoint.com/sites/ECM-PoC/_api/Web/GetFolderByServerRelativePath(decodedurl='/sites/ECM-PoC/Shared%20Documents/DMP')/Files/add(overwrite=true,url='demo.wav')")->json();

        dd($demo);

    }
}
