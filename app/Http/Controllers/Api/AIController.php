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

        if ($summary) {
            $ticket->update(['ai_summary' => $summary]);
        }

        return response()->json([
            'summary' => $summary,
            'message' => $summary ? 'Summary generated successfully' : 'Failed to generate summary',
        ], $summary ? 200 : 500);
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

        return response()->json([
            'suggested_reply' => $reply,
            'message' => $reply ? 'Reply suggestion generated successfully' : 'Failed to generate reply suggestion',
        ], $reply ? 200 : 500);
    }
}
