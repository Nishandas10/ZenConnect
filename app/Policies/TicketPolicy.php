<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }
        return $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }
        return $ticket->user_id === $user->id;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if agent can self-assign an unassigned ticket.
     */
    public function selfAssign(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $ticket->assigned_to === null;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }
        return $ticket->user_id === $user->id;
    }
}
