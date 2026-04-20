<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Support Ticket: ' . $this->ticket->ticket_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new support ticket has been created.')
            ->line('**Ticket:** ' . $this->ticket->ticket_number)
            ->line('**Title:** ' . $this->ticket->title)
            ->line('**Priority:** ' . ucfirst($this->ticket->priority))
            ->line('**Created by:** ' . $this->ticket->user->name)
            ->action('View Ticket', config('app.frontend_url', 'http://localhost:3001') . '/tickets/' . $this->ticket->id)
            ->line('Thank you for using ZenConnect!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'message' => 'New ticket created: ' . $this->ticket->title,
            'type' => 'ticket_created',
        ];
    }
}
