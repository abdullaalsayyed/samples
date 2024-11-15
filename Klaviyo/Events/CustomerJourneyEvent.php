<?php

namespace App\Services\Klaviyo\Events;

use Override;
use Illuminate\Support\Facades\Log;
use App\Modules\Scooter\Models\Scooter;
use App\Services\Klaviyo\KlaviyoService;
use App\Modules\Customer\Enums\Countries;
use App\Services\Klaviyo\Models\Customer;
use App\Modules\Scooter\Models\ScooterSaleLookupView;
use App\Services\Klaviyo\Stores\CanadaKlaviyoService;
use App\Services\Klaviyo\Stores\UnitedStatesKlaviyoService;

abstract readonly class CustomerJourneyEvent implements KlaviyoEvent
{
    /**
     * CustomerJourneyEvent constructor.
     *
     * @param string $name
     * @param Customer $customer
     * @param Scooter $scooter
     * @param array $properties
     * @param int $country
     */
    public function __construct(
        private string   $name,
        private Customer $customer,
        private Scooter  $scooter,
        private array    $properties,
        private int      $country,
    )
    {
    }

    #[Override] public function getUniqueIdentifier(): string
    {
        return $this->customer->email . '|' . $this->name . '|' . $this->scooter->serial;
    }

    #[Override] public function getName(): string
    {
        return $this->name;
    }

    #[Override] public function getPayload(): array
    {
        return $this->getProperties();
    }

    #[Override] public function getCustomer(): Customer
    {
        return $this->customer;
    }

    #[Override] public function getService(): KlaviyoService|null
    {
        if (!in_array($this->country, [Countries::CANADA, Countries::USA])) {
            return null;
        }

        return $this->country === Countries::USA ? new UnitedStatesKlaviyoService() : new CanadaKlaviyoService();
    }

    private function getScooterInfo()
    {
        return ScooterSaleLookupView::whereSerial($this->scooter->serial)
            ->first();
    }

    private function getProperties(): array
    {
        return [
            ...$this->properties,
            'boughtFrom'    => $this->getScooterInfo()?->bought_from ?? 'unknown',
            'scooterSerial' => $this->scooter->serial,
            'scooterType'   => getScooterType($this->scooter->serial)
        ];
    }

    #[Override] public function send(): bool
    {
        try {

            return $this->getService()?->sendEvent($this) === 200;

        } catch (\Exception $exception) {

            Log::info('[SERVICES][Klaviyo] failed to send event: ' . $this->getUniqueIdentifier());

            return false;
        }
    }
}
