<?php

namespace App\Notifications;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Ingredient $ingredient, protected int $threshold = 50) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Stock Alert: '.$this->ingredient->name)
            ->greeting('Hello Merchant!')
            ->line("We noticed that the stock for \"{$this->ingredient->name}\" has dropped below {$this->threshold}%.")
            ->line('Please take a moment to review and restock to prevent shortages.')
            ->action('Restock Now', url('/restock')) // Change this URL to the actual restock URL
            ->line('Thank you for your attention to this matter.');
    }
}
