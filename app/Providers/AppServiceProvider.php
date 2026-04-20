<?php

namespace App\Providers;

use App\Events\CommentAdded;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Listeners\SendCommentAddedNotifications;
use App\Listeners\SendTicketCreatedNotifications;
use App\Listeners\SendTicketUpdatedNotifications;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        Event::listen(TicketCreated::class, SendTicketCreatedNotifications::class);
        Event::listen(TicketUpdated::class, SendTicketUpdatedNotifications::class);
        Event::listen(CommentAdded::class, SendCommentAddedNotifications::class);
    }
}
