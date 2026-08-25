<?php

namespace App\View\Composers;

use App\Config\Navigation;
use Illuminate\View\View;

class NavigationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        try {
            $navigation = $user
                ? Navigation::getForRole($user->role)
                : ['main' => Navigation::get()['main']];
        } catch (\Exception $e) {
            // Fallback to main navigation if Navigation class fails
            $navigation = ['main' => Navigation::get()['main']];
        }

        $view->with('navigation', $navigation);
    }
}
