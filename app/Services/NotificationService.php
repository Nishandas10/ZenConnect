<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Notifications\CommentAddedNotification;

class NotificationService
{
    public function notifyTicketCreated(Ticket $ticket): void
    {
        // Notify all admins
        User::admins()->each(function (User $admin) use ($ticket) {
            $admin->notify(new NewTicketNotification($ticket));
        });

        // Notify assigned agent if any
        if ($ticket->assignee) {
            $ticket->assignee->notify(new NewTicketNotification($ticket));
        }
    }

    public function notifyStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        // Notify ticket owner
        $ticket->user->notify(new TicketStatusChangedNotification($ticket, $oldStatus, $newStatus));

        // Notify assigned agent
        if ($ticket->assignee && $ticket->assignee->id !== $ticket->user_id) {
            $ticket->assignee->notify(new TicketStatusChangedNotification($ticket, $oldStatus, $newStatus));
        }
    }

    public function notifyCommentAdded(Ticket $ticket, \App\Models\TicketComment $comment): void
    {
        $notification = new CommentAddedNotification($ticket, $comment);

        // Notify ticket owner if commenter is not the owner
        if ($comment->user_id !== $ticket->user_id) {
            $ticket->user->notify($notification);
        }

        // Notify assigned agent if commenter is not the agent
        if ($ticket->assignee && $comment->user_id !== $ticket->assigned_to) {
            $ticket->assignee->notify($notification);
        }
    }
}
