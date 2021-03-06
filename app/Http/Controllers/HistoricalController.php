<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HistoricalController extends Controller {


    /**
     * @param \App\Models\Variant $variant
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index(Variant $variant, Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $filters = json_decode($request->input('filter'));

        return DB::table('data')
            ->selectRaw('ROUND(historicals.price / 1000000,2) as price,inventory_quantity as quantity,compare_at_price, sales,date_created, product_position.position')
            ->join('sites', 'historicals.site_id', '=', 'sites.id')
            ->join('variants', 'variants.variant_id', '=', 'historicals.variant_id')
            ->join('products_position', function ($q) {
                $q->on('products.id', '=', 'product_position.product_id');
                $q->on('products.site_id', '=', 'sites.id');
            })
            ->where('variants.variant_id', '=', $variant->variant_id)
            ->orderBy('date_created')
            ->whereBetween('date_created', [ $filters->date->start_date, $filters->date->end_date ])
            ->paginate();

    }
}
