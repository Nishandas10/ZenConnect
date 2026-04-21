<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at->toISOString(),
            'open_ticket_count' => $this->when(
                $this->relationLoaded('assignedTickets') || $this->role === 'agent' || $this->role === 'admin',
                fn() => $this->open_ticket_count
            ),
        ];
    }
}
