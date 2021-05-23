<?php

namespace App\Console\Commands;

use App\Models\Proxy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;

class TestProxies extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:proxies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        Proxy::each(function ($proxy) {
            print_r($proxy->ip);
            echo PHP_EOL;
            $client = new Client([ 'base_uri' => 'https://google.com' ]);
            $request = new Request('GET', '/');
            try
            {
                $client->send($request,
                    [
                        'proxy'           => $proxy->ip,
                        'connect_timeout' => 40
                    ]
                );

            } catch ( \Exception $e )
            {
                $proxy->delete();
            }

        });
    }
}
