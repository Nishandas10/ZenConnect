<?php

namespace App\Listeners;

use App\Events\TicketUpdated;
use App\Services\NotificationService;

class SendTicketUpdatedNotifications
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function handle(TicketUpdated $event): void
    {
        if (isset($event->changes['status'])) {
            $this->notificationService->notifyStatusChanged(
                $event->ticket,
                $event->changes['status']['old'],
                $event->changes['status']['new']
            );
        }
    }
}
