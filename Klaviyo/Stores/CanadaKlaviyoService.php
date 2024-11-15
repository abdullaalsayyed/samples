<?php

namespace App\Services\Klaviyo\Stores;

use App\Services\Klaviyo\KlaviyoService;

class CanadaKlaviyoService extends KlaviyoService
{
    /**
     * CanadaKlaviyoService constructor.
     */
    public function __construct()
    {
        parent::__construct(
            config('klaviyo.ca.key'),
            config('klaviyo.ca.version'),
        );
    }
}
