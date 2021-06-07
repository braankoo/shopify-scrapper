<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Proxy;
use App\Models\Site;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GetCatalogs implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * @var \App\Models\Site
     */
    public $site;
    /**
     * @var string
     */
    public $dataBase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
        $this->dataBase = str_replace('.', '', Str::random(15));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client([ 'base_uri' => $this->site->url ]);
        $this->generateExistingCatalogsTable();
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
                    'proxy' => Proxy::inRandomOrder()->first()->ip
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
                        $catalog['site_id'] = $this->site->id;
                        $catalog['active'] = 'true';
                        $catalogs[] = $catalog;
                    }
                    Catalog::upsert($catalogs, [ 'catalog_id', 'site_id' ], array_keys($catalogs[0]));

                    DB::table($this->dataBase)->upsert(
                        array_map(function ($catalog) {
                            return [
                                'catalog_id' => $catalog['catalog_id'],
                                'site_id'    => $this->site->id
                            ];
                        }, $catalogs), [ 'catalog_id', 'site_id' ], [ 'catalog_id', 'site_id' ]);
                }

            }

        } while ( !empty($response->getBody()->getContents()->collections) && $response->getStatusCode() == 200 );

        $this->deactivateRemovedCatalogs();
    }


    /**
     * return @void
     */
    private function generateExistingCatalogsTable()
    {
        DB::statement(
            "CREATE  TABLE {$this->dataBase}(
                id int unsigned auto_increment primary key,
                catalog_id bigint not null,
                site_id int,
                UNIQUE(catalog_id, site_id)
            )"
        );
    }


    /**
     * return @void
     */
    public function deactivateRemovedCatalogs()
    {
        Catalog::whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from($this->dataBase)
                ->whereRaw("catalogs.catalog_id = `{$this->dataBase}`.catalog_id")
                ->whereRaw("catalogs.site_id = `{$this->dataBase}`.site_id");
        })->update([
            'active' => 'false'
        ]);
        DB::statement("DROP TABLE `{$this->dataBase}`");
    }
}
