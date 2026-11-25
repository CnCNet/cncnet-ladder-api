<?php

namespace App\Jobs\Qm;

use App\Http\Services\ChatFlagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-reads every game's chat flags after staff change the word list or the spam limits.
 */
class RescanChatFlagsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    public function __construct()
    {
        $this->onQueue('saveladderresult');
    }

    public function handle(): void
    {
        (new ChatFlagService())->refreshAll();
    }
}
