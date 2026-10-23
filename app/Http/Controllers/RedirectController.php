<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    /**
     * Redirect an authenticated user to the dashboard
     * belonging to their role.
     */
    public function __invoke(): RedirectResponse
    {
        $user = request()->user();

        return match (true) {
            $user->isAdmin() =>
                redirect()->route('admin.dashboard'),

            $user->isElectionOfficer() =>
                redirect()->route('officer.dashboard'),

            $user->isObserver() =>
                redirect()->route('observer.dashboard'),

            default =>
                abort(403, 'Your account does not have a valid application role.'),
        };
    }
}
