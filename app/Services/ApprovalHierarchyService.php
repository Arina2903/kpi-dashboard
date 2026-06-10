<?php

namespace App\Services;

class ApprovalHierarchyService
{
    protected SupabaseService $supabase;

    public function __construct(
        SupabaseService $supabase
    ) {
        $this->supabase = $supabase;
    }

    /**
     * Get approver employee record
     */
    public function getApprover(
        array $employee
    ): ?array {

        $role = strtoupper(
            trim(
                $employee['role'] ?? ''
            )
        );

        switch ($role) {

            /*
            |--------------------------------------------------------------------------
            | EXECUTIVE -> MANAGER
            |--------------------------------------------------------------------------
            */
            case 'EXECUTIVE':

                if (
                    !empty($employee['manager_id'])
                ) {
                    return $this->employee(
                        $employee['manager_id']
                    );
                }

                return null;

            /*
            |--------------------------------------------------------------------------
            | MANAGER -> VP
            |--------------------------------------------------------------------------
            */
            case 'MANAGER':

                if (
                    !empty($employee['vp_id'])
                ) {
                    return $this->employee(
                        $employee['vp_id']
                    );
                }

                return null;

            /*
            |--------------------------------------------------------------------------
            | VP -> REPORTS TO
            |--------------------------------------------------------------------------
            */
            case 'VP':

                if (
                    !empty($employee['reports_to_id'])
                ) {
                    return $this->employee(
                        $employee['reports_to_id']
                    );
                }

                return null;

            /*
            |--------------------------------------------------------------------------
            | SLT — top of hierarchy, no approver
            |--------------------------------------------------------------------------
            */
            case 'SLT':

                return null;

            default:

                return null;
        }
    }

    /**
     * Get approver UUID
     */
    public function getApproverId(
        array $employee
    ): ?string {

        $approver = $this->getApprover(
            $employee
        );

        return $approver['id'] ?? null;
    }

    /**
     * Get approver name
     */
    public function getApproverName(
        array $employee
    ): ?string {

        $approver = $this->getApprover(
            $employee
        );

        return $approver['short_name']
            ?? null;
    }

    /**
     * Get approver role
     */
    public function getApproverRole(
        array $employee
    ): ?string {

        $approver = $this->getApprover(
            $employee
        );

        return $approver['role']
            ?? null;
    }

    /**
     * Get employee by UUID
     */
    protected function employee(
        string $id
    ): ?array {

        $result = $this->supabase->get(
            'employees',
            [
                'id'     => 'eq.' . $id,
                'select' => '*',
                'limit'  => 1,
            ]
        ) ?? [];

        return $result[0] ?? null;
    }
}
