<?php

namespace App\Services\Contracts;

interface SemesterServiceInterface
{
    /**
     * Close the active semester and start a fresh one, resetting every
     * student's RPG stats while preserving historical logs.
     */
    public function resetAll(): void;
}
