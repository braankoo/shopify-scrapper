<?php

namespace App\Jobs;

use App\Models\Variant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetQuantityFromFrontEnd implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Variant
     */
    public $variant;
    /**
     * @var int
     */
    public $variantIndex;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $procu)
    {
        $this->variant = $variant;
        $this->variantIndex = $variantIndex;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dd($this->variant, $this->variantIndex);
    }
}
