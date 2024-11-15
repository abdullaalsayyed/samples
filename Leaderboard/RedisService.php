<?php

namespace App\Services\Leaderboard;

use Illuminate\Support\Facades\Redis;
use App\Services\Leaderboard\Enums\DistanceUnit;
use App\Services\Leaderboard\Enums\RedisGeoCommand;

class RedisService
{
    protected string $geoKey = 'customers:locations';
    protected string $scoreKey = 'customers:scores';

    /**
     * Add a customer's location to the geospatial set.
     *
     * @param string $customerId
     * @param float $longitude
     * @param float $latitude
     * @return void
     */
    public function setCustomerLocation(string $customerId, float $longitude, float $latitude): void
    {
        Redis::command('geoadd', [$this->geoKey, $longitude, $latitude, $customerId]);
    }

    /**
     * Get nearby customers around a specific point (longitude, latitude).
     *
     * @param float $longitude
     * @param float $latitude
     * @param float $radius
     * @param DistanceUnit $unit
     * @return array
     */
    public function getNearbyCustomersByPoint(float $longitude, float $latitude, float $radius = 30, DistanceUnit $unit = DistanceUnit::KILOMETERS): array
    {
        return Redis::command(RedisGeoCommand::RadiusByCoordinates->value, [$this->geoKey, $longitude, $latitude, $radius, $unit->value]);
    }

    /**
     * Get nearby customers around a specific customer by ID.
     *
     * @param string $customerId
     * @param float $radius
     * @param DistanceUnit $unit
     * @param int $limit
     *
     * @return array{top_customers: array, neighbors: array}
     */
    public function getNearbyCustomersWithLocalRank(
        string $customerId,
        float  $radius = 30,
        DistanceUnit $unit = DistanceUnit::KILOMETERS,
        int    $limit = 10
    ): array
    {
        $nearbyCustomerIds = Redis::command(RedisGeoCommand::RadiusByMember->value, [
            $this->geoKey,
            $customerId,
            $radius,
            $unit->value,
            'WITHDIST',
            'ASC',
        ]);

        $customers = [];

        foreach ($nearbyCustomerIds as $entry) {
            $id = $entry[0];
            $distance = $entry[1];

            $score = Redis::zscore($this->scoreKey, $id);

            $customers[] = [
                'customer_id' => $id,
                'score' => ceil($score),
                'distance' => ceil($distance)
            ];
        }

        usort($customers, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($customers as $index => $customer) {
            $customers[$index]['rank'] = $index + 1; // 1-based rank
        }


        $currentCustomerIndex = array_search($customerId, array_column($customers, 'customer_id'));
        $isInTop = $currentCustomerIndex !== false && $currentCustomerIndex < $limit;

        if ($isInTop) {
            return [
                'top_customers' => array_slice($customers, 0, $limit),
                'neighbors' => [],
            ];
        }

        $results = [];

        if ($currentCustomerIndex < $limit) {
            return [
                'top_customers' => array_slice($customers, 0, $limit),
                'neighbors' => [],
            ];
        }

        if ($currentCustomerIndex > 0) {
            $results[] = $customers[$currentCustomerIndex - 1]; // Add the customer above
        }

        $results[] = $customers[$currentCustomerIndex]; // Add the current customer

        if ($currentCustomerIndex < count($customers) - 1) {
            $results[] = $customers[$currentCustomerIndex + 1]; // Add the customer below
        }

        $topThree = array_slice($customers, 0, 3);

        return [
            'top_customers' => $topThree,
            'neighbors' => $results,
        ];
    }

    /**
     * Add or update a customer's score.
     *
     * @param string $customerId
     * @param float $score
     * @return void
     */
    public function addCustomerScore(string $customerId, float $score): void
    {
        Redis::zadd($this->scoreKey, $score, $customerId);
    }

    /**
     * Get a customer's score.
     *
     * @param string $customerId
     * @return float|null
     */
    public function getCustomerScore(string $customerId): ?float
    {
        return Redis::zscore($this->scoreKey, $customerId);
    }

    /**
     * Get the rank of a customer within the sorted set.
     *
     * @param string $customerId
     * @return int|null
     */
    public function getCustomerRank(string $customerId): ?int
    {
        $rank = Redis::zrevrank($this->scoreKey, $customerId);
        return $rank !== null ? $rank + 1 : null; // Adjust for 1-based index
    }

    /**
     * Get the top customers by score.
     *
     * @param int $limit
     * @return array
     */
    public function getTopCustomers(int $limit = 10): array
    {
        return Redis::zrevrange($this->scoreKey, 0, $limit - 1, 'WITHSCORES');
    }
}

