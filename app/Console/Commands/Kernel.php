<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   */
  protected $commands = [
    \App\Console\Commands\SendCreditReminders::class,
  ];
  protected function schedule(Schedule $schedule)
  {
    $schedule->command('credits:remind')->dailyAt('09:00')->timezone('Asia/Jayapura');
  }

  /**
   * Register the commands for the application.
   */
  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
