<?php

namespace App\Console\Commands;

use App\Jobs\GetData;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class PrepareData extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare Data';

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
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {
        Site::each(function ($site) {

            Bus::batch([
                new \App\Jobs\GetCatalog($site),
                new \App\Jobs\GetProducts($site),
                new GetData($site)
            ])->allowFailures(false)->then(function ($e) {
                //initialize node
            })->dispatch();
        });
    }
}


