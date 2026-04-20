<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Services\NotificationService;

class SendCommentAddedNotifications
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function handle(CommentAdded $event): void
    {
        $comment = $event->comment->load('ticket');
        $this->notificationService->notifyCommentAdded($comment->ticket, $comment);
    }
}
