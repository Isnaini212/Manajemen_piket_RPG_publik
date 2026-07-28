<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        if (auth()->check() && auth()->user()->role === \App\Enums\UserRole::Admin->value) {
            return view('layouts.admin');
        }
        return view('layouts.app');
    }
}
