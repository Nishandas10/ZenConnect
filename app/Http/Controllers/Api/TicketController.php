<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ticket::with(['user', 'assignee', 'category', 'tags'])
            ->withCount('comments');

        $user = $request->user();

        // Regular users only see their own tickets
        if ($user->isUser()) {
            $query->where('user_id', $user->id);
        }

        // Agents see assigned tickets by default, can view all with ?all=true
        if ($user->isAgent()) {
            if (!$request->has('all')) {
                $query->where('assigned_to', $user->id);
            }
            // Agents can see all tickets when they pass ?all=true
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'priority', 'status', 'ticket_number'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return TicketResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createTicket(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => new TicketResource($ticket),
        ], 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'user',
            'assignee',
            'category',
            'tags',
            'attachments',
            'histories.user',
            'comments' => function ($query) use ($request) {
                $query->with('user');
                // Regular users can't see internal comments
                if ($request->user()->isUser()) {
                    $query->where('is_internal', false);
                }
            },
        ]);

        return response()->json([
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $data = $request->validated();

        // Only admin can assign
        if (isset($data['assigned_to']) && !$request->user()->isAdmin()) {
            unset($data['assigned_to']);
        }

        $ticket = $this->ticketService->updateTicket($ticket, $data, $request->user());

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('assign', $ticket);

        $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $ticket = $this->ticketService->assignAgent(
            $ticket,
            $request->agent_id,
            $request->user()
        );

        return response()->json([
            'message' => 'Agent assigned successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function autoAssign(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('assign', $ticket);

        $agent = $this->ticketService->getAgentWithLeastWorkload();

        if (!$agent) {
            return response()->json(['message' => 'No agents available'], 422);
        }

        $ticket = $this->ticketService->assignAgent($ticket, $agent->id, $request->user());

        return response()->json([
            'message' => 'Agent auto-assigned successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    /**
     * Allow agent to take ownership of an unassigned ticket.
     */
    public function selfAssign(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('selfAssign', $ticket);

        $ticket = $this->ticketService->assignAgent($ticket, $request->user()->id, $request->user());

        return response()->json([
            'message' => 'Ticket assigned to you successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function updateTags(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('manageTags', $ticket);

        $request->validate([
            'tags' => 'present|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $ticket->tags()->sync($request->input('tags', []));
        $ticket->load(['user', 'assignee', 'category', 'tags', 'attachments']);

        return response()->json([
            'message' => 'Tags updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }
}
