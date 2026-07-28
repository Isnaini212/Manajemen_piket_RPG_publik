<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Failed = 'failed';
}
