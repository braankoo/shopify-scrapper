<?php

namespace App\Jobs;

use App\Models\Historical;
use App\Models\Site;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetData implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * @var \App\Models\Site
     */
    public $site;
    /**
     * @var
     */
    public $client;

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
     */
    public function handle()
    {
        $this->client = new Client([ 'base_uri' => $this->site->url ]);

        $this->site->catalogs()->where('active', '=', 'true')->each(function ($catalog) {

            $catalog->products()->where('active', '=', 'true')->each(function ($product) use ($catalog) {

                do
                {

                    $productsRequest = new Request('GET', "{$catalog->url}{$product->link}.json");

                    $productsResponse = $this->client->send($productsRequest,
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
                                $arr[] = $this->prepareVariantData($product, $i);
                            }


                            $this->variantsWithQuantityOperations(
                                array_filter($arr, function ($variant) {
                                    return array_key_exists('inventory_quantity', $variant);
                                })
                            );

                            $this->variantsWithOutQuantityOperations(array_filter($arr, function ($variant) {
                                    return !array_key_exists('inventory_quantity', $variant);
                                })
                            );

                        }
                    }
                } while ( $productsResponse->getStatusCode() == 200 && !empty($productsResponse->getBody()->getContents()->products) );
            });
        });
    }

    /**GetDataProductsWithoutQuantity
     * @param $product
     * @param int $i
     * @return array
     */
    private function prepareVariantData($product, int $i): array
    {
        $data = [];
        $data['variant_id'] = $product->variants[$i]->id;
        $data['product_id'] = $product->id;
        $data['price'] = (int) ($product->variants[$i]->price * 1000000);
        $data['position'] = $product->variants[$i]->position;
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
        $data['site_id'] = $this->site->id;

        return $data;
    }


    /**
     * @param array $data
     */
    private function variantsWithQuantityOperations(array $data)
    {

        $yesterdayData = Historical::select('product_id', 'variant_id', 'inventory_quantity')
            ->whereIn('product_id',
                array_map(function ($product) {
                    return $product['product_id'];
                }, $data))
            ->whereIn('variant_id', array_map(
                function ($product) {
                    return $product['variant_id'];
                }, $data))
            ->where('date_created', '=', Carbon::yesterday())
            ->get()
            ->toArray();


        for ( $i = 0; $i < count($data); $i ++ )
        {
            for ( $c = 0; $c < count($yesterdayData); $c ++ )
            {
                if ($data[$i]['product_id'] == $yesterdayData[$c]['product_id'] && $data[$i]['variant_id'] == $yesterdayData[$c]['variant_id'])
                {
                    $data[$i]['sales'] = $yesterdayData[$c]['inventory_quantity'] - $data[$i]['inventory_quantity'];
                }
            }
        }

        $data = array_values($data);

        for ( $i = 0; $i < count($data); $i ++ )
        {
            if (!array_key_exists('sales', $data[$i]))
            {
                $data[$i]['sales'] = 0;
            }
        }

        $data = array_values($data);

        if (!empty($data))
        {
            Historical::upsert($data, [ 'variant_id', 'product_id', 'inventory_quantity', 'site_id', 'date_created' ], array_keys($data[0]));
        }
    }


    /**
     * @param array $data
     */
    private function variantsWithOutQuantityOperations(array $data)
    {

        if (!empty($data))
        {
            Historical::upsert($data, [ 'variant_id', 'product_id', 'site_id', 'date_created' ], array_keys($data[0]));
        }
    }
}
