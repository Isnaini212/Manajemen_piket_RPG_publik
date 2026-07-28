<?php

namespace App\Enums;

enum VerifyStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RejectedFinal = 'rejected_final';
}
