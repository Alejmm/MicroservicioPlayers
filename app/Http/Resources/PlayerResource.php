<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'number'    => $this->number,
            'position'  => $this->position,
            'team_id'   => $this->team_id,
            'photo_url' => $this->photo_url,
            'created_at'=> $this->created_at?->toISOString(),
            'updated_at'=> $this->updated_at?->toISOString(),
        ];
    }
}
