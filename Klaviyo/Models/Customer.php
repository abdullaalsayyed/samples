<?php

namespace App\Services\Klaviyo\Models;

use AllowDynamicProperties;

#[AllowDynamicProperties]
final class Customer
{
    public function __construct(
        public string $id,
        public array $attributes,
        ...$values
    )
    {
        $this->email = $this->attributes['email'];
        $this->apolloId = $this->attributes['external_id'];
        $this->values = $values;
    }
}
