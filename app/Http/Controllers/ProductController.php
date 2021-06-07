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
            ->join('sites', 'products.site_id', '=', 'sites.id')
            ->selectRaw('products.title, catalogs.name, variants.variant_id,products.type,ROUND(historicals.price/1000000,2) as price,historicals.position as position, CONCAT(sites.url,products.link) as url,sku,tags,products.created_at,products.published_at, image_1,image_2,image_3')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->join('historicals', function ($q) {
                $q->on('historicals.variant_id', '=', 'variants.variant_id');
                $q->on('historicals.product_id', '=', 'variants.product_id');
            })
            ->whereDate('date_created', Carbon::today())
            ->orderBy($request->input('sortBy') == '' ? 'title' : $request->input('sortBy'), $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
            ->paginate(20);
    }
}
