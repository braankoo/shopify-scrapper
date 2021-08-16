<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GetQuantity implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 5;
    /**
     * @var \App\Models\Site
     */
    public $site;

    public $backoff = [ 100, 200, 300 ];

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
        if (!Str::contains($this->site->product_json, [ 'tigermist', 'motelrocks' ]))
        {

            $process = new Process([ 'pkill', '-f', "phantom" ]);
            $process->run();
            $process->wait();

            $process = new Process([ 'pkill', '-f', "node" ]);
            $process->run();
            $process->wait();

            $process = new Process([ 'node', 'getQuantity.cjs', $this->site->id ], base_path());
            $process->setTimeout(3600);
            $process->run();
            $process->wait();


        }
    }

    public function fail($exception = null)
    {

        $process = new Process([ 'pkill', '-f', "phantom" ]);
        $process->run();
        $process->wait();

        $process = new Process([ 'pkill', '-f', "node" ]);
        $process->run();
        $process->wait();

    }
}
