<?php

namespace App\Services\Leaderboard\Enums;

enum RedisGeoCommand: string
{
    case RadiusByCoordinates = 'georadius';
    case RadiusByMember = 'georadiusbymember';
}
