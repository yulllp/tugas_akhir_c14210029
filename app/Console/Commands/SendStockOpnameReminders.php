<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StokOpnameSchedule;
use App\Notifications\StockOpnameReminder;
use App\Notifications\StockOpnameReminderNotification;
use Carbon\Carbon;

class SendStockOpnameReminders extends Command
{
    protected $signature = 'stokopname:send-reminders';
    protected $description = 'Send WebPush reminders for due stock-opname schedules';

    public function handle()
    {
        $now = Carbon::now();
        $dueSchedules = StokOpnameSchedule::with('user')
            ->where('date', '<=', $now)
            ->whereNull('finish_at')
            ->get();

        foreach ($dueSchedules as $schedule) {
            $user = $schedule->user;
            if ($user) {
                $user->notify(new StockOpnameReminderNotification($schedule));
                $this->info("Reminder sent for schedule #{$schedule->id} to user {$user->id}");
            }
        }

        return 0;
    }
}
