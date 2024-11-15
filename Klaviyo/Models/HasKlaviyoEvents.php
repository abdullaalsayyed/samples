<?php

namespace App\Services\Klaviyo\Models;

use App\Services\Klaviyo\Events\KlaviyoEvent;

interface HasKlaviyoEvents
{
    public function getKlaviyoEvent(): KlaviyoEvent|null;
}
