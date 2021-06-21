<?php

namespace App\Http\Controllers;


use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller {


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {

        if ($request->has('title'))
        {
            return response()->json(Product::where('title', 'LIKE', '%' . $request->input('title') . '%')->paginate(10, [ 'title', 'id' ]), JsonResponse::HTTP_OK);
        }
        if ($request->has('type'))
        {
            return response()->json(Product::where('type', 'LIKE', '%' . $request->input('type') . '%')->paginate(10, [ 'type', 'id' ]), JsonResponse::HTTP_OK);
        }

        return response()->json(Product::paginate(10, [ 'title', 'id' ]), JsonResponse::HTTP_OK);

    }


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function data(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $filters = json_decode($request->input('filter'));


        return DB::table('products')
            ->selectRaw('SUBSTRING_INDEX(sites.product_json,"/",3) as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at, IFNULL(products.position,"n/a") as `products.position`,IFNULL(quantity,"n/a") as quantity,IFNULL(sum(sales),"n/a") as sales, products.id as product_id')
            ->join('sites', 'products.site_id', '=', 'sites.id')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->leftJoin(
                DB::raw("(SELECT product_id, sum(inventory_quantity) as quantity,sum(sales) as sales from historicals WHERE date_created = CURDATE() GROUP BY product_id) inv"),
                function ($join) {
                    $join->on('products.product_id', '=', 'inv.product_id');
                }
            )
            ->when(!empty($filters->site->url), function ($q) use ($filters) {
                $q->whereIn('sites.id', array_map(function ($site) {
                    return $site->id;
                }, $filters->site->url));
            })
            ->when(!empty($filters->catalog->title), function ($q) use ($filters) {
                $q->whereIn('catalogs.title', array_map(
                        function ($catalog) {
                            return $catalog->title;
                        }, $filters->catalog->title)
                );
            })
            ->when(!empty($filters->product->title), function ($q) use ($filters) {
                $q->whereIn('products.title', array_map(
                        function ($product) {
                            return $product->title;
                        }, $filters->product->title)
                );
            })
            ->when(!empty($filters->product->type), function ($q) use ($filters) {
                $q->whereIn('products.type', array_map(
                        function ($product) {
                            return $product->type;
                        }, $filters->product->type)
                );
            })
            ->when(!empty($filters->created_at->start_date), function ($q) use ($filters) {
                $q->whereDate('products.created_at', '>=', $filters->created_at->start_date);
            })
            ->when(!empty($filters->created_at->end_date), function ($q) use ($filters) {

                $q->whereDate('products.created_at', '<=', $filters->created_at->end_date);
            })
            ->when(!empty($filters->published_at->start_date), function ($q) use ($filters) {
                $q->whereDate('products.published_at', '>=', $filters->published_at->start_date);
            })
            ->when(!empty($filters->published_at->end_date), function ($q) use ($filters) {
                $q->whereDate('products.published_at', '<=', $filters->published_at->end_date);
            })
            ->when(!empty($filters->position), function ($q) use ($filters) {
                $q->where('products.position', '=<', $filters->position);
            })
            ->when(!empty($filters->quantity), function ($q) use ($filters) {
                $q->where('quantity', '<=', $filters->quantity);
            })
            ->whereNotNull('products.position')
            ->where('products.status', '=', 'ENABLED')
            ->groupBy([ 'catalogs.id', 'products.id' ])
            ->orderBy($request->input('sortBy') == '' ? 'products.title' : $request->input('sortBy'), $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
            ->paginate(20);
    }


    /**
     * @param \App\Models\Product $product
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function historical(Product $product, Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {

        $filters = json_decode($request->input('filter'));


        $pagination = DB::table('products')
            ->selectRaw('ROUND(historicals.price / 1000000,2) as price,inventory_quantity as quantity,compare_at_price, sales,date_created')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->join('historicals', 'variants.variant_id', '=', 'historicals.variant_id')
            ->where('products.id', '=', $product->id)
            ->orderBy('date_created')
            ->whereBetween('date_created', [ $filters->date->start_date, $filters->date->end_date ])
            ->paginate(20);

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
                $b['product'] = str_replace('_', ' ', ucfirst($value));
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


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function csv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = json_decode($request->input('filter'));

        $data = DB::table('products')
            ->selectRaw('SUBSTRING_INDEX(sites.product_json,"/",3) as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at, IFNULL(products.position,"n/a") as `products.position`,IFNULL(quantity,"n/a") as quantity,IFNULL(sum(sales),"n/a") as sales, products.id as product_id')
            ->join('sites', 'products.site_id', '=', 'sites.id')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->leftJoin(
                DB::raw("(SELECT product_id, sum(inventory_quantity) as quantity,sum(sales) as sales from historicals WHERE date_created = CURDATE() GROUP BY product_id) inv"),
                function ($join) {
                    $join->on('products.product_id', '=', 'inv.product_id');
                }
            )
            ->when(!empty($filters->site->url), function ($q) use ($filters) {
                $q->whereIn('sites.id', array_map(function ($site) {
                    return $site->id;
                }, $filters->site->url));
            })
            ->when(!empty($filters->catalog->title), function ($q) use ($filters) {
                $q->whereIn('catalogs.title', array_map(
                        function ($catalog) {
                            return $catalog->title;
                        }, $filters->catalog->title)
                );
            })
            ->when(!empty($filters->product->title), function ($q) use ($filters) {
                $q->whereIn('products.title', array_map(
                        function ($product) {
                            return $product->title;
                        }, $filters->product->title)
                );
            })
            ->when(!empty($filters->product->type), function ($q) use ($filters) {
                $q->whereIn('products.type', array_map(
                        function ($product) {
                            return $product->type;
                        }, $filters->product->type)
                );
            })
            ->when(!empty($filters->created_at->start_date), function ($q) use ($filters) {
                $q->whereDate('products.created_at', '>=', $filters->created_at->start_date);
            })
            ->when(!empty($filters->created_at->end_date), function ($q) use ($filters) {
                $q->whereDate('products.created_at', '<=', $filters->created_at->end_date);
            })
            ->when(!empty($filters->published_at->start_date), function ($q) use ($filters) {
                $q->whereDate('products.published_at', '>=', $filters->published_at->start_date);
            })
            ->when(!empty($filters->published_at->end_date), function ($q) use ($filters) {
                $q->whereDate('products.published_at', '<=', $filters->published_at->end_date);
            })
            ->when(!empty($filters->position), function ($q) use ($filters) {
                $q->where('products.position', '<=', $filters->position);
            })
            ->when(!empty($filters->quantity), function ($q) use ($filters) {
                $q->where('quantity', '<=', $filters->quantity);
            })
            ->whereNotNull('products.position')
            ->where('products.status', '=', 'ENABLED')
            ->groupBy([ 'catalogs.id', 'products.id' ])
            ->orderBy($request->input('sortBy') == '' ? 'products.title' : $request->input('sortBy'), $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
            ->get()->toArray();
        $csv = [];
        array_push($csv, array_keys((array) $data[0]));

        foreach ( $data as $row )
        {
            $csv[] = array_values((array) $row);
        }
        $callback = function () use ($csv) {
            $FH = fopen('php://output', 'w');
            foreach ( $csv as $row )
            {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };


        return \Response::stream($callback, 200,
            [
                'Cache-Control'         => 'must-revalidate, post-check=0, pre-check=0'
                , 'Content-type'        => 'text/csv'
                , 'Content-Disposition' => 'attachment; filename=stats.csv'
                , 'Expires'             => '0'
                , 'Pragma'              => 'public'
            ]
        );
    }
}
