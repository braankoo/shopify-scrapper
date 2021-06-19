<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller {


    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('title'))
        {
            return response()->json(Catalog::where('title', 'LIKE', '%' . $request->input('title') . '%')->paginate(10, [ 'title' ]), JsonResponse::HTTP_OK);
        }


        return response()->json(Catalog::paginate(10, [ 'title', 'id', 'status' ]), JsonResponse::HTTP_OK);
    }


    /**
     * @param \App\Models\Catalog $catalog
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Catalog $catalog, Request $request): JsonResponse
    {
        $catalog->status = $request->input('status');
        $catalog->save();
//
        return response()->json([ 'message' => 'Successfuly updated' ], JsonResponse::HTTP_OK);
    }
}
