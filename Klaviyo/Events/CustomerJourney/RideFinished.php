<?php

namespace App\Services\Klaviyo\Events\CustomerJourney;

use App\Modules\Scooter\Models\Scooter;
use App\Services\Klaviyo\Models\Customer;
use App\Services\Klaviyo\Events\CustomerJourneyEvent;
use Override;

final readonly class RideFinished extends CustomerJourneyEvent
{
    /**
     * this event will be fired when a customer saves a ride.
     *
     * @param Customer $customer
     * @param Scooter $scooter
     * @param int $rideCount
     * @param string $rideId
     * @param int $country
     */
    public function __construct(
        Customer $customer,
        Scooter $scooter,
        private int $rideCount,
        string $rideId,
        int $country
    )
    {
        parent::__construct('App: Finish Ride',
            $customer,
            $scooter,
            [
                'rideCount'     => $rideCount,
                'rideId'        => $rideId
            ],
            $country
        );
    }

    #[Override] public function getUniqueIdentifier(): string
    {
        return parent::getUniqueIdentifier().'|'.$this->rideCount;
    }
}
