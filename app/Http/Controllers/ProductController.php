<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
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
            return response()->json(Product::where('title', 'LIKE', '%' . $request->input('title') . '%')->paginate(20, [ 'title', 'id' ]), JsonResponse::HTTP_OK);
        }
        if ($request->has('type'))
        {
            return response()->json(Product::where('type', 'LIKE', '%' . $request->input('type') . '%')->paginate(20, [ 'type', 'id' ]), JsonResponse::HTTP_OK);
        }

        return response()->json(Product::paginate(20, [ 'title', 'id' ]), JsonResponse::HTTP_OK);

    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function data(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {


        $filters = json_decode($request->input('filter'));

        switch ( $request->input('sortBy') )
        {
            case '';
                $sortBy = 'products.position';
                break;
            case 'sales':
                $sortBy = DB::raw('SUM(sales)');
                break;
            case 'quantity':
                $sortBy = DB::raw('SUM(inventory_quantity)');
                break;
            default:
                $sortBy = $request->input('sortBy');
        }

        return DB::table('products')
            ->selectRaw('sites.product_html as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at, IFNULL(products.position,"n/a") as `products.position`,IFNULL(sum(sales),"n/a") as sales,quantity, products.id as product_id')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->join('sites', 'catalogs.site_id', '=', 'sites.id')
            ->leftjoin('historicals', 'variants.variant_id', '=', 'historicals.variant_id')
            ->when(!empty($filters->site->url), function ($q) use ($filters) {
                $q->whereIn('sites.id', array_map(
                        function ($site) {
                            return $site->id;
                        }, $filters->site->url)
                );
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
//            ->when(!empty($filters->created_at->start_date), function ($q) use ($filters) {
//                $q->whereDate('products.created_at', '>=', $filters->created_at->start_date);
//            })
//            ->when(!empty($filters->created_at->end_date), function ($q) use ($filters) {
//
//                $q->whereDate('products.created_at', '<=', $filters->created_at->end_date);
//            })
//            ->when(!empty($filters->published_at->start_date), function ($q) use ($filters) {
//                $q->whereDate('products.published_at', '>=', $filters->published_at->start_date);
//            })
//            ->when(!empty($filters->published_at->end_date), function ($q) use ($filters) {
//                $q->whereDate('products.published_at', '<=', $filters->published_at->end_date);
//            })
//            ->when(!empty($filters->position), function ($q) use ($filters) {
//                $q->where('products.position', '=<', $filters->position);
//            })
//            ->when(!empty($filters->quantity), function ($q) use ($filters) {
//                $q->where('quantity', '<=', $filters->quantity);
//            })
            ->whereNotNull('products.position')
            ->where('products.position', '<=', 1000)
            ->where('products.status', '=', 'ENABLED')
            ->whereDate('historicals.date_created', '>=', $filters->date_range->start_date)
            ->whereDate('historicals.date_created', '<=', $filters->date_range->end_date)
            ->groupBy([ 'catalogs.id', 'products.id' ])
            ->orderBy($sortBy, $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
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

        return DB::table('products')
            ->selectRaw('historicals.date_created, SUBSTRING_INDEX(sites.product_json,"/",3) as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at, IFNULL(products.position,"n/a") as `position`,IFNULL(sum(sales),"n/a") as sales, IFNULL(SUM(inventory_quantity),"n/a") as quantity, ROUND((AVG(historicals.price)/1000000),2) as price')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->join('catalog_product', 'products.product_id', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', 'catalogs.catalog_id')
            ->join('sites', 'catalogs.site_id', 'sites.id')
            ->join('historicals', 'variants.variant_id', '=', 'historicals.variant_id')
            ->leftjoin('product_position', 'products.product_id', '=', 'product_position.product_id')
            ->where('products.id', '=', $product->id)
            ->orderBy('historicals.date_created')
            ->groupBy('catalogs.id', 'products.id', 'sites.id', 'historicals.date_created')
            ->whereBetween('historicals.date_created', [ $filters->date->start_date, $filters->date->end_date ])
            ->paginate(20);

    }


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function csv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = json_decode($request->input('filter'));
        switch ( $request->input('sortBy') )
        {
            case '';
                $sortBy = 'products.title';
                break;
            case 'sales':
                $sortBy = DB::raw('SUM(sales)');
                break;
            case 'quantity':
                $sortBy = DB::raw('SUM(inventory_quantity)');
                break;
            default:
                $sortBy = $request->input('sortBy');
        }

        $data = DB::table('products')
            ->selectRaw('SUBSTRING_INDEX(sites.product_json,"/",3) as site,catalogs.title as catalog, products.title as product,image,CONCAT(CONCAT(CONCAT(SUBSTRING_INDEX(sites.product_json,"/",3), "/collections/"),catalogs.handle),CONCAT("/products/",products.handle)) as url, type,DATE_FORMAT(products.created_at, "%Y-%m-%d") as created_at,DATE_FORMAT(products.published_at, "%Y-%m-%d") as published_at, IFNULL(products.position,"n/a") as `products.position`,IFNULL(sum(sales),"n/a") as sales,quantity, products.id as product_id')
            ->join('catalog_product', 'products.product_id', '=', 'catalog_product.product_id')
            ->join('catalogs', 'catalog_product.catalog_id', '=', 'catalogs.catalog_id')
            ->join('variants', 'products.product_id', '=', 'variants.product_id')
            ->join('sites', 'catalogs.site_id', '=', 'sites.id')
            ->leftjoin('historicals', 'variants.variant_id', '=', 'historicals.variant_id')
            ->when(!empty($filters->site->url), function ($q) use ($filters) {
                $q->whereIn('sites.id', array_map(
                        function ($site) {
                            return $site->id;
                        }, $filters->site->url)
                );
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
//            ->when(!empty($filters->created_at->start_date), function ($q) use ($filters) {
//                $q->whereDate('products.created_at', '>=', $filters->created_at->start_date);
//            })
//            ->when(!empty($filters->created_at->end_date), function ($q) use ($filters) {
//
//                $q->whereDate('products.created_at', '<=', $filters->created_at->end_date);
//            })
//            ->when(!empty($filters->published_at->start_date), function ($q) use ($filters) {
//                $q->whereDate('products.published_at', '>=', $filters->published_at->start_date);
//            })
//            ->when(!empty($filters->published_at->end_date), function ($q) use ($filters) {
//                $q->whereDate('products.published_at', '<=', $filters->published_at->end_date);
//            })
//            ->when(!empty($filters->position), function ($q) use ($filters) {
//                $q->where('products.position', '=<', $filters->position);
//            })
//            ->when(!empty($filters->quantity), function ($q) use ($filters) {
//                $q->where('quantity', '<=', $filters->quantity);
//            })
            ->whereNotNull('products.position')
            ->where('products.position', '<=', 1000)
            ->where('products.status', '=', 'ENABLED')
            ->whereDate('historicals.date_created', '>=', $filters->date_range->start_date)
            ->whereDate('historicals.date_created', '<=', $filters->date_range->end_date)
            ->groupBy([ 'catalogs.id', 'products.id' ])
            ->orderBy($sortBy, $request->input('sortDesc') == 'true' ? 'ASC' : 'DESC')
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
