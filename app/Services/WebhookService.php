<?php

namespace App\Services;

use App\Models\ExternalApp;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Send webhook notification when a ticket status changes.
     */
    public function notifyTicketStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        if (!$ticket->external_app_id) {
            return;
        }

        $this->sendWebhook($ticket->externalApp, 'ticket.status_changed', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_id' => $ticket->external_customer_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send webhook notification when a comment is added to a ticket.
     */
    public function notifyCommentAdded(TicketComment $comment): void
    {
        $ticket = $comment->ticket;

        if (!$ticket->external_app_id) {
            return;
        }

        // Don't notify for internal comments
        if ($comment->is_internal) {
            return;
        }

        // Don't notify for comments from the external customer themselves
        if ($comment->user && $comment->user->email === $ticket->external_customer_email) {
            return;
        }

        $this->sendWebhook($ticket->externalApp, 'ticket.comment_added', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_id' => $ticket->external_customer_id,
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => $comment->user->name,
                'author_role' => $comment->user->role,
                'created_at' => $comment->created_at->toIso8601String(),
            ],
            'ticket_status' => $ticket->status,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send webhook notification when a ticket is assigned.
     */
    public function notifyTicketAssigned(Ticket $ticket): void
    {
        if (!$ticket->external_app_id) {
            return;
        }

        $this->sendWebhook($ticket->externalApp, 'ticket.assigned', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_id' => $ticket->external_customer_id,
            'assigned_to' => $ticket->assignee?->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send webhook notification when a ticket is resolved.
     */
    public function notifyTicketResolved(Ticket $ticket): void
    {
        if (!$ticket->external_app_id) {
            return;
        }

        $this->sendWebhook($ticket->externalApp, 'ticket.resolved', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_id' => $ticket->external_customer_id,
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send webhook to the external app.
     */
    protected function sendWebhook(ExternalApp $app, string $event, array $payload): void
    {
        if (!$app->webhook_url || !$app->is_active) {
            return;
        }

        $payload['event'] = $event;
        $payload['app_name'] = $app->name;

        $signature = $app->signPayload($payload);

        try {
            Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                ])
                ->post($app->webhook_url, $payload);

            Log::info("Webhook sent successfully", [
                'app' => $app->name,
                'event' => $event,
                'ticket_id' => $payload['ticket_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook failed", [
                'app' => $app->name,
                'event' => $event,
                'url' => $app->webhook_url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
