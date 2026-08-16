<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'profile_url' => $this->profile_photo
                ? asset('storage/' . $this->profile_photo)
                : null,
        ];
    }
}