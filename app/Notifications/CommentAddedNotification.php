<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment on Ticket: ' . $this->ticket->ticket_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new comment was added to your ticket.')
            ->line('**Ticket:** ' . $this->ticket->ticket_number)
            ->line('**Comment by:** ' . $this->comment->user->name)
            ->line('"' . \Illuminate\Support\Str::limit($this->comment->body, 200) . '"')
            ->action('View Ticket', config('app.frontend_url', 'http://localhost:3001') . '/tickets/' . $this->ticket->id)
            ->line('Thank you for using ZenConnect!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'comment_by' => $this->comment->user->name,
            'message' => $this->comment->user->name . ' commented on ticket ' . $this->ticket->ticket_number,
            'type' => 'comment_added',
        ];
    }
}
