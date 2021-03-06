<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Product;
use App\Models\Proxy;
use App\Models\Site;
use App\Models\Variant;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetProducts implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * @var \App\Models\Site
     */
    public $site;

    /**
     * @var false|string
     */
    public $dataBase;
    /**
     * @var
     */
    public $catalog;
    /**
     * @var int
     */
    public $tries = 3;
    /**
     * @var int[]
     */
    public $backoff = [ 600, 1200, 1200 ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
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


        $this->catalog = Catalog::where('status', '=', 'ENABLED')->where('site_id', '=', $this->site->id)->first();

        if (is_null($this->catalog))
        {
            return;
        }

        $this->catalog->products()->update([ 'status' => 'DISABLED' ]);


        $request = new Request('GET', $this->site->product_json_path);
        $page = 1;

        do
        {

            $response = $client->send(
                $request,
                [
                    'query' => [
                        'page'  => $page ++,
                        'limit' => 1000
                    ],
//                    'proxy' => Proxy::inRandomOrder()->first()->ip
                ]
            );
            if ($response->getStatusCode() == 200)
            {
                $data = json_decode($response->getBody()->getContents(), false);

                if (!empty($data->products))
                {
                    $products = [];
                    $variants = [];
                    $productCatalogRelation = [];

                    foreach ( $data->products as $product )
                    {
                        list($arr, $variants) = $this->prepareProductData($product, $variants);
                        $products[] = $arr;
                        $productCatalogRelation[] = [ 'catalog_id' => $this->catalog->id, 'product_id' => $product->id, 'site_id' => $this->catalog->site->id ];
                    }


                    Product::upsert($products, [ 'id', 'site_id' ], array_keys($products[0]));
                    Product::whereIn('id', array_map(function ($product) {
                        return $product['id'];
                    }, $products))->whereNull('first_scrape')->update([
                        'first_scrape' => Carbon::now()
                    ]);
                    Variant::upsert($variants, [ 'id', 'product_id' ], array_keys($variants[0]));
                    DB::table('catalog_product')->upsert($productCatalogRelation, [ 'catalog_id', 'product_id', 'site_id' ], array_keys($productCatalogRelation[0]));
                }
                sleep(5);
            }

        } while ( $response->getStatusCode() == 200 && !empty($data->products) );

    }

    /**
     * @param $product
     * @param array $variants
     * @return array
     */
    private
    function prepareProductData($product, array $variants): array
    {
        $arr = [];
        $arr['id'] = $product->id;
        $arr['title'] = $product->title;
        $arr['type'] = $product->product_type;
        $arr['handle'] = $product->handle;
        $arr['created_at'] = $product->created_at;
        $arr['updated_at'] = $product->updated_at;
        $arr['published_at'] = $product->published_at;
        $arr['position'] = null;
        $arr['quantity'] = null;
        $arr['status'] = 'ENABLED';
        $arr['site_id'] = $this->catalog->site->id;


        if (array_key_exists('0', $product->images))
        {
            $arr["image"] = $product->images[0]->src;
        } else
        {
            $arr["image"] = '';
        }

        for ( $i = 0; $i < count($product->variants); $i ++ )
        {
            $variant['product_id'] = $product->id;
            $variant['id'] = $product->variants[$i]->id;
            $variant['sku'] = $product->variants[$i]->sku;
            $variants[] = $variant;
        }

        return array( $arr, $variants );
    }
}
