<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class LanguageResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            "id"                             => $this->id,
            "name"                           => $this->name,
            "code"                           => $this->code,
        ];
    }
}
