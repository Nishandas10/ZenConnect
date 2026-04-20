<?php

namespace App\Services;

use App\Events\CommentAdded;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    protected ?WebhookService $webhookService = null;

    public function __construct()
    {
        $this->webhookService = app(WebhookService::class);
    }

    public function createTicket(array $data, User $user): Ticket
    {
        return DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'title' => $data['title'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'category_id' => $data['category_id'] ?? null,
                'user_id' => $user->id,
                'status' => 'open',
                'sla_deadline' => $this->calculateSlaDeadline($data['priority'] ?? 'medium'),
            ]);

            if (!empty($data['tags'])) {
                $ticket->tags()->sync($data['tags']);
            }

            if (!empty($data['attachments'])) {
                $this->handleAttachments($ticket, $data['attachments'], $user);
            }

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'created',
                'new_value' => 'Ticket created',
            ]);

            event(new TicketCreated($ticket));

            return $ticket->load(['user', 'category', 'tags', 'attachments']);
        });
    }

    public function updateTicket(Ticket $ticket, array $data, User $user): Ticket
    {
        return DB::transaction(function () use ($ticket, $data, $user) {
            $changes = [];

            foreach (['title', 'description', 'priority', 'category_id'] as $field) {
                if (isset($data[$field]) && $ticket->{$field} != $data[$field]) {
                    $changes[$field] = [
                        'old' => $ticket->{$field},
                        'new' => $data[$field],
                    ];
                }
            }

            if (isset($data['status']) && $ticket->status !== $data['status']) {
                $changes['status'] = [
                    'old' => $ticket->status,
                    'new' => $data['status'],
                ];
                if ($data['status'] === 'resolved' || $data['status'] === 'closed') {
                    $data['resolved_at'] = now();
                }
            }

            $oldStatus = $ticket->status;
            $ticket->update($data);

            if (!empty($data['tags'])) {
                $ticket->tags()->sync($data['tags']);
            }

            foreach ($changes as $field => $change) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'action' => 'updated',
                    'field' => $field,
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                ]);
            }

            if (!empty($changes)) {
                event(new TicketUpdated($ticket, $changes));

                // Send webhook for status changes
                if (isset($changes['status'])) {
                    $this->webhookService->notifyTicketStatusChanged(
                        $ticket,
                        $changes['status']['old'],
                        $changes['status']['new']
                    );

                    if (in_array($ticket->status, ['resolved', 'closed'])) {
                        $this->webhookService->notifyTicketResolved($ticket);
                    }
                }
            }

            return $ticket->load(['user', 'assignee', 'category', 'tags', 'attachments']);
        });
    }

    public function assignAgent(Ticket $ticket, int $agentId, User $admin): Ticket
    {
        return DB::transaction(function () use ($ticket, $agentId, $admin) {
            $oldAssignee = $ticket->assigned_to;

            $ticket->update([
                'assigned_to' => $agentId,
                'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
            ]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $admin->id,
                'action' => 'assigned',
                'field' => 'assigned_to',
                'old_value' => $oldAssignee,
                'new_value' => $agentId,
            ]);

            event(new TicketUpdated($ticket, ['assigned_to' => [
                'old' => $oldAssignee,
                'new' => $agentId,
            ]]));

            // Send webhook notification
            $this->webhookService->notifyTicketAssigned($ticket);

            return $ticket->load(['user', 'assignee', 'category', 'tags']);
        });
    }

    public function addComment(Ticket $ticket, array $data, User $user): \App\Models\TicketComment
    {
        return DB::transaction(function () use ($ticket, $data, $user) {
            $comment = $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $data['body'],
                'is_internal' => $data['is_internal'] ?? false,
            ]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'commented',
                'new_value' => 'Comment added',
            ]);

            event(new CommentAdded($comment));

            // Send webhook notification for external app tickets
            $this->webhookService->notifyCommentAdded($comment);

            return $comment->load('user');
        });
    }

    public function getAgentWithLeastWorkload(): ?User
    {
        return User::agents()
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['open', 'in_progress']);
            }])
            ->orderBy('assigned_tickets_count', 'asc')
            ->first();
    }

    protected function handleAttachments(Ticket $ticket, array $files, User $user): void
    {
        foreach ($files as $file) {
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ticket-attachments/' . $ticket->id, $filename, 'public');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ]);
        }
    }

    protected function calculateSlaDeadline(string $priority): \Carbon\Carbon
    {
        return match ($priority) {
            'urgent' => now()->addHours(4),
            'high' => now()->addHours(8),
            'medium' => now()->addHours(24),
            'low' => now()->addHours(48),
            default => now()->addHours(24),
        };
    }
}
