<?php

namespace App\Services\Klaviyo\Stores;

use App\Services\Klaviyo\KlaviyoService;

class UnitedStatesKlaviyoService extends KlaviyoService
{
    /**
     * UnitedStatesKlaviyoService constructor
     */
    public function __construct()
    {
        parent::__construct(
            config('klaviyo.usa.key'),
            config('klaviyo.ca.version'),
        );
    }
}
