<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,
            'price_per_kg' => (float) $this->price_per_kg,
            'effective_from' => $this->effective_from,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
        ];
    }
}
