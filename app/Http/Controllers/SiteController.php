<?php

namespace App\Http\Controllers;

use App\Jobs\GetData;
use App\Jobs\GetPosition;
use App\Jobs\GetPositionAndQuantity;
use App\Jobs\GetQuantity;
use App\Models\Site;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

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
            return response()->json(Site::where('product_html', 'LIKE', '%' . $request->input('url') . '%')->paginate(10, [ DB::raw('DISTINCT(SUBSTRING_INDEX(sites.product_json, "/", 3))  as site')]), JsonResponse::HTTP_OK);
        }
        $jobs = DB::table('jobs')->get();
        $runningJobs = $jobs->filter(function ($job) {
            return $job->attempts > 0 && !is_null($job->reserved_at);
        })->map(function ($job) {
            $data = json_decode($job->payload);
            preg_match('/id\\";i:\d{0,10}/', $data->data->command, $matches);

            $matches = array_map(function ($match) {
                return str_replace([ 'id";i:' ], '', $match);
            }, $matches);

            return $matches;

        })->flatten()->toArray();

        $pagination = Site::paginate(10, [ 'product_html', 'product_json', 'quantity_updated_at', 'position_updated_at', 'json_updated_at', 'id' ]);

        $pagination->getCollection()->transform(function ($site, $key) use ($runningJobs) {
            return [

                'product_html'        => $site->product_html,
                'product_json'        => $site->product_json,
                'quantity_updated_at' => $site->quantity_updated_at,
                'position_updated_at' => $site->position_updated_at,
                'json_updated_at'     => $site->json_updated_at,
                'id'                  => $site->id,
                'running'             => in_array($site->id, $runningJobs)
            ];
        });

        return response()->json($pagination, JsonResponse::HTTP_OK);
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


    /**
     * @param \App\Models\Site $site
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function fetch(Site $site): JsonResponse
    {


        $process = new Process([ 'ps', '-axjf' ]);
        $process->mustRun();
        $process->wait();
        if (preg_match('/Worker.js/', $process->getOutput()) || DB::table('jobs')->count() > 0)
        {
            return response()->json([ 'message' => 'Job in progress. Try later.' ], JsonResponse::HTTP_OK);
        }

        Bus::chain([
            new \App\Jobs\GetCatalog($site),
            new \App\Jobs\GetProducts($site),
            new GetData($site),
            new GetPosition($site),
            new GetQuantity($site)
        ])->dispatch();

        return response()->json([ 'message' => 'Initialized' ], JsonResponse::HTTP_OK);
    }
}
