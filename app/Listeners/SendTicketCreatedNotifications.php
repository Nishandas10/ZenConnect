<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\NotificationService;

class SendTicketCreatedNotifications
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function handle(TicketCreated $event): void
    {
        $this->notificationService->notifyTicketCreated($event->ticket);
    }
}
