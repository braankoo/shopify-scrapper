<?php

namespace App\Console\Commands;

use App\Models\Catalog;
use App\Models\Proxy;
use App\Models\Site;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;

class GetCatalogs extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:catalogs';

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        Site::each(function ($site) {
            $client = new Client([ 'base_uri' => $site->url ]);

            $collectionPage = 0;
            do
            {
                $request = new Request('GET', "/collections.json");

                $response = $client->send($request,
                    [
                        'query' => [
                            'page'  => $collectionPage ++,
                            'limit' => '100'
                        ],
//                        'proxy' => Proxy::inRandomOrder()->first()->ip
                    ]
                );

                if ($response->getStatusCode() == 200)
                {
                    $catalogsInResponse = json_decode($response->getBody()->getContents(), false);

                    $catalogs = [];
                    if (!empty($catalogsInResponse->collections))
                    {
                        foreach ( $catalogsInResponse->collections as $catalogInResponse )
                        {
                            $catalog = [];
                            $catalog['catalog_id'] = $catalogInResponse->id;
                            $catalog['url'] = '/collections/' . $catalogInResponse->handle;
                            $catalog['name'] = $catalogInResponse->title;
                            $catalog['site_id'] = $site->id;
                            $catalogs[] = $catalog;
                        }
                        Catalog::upsert($catalogs, [ 'catalog_id', 'site_id' ], array_keys($catalogs[0]));
                    }

                }

            } while ( !empty($response->getBody()->getContents()->collections) && $response->getStatusCode() == 200 );
        });
    }
}
