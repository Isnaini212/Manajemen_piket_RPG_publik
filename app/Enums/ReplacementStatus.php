<?php

namespace App\Enums;

enum ReplacementStatus: string
{
    case OFFERED = 'offered';
    case TAKEN = 'taken';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
}
