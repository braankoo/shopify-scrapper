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


        $pagination = DB::table('variants')
            ->selectRaw('ROUND(historicals.price / 1000000,2) as price,inventory_quantity as quantity,compare_at_price, sales,date_created')
            ->join('historicals', 'variants.variant_id', '=', 'historicals.variant_id')
            ->where('variants.variant_id', '=', $variant->variant_id)
            ->orderBy('date_created')
            ->whereBetween('date_created', [ $filters->date->start_date, $filters->date->end_date ])
            ->paginate();
        $itemsTransformed = new Collection();

        foreach ( $pagination->items() as $item )
        {
            $itemsTransformed->push($item);
        }
        $itemsTransformed = $itemsTransformed->groupBy('date_created');

        $values = array_keys((array) $itemsTransformed->first()->first());

        $data = [];
        foreach ( $values as $value )
        {
            if ($value == 'date_created')
            {
                continue;
            }
            $b = [];
            foreach ( $itemsTransformed as $date => $items )
            {

                $arr = (array) $items->first();
                $b['variant'] = str_replace('_', ' ', ucfirst($value));
                $b[$date] = $arr[$value];
            }
            $data[] = $b;
        }


        return new \Illuminate\Pagination\LengthAwarePaginator(
            $data,
            $pagination->total(),
            $pagination->perPage(),
            $pagination->currentPage(),
            [ 'path'  => \Request::url(),
              'query' => [ 'page' => $pagination->currentPage() ]
            ]
        );
    }
}
