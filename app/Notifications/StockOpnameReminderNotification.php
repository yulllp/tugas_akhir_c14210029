<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Models\StokOpnameSchedule;

class StockOpnameReminderNotification extends Notification
{
    use Queueable;

    protected $schedule;

    public function __construct(StokOpnameSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        $title = "🔔 Pengingat Stok Opname";
        $opnameDate = $this->schedule->date->format('d M Y H:i');
        $body  = "Jadwal stok opname Anda dijadwalkan pada: {$opnameDate}. Jangan lupa lakukan pengecekan stok.";
        $icon  = url('/public/bell.png');
        $url   = route('opnames.index');

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->data(['url' => $url]);
    }
}
