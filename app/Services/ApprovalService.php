<?php

namespace App\Services;

class ApprovalService
{
    protected SupabaseService $supabase;

    public function __construct(
        SupabaseService $supabase
    ) {
        $this->supabase = $supabase;
    }

    public function alreadyPending(
        string $table,
        string $kpiId
    ): bool {
        return $this->countPending(
            $table,
            $kpiId
        ) > 0;
    }

    public function countPending(
        string $table,
        string $kpiId
    ): int {
        $rows = $this->supabase->get(
            $table,
            [
                'kpi_id' => 'eq.' . $kpiId,
                'status' => 'eq.pending',
            ]
        );

        return count($rows);
    }

    public function getPending(
        string $table,
        string $kpiId
    ): array {
        return $this->supabase->get(
            $table,
            [
                'kpi_id' => 'eq.' . $kpiId,
                'status' => 'eq.pending',
            ]
        );
    }

    public function alreadyPendingQuarter(
        string $kpiId,
        string $quarter
    ): bool {

        $rows = $this->supabase->get(
            'kpi_update_approvals',
            [
                'kpi_id'  => 'eq.' . $kpiId,
                'quarter' => 'eq.' . $quarter,
                'status'  => 'eq.pending',
            ]
        );

        return !empty($rows);
    }
}
