<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GetPositionAndQuantity implements ShouldQueue, ShouldBeUnique {


    /**
     * @var \App\Models\Site
     */
    public $site;


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        try
        {

            $process = new Process([ 'node', 'getPosition.cjs', $this->site->id ], base_path());
            $process->setTimeout(3599);
            $process->mustRun();
            $process->wait();

            if (!$process->isSuccessful())
            {
                throw new ProcessFailedException($process);
            }

            echo $process->getOutput();


            if (!Str::contains($this->site->product_json, [ 'tigermist', 'motelrocks' ]))
            {
                $process->wait();
                $process = new Process([ 'node', 'getQuantity.cjs', $this->site->id ], base_path());
                $process->start();
            }
        } catch ( \Exception $e )
        {
            dd($e->getMessage());
        }
    }
}
