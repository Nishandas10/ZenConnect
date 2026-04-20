<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketCommentController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('view', $ticket);

        $comments = $ticket->comments()->with('user');

        // Regular users can't see internal comments
        if ($request->user()->isUser()) {
            $comments->where('is_internal', false);
        }

        return TicketCommentResource::collection(
            $comments->orderBy('created_at', 'asc')->paginate(50)
        );
    }

    public function store(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('comment', $ticket);

        // Only agents/admins can create internal comments
        $data = $request->validated();
        if (isset($data['is_internal']) && $data['is_internal'] && $request->user()->isUser()) {
            $data['is_internal'] = false;
        }

        $comment = $this->ticketService->addComment($ticket, $data, $request->user());

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => new TicketCommentResource($comment),
        ], 201);
    }
}
