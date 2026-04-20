<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'ai_summary' => $this->ai_summary,
            'sla_deadline' => $this->sla_deadline?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'comments' => TicketCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'histories' => TicketHistoryResource::collection($this->whenLoaded('histories')),
            'comments_count' => $this->when($this->comments_count !== null, $this->comments_count),
        ];
    }
}
