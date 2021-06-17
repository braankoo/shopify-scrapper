<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller {


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        return DB::table('products')
            ->selectRaw('SUBSTRING_INDEX(sites.product_json,"/",3) as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at,IFNULL(products.position,"n/a") as position,IFNULL(inv.quantity,"n/a") as quantity,IFNULL(inv.sales,"n/a") as sales')
            ->join('sites', 'products.site_id', '=', 'sites.id')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->leftJoin(
                DB::raw("(SELECT product_id, sum(inventory_quantity) as quantity,sum(sales) as sales from historicals WHERE date_created = CURDATE() GROUP BY product_id,catalog_id) inv"),
                function ($join) {
                    $join->on('products.id', '=', 'inv.product_id');
                })
            ->groupBy([ 'catalogs.id', 'products.id' ])
            ->orderBy($request->input('sortBy') == '' ? 'products.title' : $request->input('sortBy'), $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
            ->paginate(20);
    }
}
