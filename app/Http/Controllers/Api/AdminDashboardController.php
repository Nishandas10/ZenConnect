<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && !$request->user()->isAgent()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'metrics' => $this->getMetrics(),
            'tickets_by_status' => $this->getTicketsByStatus(),
            'tickets_by_category' => $this->getTicketsByCategory(),
            'tickets_by_priority' => $this->getTicketsByPriority(),
            'agent_performance' => $this->getAgentPerformance(),
            'recent_tickets' => $this->getRecentTickets(),
            'daily_ticket_counts' => $this->getDailyTicketCounts(),
        ]);
    }

    protected function getMetrics(): array
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', 'open')->count();
        $inProgressTickets = Ticket::where('status', 'in_progress')->count();
        $resolvedTickets = Ticket::where('status', 'resolved')->count();
        $closedTickets = Ticket::where('status', 'closed')->count();

        $avgResolutionTime = Ticket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        $todayTickets = Ticket::whereDate('created_at', today())->count();
        $thisWeekTickets = Ticket::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'in_progress_tickets' => $inProgressTickets,
            'resolved_tickets' => $resolvedTickets,
            'closed_tickets' => $closedTickets,
            'avg_resolution_hours' => round($avgResolutionTime ?? 0, 1),
            'today_tickets' => $todayTickets,
            'this_week_tickets' => $thisWeekTickets,
        ];
    }

    protected function getTicketsByStatus(): array
    {
        return Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function getTicketsByCategory(): array
    {
        return Ticket::join('categories', 'tickets.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.name')
            ->pluck('count', 'name')
            ->toArray();
    }

    protected function getTicketsByPriority(): array
    {
        return Ticket::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
    }

    protected function getAgentPerformance(): array
    {
        return User::agents()
            ->withCount([
                'assignedTickets',
                'assignedTickets as resolved_count' => function ($query) {
                    $query->whereIn('status', ['resolved', 'closed']);
                },
                'assignedTickets as open_count' => function ($query) {
                    $query->whereIn('status', ['open', 'in_progress']);
                },
            ])
            ->get()
            ->map(function ($agent) {
                $avgTime = Ticket::where('assigned_to', $agent->id)
                    ->whereNotNull('resolved_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                    ->value('avg_hours');

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'total_assigned' => $agent->assigned_tickets_count,
                    'resolved' => $agent->resolved_count,
                    'open' => $agent->open_count,
                    'avg_resolution_hours' => round($avgTime ?? 0, 1),
                ];
            })
            ->toArray();
    }

    protected function getRecentTickets(): array
    {
        return Ticket::with(['user', 'assignee', 'category'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'user' => $ticket->user->name,
                    'assignee' => $ticket->assignee?->name,
                    'category' => $ticket->category?->name,
                    'created_at' => $ticket->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    protected function getDailyTicketCounts(): array
    {
        return Ticket::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
