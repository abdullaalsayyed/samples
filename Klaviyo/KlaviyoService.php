<?php

namespace App\Services\Klaviyo;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\Client\PendingRequest;
use App\Services\Klaviyo\Events\KlaviyoEvent;
use App\Services\Klaviyo\Models\Customer as KlaviyoCustomer;

class KlaviyoService
{
    /**
     * @var PendingRequest
     */
    protected PendingRequest $httpClient;

    private string $baseUrl = 'https://a.klaviyo.com/api/';

    /**
     * KlaviyoService constructor.
     *
     * @param string $key
     * @param string $version
     */
    public function __construct(
        protected readonly string $key,
        protected readonly string $version,
    ) {
        $this->httpClient = Http::withHeaders([
            'revision'      => $version,
            'Authorization' => 'Klaviyo-API-Key ' . $this->key
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Get or Create a profile for a customer.
     *
     * @param Customer $customer
     * @return KlaviyoCustomer|null
     */
    public function getProfile(Customer $customer): KlaviyoCustomer|null
    {
        $result = $this->parseResponse($this->httpClient->post('profile-import', [
            'data' => [
                'type' => 'profile',
                'attributes' => [
                    'first_name'            => $customer->first_name,
                    'last_name'             => $customer->last_name,
                    'email'                 => $customer->email,
                    'external_id'           => $customer->firebase_ref,
                    'properties'            => [
                        'is_stamped_linked'     => $customer->isLinkedToStamped()
                    ]
                ]
            ]
        ]));

        if (isset($result['data'])) {

            return new KlaviyoCustomer(...$result['data']);
        }

        return null;
    }

    /**
     * Send event.
     *
     * @param KlaviyoEvent $event
     * @return int
     */
    public function sendEvent(KlaviyoEvent $event): int
    {
        return $this->httpClient->post('events', [
            'data' => [
                'type'       => 'event',
                'attributes' => [
                    'properties' => $event->getPayload(),
                    'metric'     => [
                        'data' => [
                            'type'       => 'metric',
                            'attributes' => [
                                'name' => $event->getName()
                            ]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type'  => 'profile',
                            'id'    => $event->getCustomer()->id,
                        ]
                    ],
                    'unique_id' => $event->getUniqueIdentifier()
                ],
            ]
        ])->status();
    }

    /**
     * Parse incoming response.
     *
     * @param Response $response
     * @return array|null
     */
    private function parseResponse(Response $response): array|null
    {
        return $response->json();
    }
}
