<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * True whenever the acting user is BTS — this app's only cross-
     * department/company admin concept — either in their own normal
     * session, or currently impersonating someone via "View As".
     *
     * While impersonating, session('department_code'), role, hr_access,
     * etc. all become the IMPERSONATED employee's (see
     * AdminController::start()), so any permission gate that checks those
     * fields directly loses BTS's own access the moment View As starts.
     * admin_impersonating is set independently of those swapped fields, so
     * checking it here keeps BTS's full access while viewing as anyone.
     */
    protected function isBtsSession(): bool
    {
        if (session('admin_impersonating') === true) {
            return true;
        }

        return strtoupper(trim(session('department_code') ?? '')) === 'BTS';
    }
}
