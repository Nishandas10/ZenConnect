<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(protected AIService $aiService)
    {
    }

    public function summarize(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);

        $ticket = Ticket::findOrFail($request->ticket_id);
        $this->authorize('view', $ticket);

        $summary = $this->aiService->summarizeTicket($ticket->title, $ticket->description);

        if (!$summary) {
            return response()->json(['message' => 'Failed to generate summary. Check logs for details.'], 500);
        }

        $ticket->update(['ai_summary' => $summary]);

        return response()->json([
            'summary' => $summary,
            'message' => 'Summary generated successfully',
        ]);
    }

    public function suggestReply(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);

        $ticket = Ticket::with(['comments.user'])->findOrFail($request->ticket_id);
        $this->authorize('view', $ticket);

        $comments = $ticket->comments->map(function ($comment) {
            return [
                'user' => $comment->user->name,
                'body' => $comment->body,
            ];
        })->toArray();

        $reply = $this->aiService->suggestReply($ticket->title, $ticket->description, $comments);

        if (!$reply) {
            return response()->json(['message' => 'Failed to generate reply suggestion. Check logs for details.'], 500);
        }

        return response()->json([
            'suggested_reply' => $reply,
            'message' => 'Reply suggestion generated successfully',
        ]);
    }
}
