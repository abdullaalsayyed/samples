<?php

namespace App\Services\Leaderboard\Enums;

enum DistanceUnit: string
{
    case KILOMETERS = 'km';
    case METERS = 'm';
    case MILES = 'mi';
    case FEET = 'ft';
}
