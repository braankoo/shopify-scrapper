<?php

namespace App\Console\Commands;


use App\Models\Catalog;
use App\Models\Product;
use App\Models\Proxy;
use App\Models\Variant;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GetProducts extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get products from shopify JSON';

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {


        $this->generateProductsTemporaryTable();

        Catalog::each(function ($catalog) {

            $client = new Client([ 'base_uri' => $catalog->site->url ]);
            $request = new Request('GET', "{$catalog->url}/products.json");

            $page = 0;
            do
            {
                $response = $client->send($request,
                    [
                        'query' => [
                            'page'  => $page ++,
                            'limit' => '100'
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
                        foreach ( $data->products as $key => $product )
                        {
                            $arr = [];
                            $arr['product_id'] = $product->id;
                            $arr['title'] = $product->title;
                            $arr['type'] = $product->product_type;
                            for ( $i = 0; $i < 3; $i ++ )
                            {
                                if (array_key_exists($i, $product->images))
                                {
                                    $arr["image_" . ($i + 1)] = $product->images[$i]->src;
                                } else
                                {
                                    $arr["image_" . ($i + 1)] = null;
                                }
                            }
                            for ( $i = 0; $i < count($product->variants); $i ++ )
                            {
                                $variant['product_id'] = $product->id;
                                $variant['variant_id'] = $product->variants[$i]->id;
                                $variant['sku'] = $product->variants[$i]->sku;
                                $variant['price'] = $product->variants[$i]->price;
                                $variant['position'] = $product->variants[$i]->position;
                                $variants[] = $variant;
                            }
                            $arr['tags'] = json_encode($product->tags);
                            $arr['link'] = "/products/{$product->handle}";
                            $arr['created_at'] = $product->created_at;
                            $arr['updated_at'] = $product->updated_at;
                            $arr['published_at'] = $product->published_at;
                            $arr['site_id'] = $catalog->site->id;
                            $products[] = $arr;
                            $productCatalogRelation[] = [ 'catalog_id' => $catalog->catalog_id, 'product_id' => $product->id, 'site_id' => $catalog->site->id ];
                        }

                        Product::upsert($products, [ 'product_id', 'site_id' ], array_keys($products[0]));

                        Product::whereIn('product_id', array_map(function ($product) {
                            return $product['product_id'];
                        }, $products))->whereNull('first_scrape')->update([
                            'first_scrape' => Carbon::now()
                        ]);

                        DB::table('existing_products')->upsert(
                            array_map(
                                function ($product) {
                                    return [
                                        'product_id' => $product['product_id'],
                                        'site_id'    => $product['site_id']
                                    ];
                                }, $products), [ 'product_id', 'site_id' ], [ 'product_id', 'site_id' ]);

                        Variant::upsert($variants, [ 'product_id' ], array_keys($variants[0]));

                        DB::table('catalog_product')->upsert($productCatalogRelation, [ 'catalog_id', 'product_id', 'site_id' ], array_keys($productCatalogRelation[0]));

                    }

                }


            } while ( !(empty($response->getBody()->getContents()->products)) && $response->getStatusCode() == 200 );

        });

        $this->deactivateRemovedProducts();
    }


    private function generateProductsTemporaryTable()
    {
        DB::statement(
            "CREATE TABLE existing_products(
                id int unsigned auto_increment primary key,
                product_id bigint not null,
                site_id int,
                UNIQUE(product_id, site_id)
            )"
        );
    }

    private function deactivateRemovedProducts()
    {
        Product::whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('existing_products')
                ->whereRaw('products.product_id = existing_products.product_id');
        })->update([
            'active' => 'false'
        ]);

        DB::statement('DROP TABLE existing_products');
    }
}
