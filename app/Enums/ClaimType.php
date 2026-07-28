<?php

namespace App\Enums;

enum ClaimType: string
{
    case REGULAR = 'regular';
    case REPLACEMENT = 'replacement';
    case PUNISHMENT = 'punishment';
}
