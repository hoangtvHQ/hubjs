<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\HubspotController;

class ContactCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contact:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get contact from VHT, save data to bigQuery and Hubspot';

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
        $controller = new HubspotController(); 
        $controller->bigQuery();
      
        $this->info('Contact:Cron Command run successfully!');
    }
}
