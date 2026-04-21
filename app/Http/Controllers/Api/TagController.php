<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::orderBy('name')->get();

        return response()->json(['data' => $tags]);
    }

    /**
     * Create (or find) a tag by name and attach it to a ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:50',
            'ticket_id' => 'required|exists:tickets,id',
        ]);

        $this->authorize('manageTags', Ticket::findOrFail($request->ticket_id));

        $name = trim($request->name);
        $slug = Str::slug($name);

        $tag = Tag::firstOrCreate(
            ['slug' => $slug],
            [
                'name'  => $name,
                'slug'  => $slug,
                'color' => $this->randomColor(),
            ]
        );

        $ticket = Ticket::findOrFail($request->ticket_id);
        $ticket->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json(['tag' => $tag], 201);
    }

    private function randomColor(): string
    {
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];

        return $colors[array_rand($colors)];
    }
}
