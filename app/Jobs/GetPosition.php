<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class GetPosition implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Site
     */
    public $site;

    public $tries = 5;

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
        $process = Process::fromShellCommandline("pkill -f 'node getPosition.cjs {$this->site->id}'");
        $process->setTimeout(7000);
        $process->run();
        $process->wait();

        $process = new Process([ 'node', 'getPosition.cjs', $this->site->id ], base_path());
        $process->setTimeout(7000);
        $process->mustRun();
        $process->wait();
    }

    public function fail($exception = null)
    {
        $process = Process::fromShellCommandline("pkill -f 'node getPosition.cjs {$this->site->id}'");
        $process->setTimeout(7000);
        $process->run();
        $process->wait();
    }
}
