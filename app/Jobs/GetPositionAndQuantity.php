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

    public $timeout = 7001;

    public $tries = 4;

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

        $process = new Process([ 'pkill', '-f', "node getPosition.cjs {$this->site->id}" ]);

        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }


        $process = new Process([ 'pkill', '-f', "{$this->site->host}" ]);
        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }

        $process = new Process([ 'node', 'getPosition.cjs', $this->site->id ], base_path());
        $process->setTimeout(7000);
        $process->mustRun();
        $process->wait();


        if (!Str::contains($this->site->product_json, [ 'tigermist', 'motelrocks' ]))
        {
            sleep(10);
            $process = new Process([ 'pkill', '-f', "node getQuantity.cjs {$this->site->id}" ]);
            for ( $i = 0; $i < 10; $i ++ )
            {
                $i ++;
                $process->run();

            }

            $process = new Process([ 'node', 'getQuantity.cjs', $this->site->id ], base_path());
            $process->setTimeout(7000);
            $process->run();
        }

    }

    public function fail($exception = null)
    {
        $process = new Process([ 'pkill', '-f', "node getPosition.cjs {$this->site->id}" ]);
        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }

        $process = new Process([ 'pkill', '-f', "position/{$this->site->host}" ]);
        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }


        $process = new Process([ 'pkill', '-f', "node getQuantity.cjs {$this->site->id}" ]);
        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }

        $process = new Process([ 'pkill', '-f', "quantity/{$this->site->host}" ]);
        for ( $i = 0; $i < 10; $i ++ )
        {
            $i ++;
            $process->run();

        }

    }
}
