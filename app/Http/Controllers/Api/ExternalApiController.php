<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Category;
use App\Models\ExternalApp;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class ExternalApiController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    /**
     * Create a ticket from an external app.
     */
    public function createTicket(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'category_slug' => 'sometimes|nullable|string|max:100',
            'customer_id' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_name' => 'required|string|max:255',
            'tags' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        /** @var ExternalApp $externalApp */
        $externalApp = $request->get('external_app');

        // Find or create a placeholder user for the external customer
        $user = User::firstOrCreate(
            ['email' => $request->customer_email],
            [
                'name' => $request->customer_name,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                'role' => 'user',
            ]
        );

        // Resolve category by slug if provided
        $categoryId = $request->category_id;
        if (!$categoryId && $request->category_slug) {
            $category = Category::where('slug', $request->category_slug)->first();
            $categoryId = $category?->id;
        }

        // Create ticket data
        $ticketData = [
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'category_id' => $categoryId,
            'tags' => $request->tags ?? [],
        ];

        // Create the ticket using existing service
        $ticket = $this->ticketService->createTicket($ticketData, $user);

        // Update with external app info
        $ticket->update([
            'external_app_id' => $externalApp->id,
            'external_customer_id' => $request->customer_id,
            'external_customer_email' => $request->customer_email,
            'external_customer_name' => $request->customer_name,
        ]);

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get tickets for a specific external customer.
     */
    public function getCustomerTickets(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string|max:255',
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        /** @var ExternalApp $externalApp */
        $externalApp = $request->get('external_app');

        $query = Ticket::with(['category', 'assignee'])
            ->where('external_app_id', $externalApp->id)
            ->where('external_customer_id', $request->customer_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'tickets' => $tickets->getCollection()->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category?->name,
                    'assigned_to' => $ticket->assignee?->name,
                    'created_at' => $ticket->created_at->toIso8601String(),
                    'updated_at' => $ticket->updated_at->toIso8601String(),
                    'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'total_pages' => $tickets->lastPage(),
                'total_items' => $tickets->total(),
                'per_page' => $tickets->perPage(),
            ],
        ]);
    }

    /**
     * Get a specific ticket with comments.
     */
    public function getTicket(Request $request, int $ticketId): JsonResponse
    {
        /** @var ExternalApp $externalApp */
        $externalApp = $request->get('external_app');

        $ticket = Ticket::with(['category', 'assignee', 'comments' => function ($query) {
                // Only show non-internal comments to external customers
                $query->where('is_internal', false)
                    ->with('user')
                    ->orderBy('created_at', 'asc');
            }])
            ->where('external_app_id', $externalApp->id)
            ->where('id', $ticketId)
            ->first();

        if (!$ticket) {
            return response()->json([
                'error' => 'Ticket not found',
                'message' => 'The requested ticket does not exist or does not belong to this app',
            ], 404);
        }

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category?->name,
                'assigned_to' => $ticket->assignee?->name,
                'created_at' => $ticket->created_at->toIso8601String(),
                'updated_at' => $ticket->updated_at->toIso8601String(),
                'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                'comments' => $ticket->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'author' => $comment->user->name,
                        'author_role' => $comment->user->role,
                        'created_at' => $comment->created_at->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Add a comment from an external customer.
     */
    public function addComment(Request $request, int $ticketId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        /** @var ExternalApp $externalApp */
        $externalApp = $request->get('external_app');

        $ticket = Ticket::where('external_app_id', $externalApp->id)
            ->where('id', $ticketId)
            ->where('external_customer_id', $request->customer_id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'error' => 'Ticket not found',
                'message' => 'The requested ticket does not exist or does not belong to this customer',
            ], 404);
        }

        // Find the user associated with this external customer
        $user = User::where('email', $ticket->external_customer_email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'Customer not found',
                'message' => 'Could not find the customer associated with this ticket',
            ], 404);
        }

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $request->body,
            'is_internal' => false,
        ]);

        // If ticket was resolved/closed and customer replies, reopen it
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => $user->name,
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get available categories for the external app.
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->select('id', 'name', 'slug', 'description')
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Register/update webhook URL for the external app.
     */
    public function registerWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        /** @var ExternalApp $externalApp */
        $externalApp = $request->get('external_app');

        $externalApp->update([
            'webhook_url' => $request->webhook_url,
        ]);

        // Generate webhook secret if not exists
        $webhookSecret = $externalApp->webhook_secret;
        if (!$webhookSecret) {
            $webhookSecret = $externalApp->generateWebhookSecret();
        }

        return response()->json([
            'message' => 'Webhook registered successfully',
            'webhook_url' => $request->webhook_url,
            'webhook_secret' => $webhookSecret,
            'info' => 'Use this secret to verify webhook signatures. Each webhook request includes X-Webhook-Signature header.',
        ]);
    }
}
