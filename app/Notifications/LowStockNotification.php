<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Models\Product;

class LowStockNotification extends Notification
{
    use Queueable;

    protected $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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
        $title = "⚠️ Stok Rendah: {$this->product->name}";
        $body  = "Stok barang \"{$this->product->name}\" hanya tersisa {$this->product->totalStok}. Minstok-nya adalah {$this->product->minStok}. Segera lakukan restock.";
        $icon  = url('/public/stock.png'); // ubah sesuai asset Anda
        $url   = route('products.show', ['id' => $this->product->id]);

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->data(['url' => $url]);
    }
}
