<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmailVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $required = filter_var(SystemConfig::get('require_email_verification', 'false'), FILTER_VALIDATE_BOOLEAN);

        if ($required && $request->user() && ! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
