<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class CreditReminderNotification extends Notification
{
    use Queueable;

    protected $customers;
    protected $suppliers;

    /**
     * @param array $customers Array of customers with credit
     * @param array $suppliers Array of suppliers with credit
     */
    public function __construct(array $customers, array $suppliers)
    {
        $this->customers = $customers;
        $this->suppliers = $suppliers;
    }

    /**
     * Specify that this notification uses the WebPush channel.
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Build the web-push message.
     */
    public function toWebPush($notifiable, $notification)
    {
        $title = "⏰ Pengingat Utang & Piutang";
        
        // Build customer list
        $customerBody = "- Pelanggan:\n";
        foreach ($this->customers as $customer) {
            $customerBody .= "{$customer['name']}: Rp. " . number_format($customer['amount'], 0, ',', '.') . "\n";
        }
        
        // Build supplier list
        $supplierBody = "- Supplier:\n";
        foreach ($this->suppliers as $supplier) {
            $supplierBody .= "{$supplier['name']}: Rp. " . number_format($supplier['amount'], 0, ',', '.') . "\n";
        }
        
        $body = trim($customerBody) . "\n" . trim($supplierBody);
        $icon = url('/public/stock.png'); // Make sure to add this icon
        $url = route('customers.credits.index'); // Or any other relevant route

        return (new WebPushMessage)
            ->title($title)
            ->body('klik untuk melihat detail utang dan piutang')
            ->icon($icon)
            ->data(['url' => $url . '?reminderUtangPiutang='. urlencode($body)]);
    }
}
