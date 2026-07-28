<?php

namespace App\Http\Middleware;

use App\Enums\StudentStatus;
use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfile
{
    /**
     * Guarantee the authenticated student has a profile, creating a default
     * one on the fly if it is missing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && ! $user->studentProfile) {
            $user->studentProfile()->create([
                'xp' => 0,
                'lives' => (int) (SystemConfig::get('lives_max') ?? 3),
                'status' => StudentStatus::CITIZEN->value,
                'status_since' => null,
            ]);

            // Drop the cached null relation so downstream code sees the profile.
            $user->unsetRelation('studentProfile');
        }

        return $next($request);
    }
}
