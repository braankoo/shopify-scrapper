<?php

namespace App\Console\Commands;

use App\Jobs\GetQuantityFromFrontEnd;
use App\Models\Historical;
use App\Models\Product;
use App\Models\Proxy;
use App\Models\Site;
use App\Models\Variant;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class PrepareHistoricalData extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare:historical';

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

        $this->checkedProducts = [];
        DB::table('historicals')->whereDate('date_created', Carbon::today())->delete();

        Site::each(function ($site) {

            $client = new Client([ 'base_uri' => $site->url ]);


            $site->catalogs()->each(function ($catalog) use ($client, $site) {

                $catalog->products()->each(function ($product) use ($client, $site, $catalog) {

                    do
                    {


                        $productsRequest = new Request('GET', "{$catalog->url}{$product->link}.json");


                        $productsResponse = $client->send($productsRequest,
                            [


//                            'proxy' => Proxy::inRandomOrder()->first()->ip

                            ]
                        );

                        if ($productsResponse->getStatusCode() == 200)
                        {
                            $productsData = json_decode($productsResponse->getBody()->getContents(), false);
                            if (!empty($productsData->product))
                            {
                                $product = $productsData->product;
                                $arr = [];

                                for ( $i = 0; $i < count($productsData->product->variants); $i ++ )
                                {
                                    $data = [];
                                    $data['variant_id'] = $product->variants[$i]->id;
                                    $data['product_id'] = $product->id;
                                    $data['price'] = (int) ($product->variants[$i]->price * 1000000);
                                    if (!empty($product->variants[$i]->compare_at_price))
                                    {
                                        $data['compare_at_price'] = (int) ($product->variants[$i]->compare_at_price * 1000000);
                                    } else
                                    {
                                        $data['compare_at_price'] = 0;
                                    }


                                    if (property_exists($product->variants[$i], 'inventory_quantity'))
                                    {

                                        $data['inventory_quantity'] = $product->variants[$i]->inventory_quantity;

                                    }

                                    $data['date_created'] = Carbon::today();
                                    $data['site_id'] = $site->id;
                                    $arr[] = $data;
                                }


                                $variantsWithQuantity = array_filter($arr, function ($variant) {
                                    return array_key_exists('inventory_quantity', $variant);
                                });

                                $variantsWithoutQuantity = array_filter($arr, function ($variant) {
                                    return !array_key_exists('inventory_quantity', $variant);
                                });


                                if (!empty($variantsWithoutQuantity))
                                {
                                    Historical::upsert($variantsWithoutQuantity, [ 'variant_id', 'product_id', 'site_id', 'date_created' ], array_keys($variantsWithoutQuantity[0]));


//                                    exec("node getQuantity.js");

                                }


                                $yesterdayData = Historical::select('product_id', 'variant_id', 'inventory_quantity')
                                    ->whereIn('product_id',
                                        array_map(function ($product) {
                                            return $product['product_id'];
                                        }, $variantsWithQuantity))
                                    ->whereIn('variant_id', array_map(
                                        function ($product) {
                                            return $product['variant_id'];
                                        }, $variantsWithQuantity))
                                    ->where('date_created', '=', Carbon::yesterday())
                                    ->get()
                                    ->toArray();


                                for ( $i = 0; $i < count($variantsWithQuantity); $i ++ )
                                {
                                    for ( $c = 0; $c < count($yesterdayData); $c ++ )
                                    {
                                        if ($variantsWithQuantity[$i]['product_id'] == $yesterdayData[$c]['product_id'] && $variantsWithQuantity[$i]['variant_id'] == $yesterdayData[$c]['variant_id'])
                                        {
                                            $variantsWithQuantity[$i]['sales'] = $yesterdayData[$c]['inventory_quantity'] - $variantsWithQuantity[$i]['inventory_quantity'];
                                        }
                                    }
                                }


                                $variantsWithQuantity = array_values($variantsWithQuantity);

                                for ( $i = 0; $i < count($variantsWithQuantity); $i ++ )
                                {
                                    if (!array_key_exists('sales', $variantsWithQuantity[$i]))
                                    {
                                        $variantsWithQuantity[$i]['sales'] = 0;
                                    }
                                }

                                $variantsWithQuantity = array_values($variantsWithQuantity);

                                if (!empty($variantsWithQuantity))
                                {
                                    Historical::upsert($variantsWithQuantity, [ 'variant_id', 'product_id', 'inventory_quantity', 'site_id', 'date_created' ], array_keys($variantsWithQuantity[0]));
                                }

                            }
                        }
                    } while ( $productsResponse->getStatusCode() == 200 && !empty($productsResponse->getBody()->getContents()->products) );
                });
            });
        });
    }
}
