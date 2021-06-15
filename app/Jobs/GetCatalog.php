<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Site;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetCatalog implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * @var \App\Models\Site
     */
    public $site;
    /**
     * @var string
     */
    public $dataBase;

    public $tries = 5;
    public $backoff = [ 120, 240, 600, 1200 ];


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
        $this->dataBase = substr(md5(microtime()), rand(0, 26), 5);
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {


        $client = new Client([ 'base_uri' => $this->site->url ]);

        $request = new Request('GET', $this->site->catalog_json_path);

        $response = $client->send($request,
            [
                //  'proxy' => Proxy::inRandomOrder()->first()->ip
            ]
        );


        if ($response->getStatusCode() == 200)
        {
            $catalogResponse = json_decode($response->getBody()->getContents(), false);

            if (!empty($collection = $catalogResponse->collection))
            {

                $catalog = $this->getCatalog($collection);
                Catalog::upsert(
                    $catalog,
                    array_keys($catalog),
                    [
                        'handle', 'title', 'description', 'updated_at'
                    ]
                );
            }
        }
        if ($response->getStatusCode() === 430)
        {
            $this->release(now()->addMinutes(10));
        }
    }

    /**
     * @param $collection
     * @return array
     */
    private function getCatalog($collection): array
    {
        $catalog = [];
        $catalog['catalog_id'] = $collection->id;
        $catalog['handle'] = $collection->handle;
        $catalog['title'] = $collection->title;
        $catalog['description'] = $collection->description;
        $catalog['published_at'] = $collection->published_at;
        $catalog['updated_at'] = $collection->updated_at;
        $catalog['site_id'] = $this->site->id;

        return $catalog;
    }

}
