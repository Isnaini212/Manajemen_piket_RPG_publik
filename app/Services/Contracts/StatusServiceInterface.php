<?php

namespace App\Services\Contracts;

use App\Models\StudentProfile;

interface StatusServiceInterface
{
    /**
     * Transition a student from Citizen to Convict.
     */
    public function changeToConvict(StudentProfile $profile): void;

    /**
     * Transition a student from Convict back to Citizen.
     */
    public function changeBackToCitizen(StudentProfile $profile): void;
}
