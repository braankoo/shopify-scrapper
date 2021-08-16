<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MergeData implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Site
     */
    public $site;

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

        DB::statement("DELETE FROM data WHERE date_created = CURDATE() and site_id = :site_id", [ 'site_id' => $this->site->id ]);

        DB::statement("
                INSERT INTO data
                    SELECT sites.product_html                             as site,
                           catalogs.title                                 as catalog,
                           products.title                                 as product,
                           sites.id                                       as site_id,
                           image,
                            CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json, '/', 3), '/collections/'), catalogs.handle),
                            CONCAT('/products/', products.handle))  as url,
                            type,
                            DATE_FORMAT(products.created_at, '%Y-%m-%d')   as created_at,
                            DATE_FORMAT(products.published_at, '%Y-%m-%d') as published_at,
                            products.position                              as `position`,
                            sum(sales)                                     as sales,
                            sum(quantity)                                  as quantity,
                            products.id                                    as product_id,
                            date_created
                    FROM historicals
                        INNER JOIN products
                    ON historicals.product_id = products.id AND products.site_id = historicals.site_id
                        INNER JOIN catalog_product
                    ON products.id = catalog_product.product_id AND catalog_product.site_id = products.site_id
                        INNER JOIN catalogs on catalog_product.catalog_id = catalogs.id
                        INNER JOIN sites on products.site_id = sites.id

                    WHERE sites.id = :site_id
                    AND date_created = CURDATE()
                    GROUP BY products.id, sites.id, historicals.date_created, catalogs.id
        ", [ 'site_id' => $this->site->id ]);
    }
}
