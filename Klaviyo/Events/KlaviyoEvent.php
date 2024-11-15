<?php

namespace App\Services\Klaviyo\Events;

use App\Services\Klaviyo\KlaviyoService;
use App\Services\Klaviyo\Models\Customer;

interface KlaviyoEvent
{
    /**
     * Get unique ID consists of email|event-name|scooter-serial
     *
     * @return string
     */
    public function getUniqueIdentifier(): string;

    /**
     * Get event name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get event payload.
     *
     * @return array
     */
    public function getPayload(): array;

    /**
     * Receiver.
     *
     * @return Customer
     */
    public function getCustomer(): Customer;

    /**
     * Get targeted service to be sent to.
     *
     * @return KlaviyoService|null
     */
    public function getService(): KlaviyoService|null;

    /**
     * Push the event to the targeted service.
     *
     * @return bool
     */
    public function send(): bool;
}
