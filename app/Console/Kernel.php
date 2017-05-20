<?php

namespace App\Console;

use App\Console\Commands\ChangeMongoNestedCollection;
use App\Console\Commands\ChangeMongoRootValue;
use App\Console\Commands\CopyMovie;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\UnzipMovie;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UnzipMovie::class,
        CopyMovie::class,
        ChangeMongoNestedCollection::class,
        ChangeMongoRootValue::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
