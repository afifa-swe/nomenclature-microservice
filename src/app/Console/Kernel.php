<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\EnsureS3Bucket::class,
    ];

    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        //
    }

    protected function commands()
    {
        //
    }
}
