<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket Status Updated: ' . $this->ticket->ticket_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('The status of your ticket has been updated.')
            ->line('**Ticket:** ' . $this->ticket->ticket_number)
            ->line('**Title:** ' . $this->ticket->title)
            ->line('**Status:** ' . ucfirst(str_replace('_', ' ', $this->oldStatus)) . ' → ' . ucfirst(str_replace('_', ' ', $this->newStatus)))
            ->action('View Ticket', config('app.frontend_url', 'http://localhost:3001') . '/tickets/' . $this->ticket->id)
            ->line('Thank you for using ZenConnect!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Ticket {$this->ticket->ticket_number} status changed to " . ucfirst(str_replace('_', ' ', $this->newStatus)),
            'type' => 'status_changed',
        ];
    }
}
