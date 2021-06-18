<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('url'))
        {
            return response()->json(Site::where('product_html', 'LIKE', '%' . $request->input('url') . '%')->paginate(10, [ DB::raw('SUBSTRING_INDEX(sites.product_json, "/", 3)  as site'), 'id' ]), JsonResponse::HTTP_OK);
        }

        return response()->json(Site::paginate(10, [ 'product_html', 'product_json', 'id' ]), JsonResponse::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'html' => 'required|url|unique:sites,product_html',
            'json' => 'required|url|unique:sites,product_json'

        ]);


        $site = new Site;
        $site->product_html = $request->input('html');
        $site->product_json = $request->input('json');
        $site->save();

        return response()->json([ 'message' => 'Successfuly added' ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Site $site
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Site $site): JsonResponse
    {
        return response()->json($site, JsonResponse::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Site $site)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Site $site): JsonResponse
    {
        $site->delete();

        return response()->json([ 'message' => 'Successfuly removed' ], JsonResponse::HTTP_OK);
    }
}
