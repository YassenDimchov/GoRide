<?php

namespace App\Enums;

enum RideStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
