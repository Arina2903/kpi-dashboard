<?php

namespace App\Services;

class ApprovalActionService
{
    protected SupabaseService $supabase;

    public function __construct(
        SupabaseService $supabase
    ) {
        $this->supabase = $supabase;
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE RECORD
    |--------------------------------------------------------------------------
    */

    public function approve(
        string $table,
        string $id,
        string $employeeId,
        string $employeeName
    ): bool {

        $data = [

            'status'      => 'approved',
            'approved_by' => $employeeId,
            'approved_at' => now(),

        ];

        /*
        |--------------------------------------------------------------------------
        | TABLES WITH approved_by_name
        |--------------------------------------------------------------------------
        */

        if(
            in_array(
                $table,
                [
                    'kpi_update_approvals',
                    'kpi_target_change_requests',
                    'kpi_delete_requests',
                ]
            )
        ){
            $data['approved_by_name']
                = $employeeName;
        }

        if(
            $table === 'kpi_update_approvals'
        ){

            $data['is_viewed']
                = true;

            $data['viewed_at']
                = now();
        }

        return $this->supabase->safePatch(

            $table,

            [

                'id'
                    => 'eq.' . $id,

                'status'
                    => 'eq.pending',

            ],

            $data

        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT RECORD
    |--------------------------------------------------------------------------
    */

    public function reject(
        string $table,
        string $id,
        string $employeeId,
        string $employeeName,
        string $reason
    ): bool {

        $data = [

            'status' => 'rejected',

            'rejected_by' =>
                $employeeId,

            'rejected_at' =>
                now()->toDateTimeString(),

            'rejection_reason' =>
                $reason,

            'approver_remark' =>
                $reason,

        ];

        /*
        |--------------------------------------------------------------------------
        | TABLES WITH rejected_by_name
        |--------------------------------------------------------------------------
        */

        if(
            in_array(
                $table,
                [
                    'kpi_update_approvals'
                ]
            )
        ){

            $data['rejected_by_name']
                = $employeeName;
        }

        /*
        |--------------------------------------------------------------------------
        | VIEWED FLAG
        |--------------------------------------------------------------------------
        */

        if(
            $table === 'kpi_update_approvals'
        ){

            $data['is_viewed']
                = true;

            $data['viewed_at']
                = now();
        }

        return $this->supabase->safePatch(

            $table,

            [

                'id'
                    => 'eq.' . $id,

                'status'
                    => 'eq.pending'

            ],

            $data

        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */

    public function history(
        string $kpiId,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        string $employeeId,
        string $employeeName
    ): bool {

        return $this->supabase->safeInsert(
            'kpi_histories',
            [
                'kpi_id'         => $kpiId,
                'edited_by'      => $employeeId,
                'edited_by_name' => $employeeName,
                'field_name'     => $field,
                'old_value' => json_encode($oldValue, JSON_UNESCAPED_UNICODE),
                'new_value' => json_encode($newValue, JSON_UNESCAPED_UNICODE),
                'created_at'     => now(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET KPI
    |--------------------------------------------------------------------------
    */

    public function kpi(
        string $kpiId
    ): ?array {

        $rows = $this->supabase->get(
            'kpis',
            [
                'id'     => 'eq.' . $kpiId,
                'select' => '*',
                'limit'  => 1,
            ]
        ) ?? [];

        return $rows[0] ?? null;
    }

    private function calculateAchievement(
        $base,
        $stretch,
        $actual
    )
    {

        $base = max(
            0,
            (float)$base
        );

        $stretch = max(
            $base,
            (float)$stretch
        );

        $actual = max(
            0,
            (float)$actual
        );

        if($base <= 0){
            return 0;
        }

        if($actual <= $base){

            return round(
                ($actual / $base) * 100,
                2
            );
        }

        if($stretch > $base){

            return round(

                min(
                    200,

                    100 +

                    (
                        ($actual - $base)
                        /
                        ($stretch - $base)
                    ) * 100

                ),

                2
            );
        }

        return 100;
    }

    public function approveQuarter(
        array $approval,
        string $employeeId,
        string $employeeName
    )
    {

        if(
            ($approval['status'] ?? '')
            !== 'pending'
        ){
            return response()->json([
                'success' => false,
                'message' => 'Already processed'
            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE QUARTER
        |--------------------------------------------------------------------------
        */

        $updated = $this->supabase->safePatch(
            'kpi_quarters',
            [
                'id' =>
                    'eq.' . $approval['quarter_id']
            ],
            [
                'quarter_actual' =>
                    $approval['requested_actual'],

                'updated_at' =>
                    now()
            ]
        );

        if(!$updated){
            return $this->fail(
                'Failed updating quarter'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RECALCULATE KPI
        |--------------------------------------------------------------------------
        */

        $quarters = $this->supabase->get(
            'kpi_quarters',
            [
                'kpi_id' =>
                    'eq.' . $approval['kpi_id']
            ]
        ) ?? [];

        $overallActual = collect($quarters)
            ->sum(
                fn($q)
                =>
                (float)
                ($q['quarter_actual'] ?? 0)
            );

        $kpi = $this->kpi(
            $approval['kpi_id']
        );

        if(!$kpi){
            return $this->fail(
                'KPI not found'
            );
        }

        $achievement =
            $this->calculateAchievement(
                $kpi['base_target'] ?? 0,
                $kpi['stretch_target'] ?? 0,
                $overallActual
            );

        $updated = $this->supabase->safePatch(
            'kpis',
            [
                'id' =>
                    'eq.' .
                    $approval['kpi_id']
            ],
            [
                'actual_value' =>
                    $overallActual,

                'achievement_percentage' =>
                    $achievement,

                'updated_at' =>
                    now()
            ]
        );

        if(!$updated){
            return $this->fail(
                'Failed updating KPI'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HISTORY
        |--------------------------------------------------------------------------
        */

        $saved = $this->history(
            $approval['kpi_id'],
            'quarter_actual',
            $approval['old_actual'] ?? 0,
            $approval['requested_actual'],
            $employeeId,
            $employeeName
        );

        if(!$saved){
            return $this->fail(
                'Failed saving history'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVE
        |--------------------------------------------------------------------------
        */

        $approved = $this->approve(
            'kpi_update_approvals',
            $approval['id'],
            $employeeId,
            $employeeName
        );

        if(!$approved){
            return $this->fail(
                'Failed approving request'
            );
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function approveTarget(
        array $request,
        string $employeeId,
        string $employeeName
    )
    {

        if(
            ($request['status'] ?? '')
            !== 'pending'
        ){
            return response()->json([
                'success'=>false,
                'message'=>'Already processed'
            ],422);
        }

        $updated =
            $this->supabase->safePatch(

                'kpis',

                [

                    'id'
                        => 'eq.' .
                        $request['kpi_id']

                ],

                [

                    'base_target'
                        =>
                        $request['new_base_target'],

                    'stretch_target'
                        =>
                        $request['new_stretch_target'],

                    'updated_at'
                        =>
                        now()

                ]

);

        if(!$updated){
            return $this->fail(
                'Failed updating target'
            );
        }

        $kpi = $this->kpi(
            $request['kpi_id']
        );

        if(!$kpi){

            return response()->json([
                'success'=>false,
                'message'=>'KPI not found'
            ],404);
        }

        $achievement =
            $this->calculateAchievement(
                $kpi['base_target'],
                $kpi['stretch_target'],
                $kpi['actual_value']
            );

        $updated = $this->supabase->safePatch(
            'kpis',
            [
                'id' =>
                    'eq.' .
                    $request['kpi_id']
            ],
            [
                'achievement_percentage'
                    => $achievement,

                'updated_at'
                    => now()
            ]
        );

        if(!$updated){
            return $this->fail(
                'Failed updating achievement'
            );
        }

        $saved = $this->history(

            $request['kpi_id'],

            'target_change',

            [

                'base_target'
                    => $request['old_base_target'],

                'stretch_target'
                    => $request['old_stretch_target']

            ],

            [

                'base_target'
                    => $request['new_base_target'],

                'stretch_target'
                    => $request['new_stretch_target']

            ],

            $employeeId,

            $employeeName
        );

        if(!$saved){
            return $this->fail(
                'Failed saving history'
            );
        }

        $approved = $this->approve(
            'kpi_target_change_requests',
            $request['id'],
            $employeeId,
            $employeeName
        );

        if(!$approved){
            return $this->fail(
                'Failed approving target request'
            );
        }

        return response()->json([
            'success'=>true
        ]);
    }

    public function approveDelete(
        array $request,
        string $employeeId,
        string $employeeName
    )
    {
        /*
        |--------------------------------------------------------------------------
        | SAFETY CHECK
        |--------------------------------------------------------------------------
        */

        if(
            ($request['status'] ?? '')
            !== 'pending'
        ){
            return response()->json([
                'success' => false,
                'message' => 'Already processed'
            ],422);
        }

        $kpiId = $request['kpi_id'];

        /*
        |--------------------------------------------------------------------------
        | DELETE KPI HISTORIES
        |--------------------------------------------------------------------------
        */

        $this->supabase->safeDelete(
            'kpi_histories',
            [
                'kpi_id' => 'eq.' . $kpiId
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE KPI QUARTERS
        |--------------------------------------------------------------------------
        */

        $this->supabase->safeDelete(
            'kpi_quarters',
            [
                'kpi_id' => 'eq.' . $kpiId
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE KPI UPDATE APPROVALS
        |--------------------------------------------------------------------------
        */

        $this->supabase->safeDelete(
            'kpi_update_approvals',
            [
                'kpi_id' => 'eq.' . $kpiId
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE TARGET CHANGE REQUESTS
        |--------------------------------------------------------------------------
        */

        $this->supabase->safeDelete(
            'kpi_target_change_requests',
            [
                'kpi_id' => 'eq.' . $kpiId
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE ALL DELETE REQUESTS
        |--------------------------------------------------------------------------
        */

        $this->supabase->safeDelete(
            'kpi_delete_requests',
            [
                'kpi_id' => 'eq.' . $kpiId
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE KPI
        |--------------------------------------------------------------------------
        */

        $deleted = $this->supabase->safeDelete(
            'kpis',
            [
                'id' => 'eq.' . $kpiId
            ]
        );

        if(!$deleted){

            return response()->json([
                'success' => false,
                'message' => 'Failed deleting KPI'
            ],500);
        }

        return response()->json([
            'success' => true,
            'message' => 'KPI deleted successfully'
        ]);
    }

    public function approveWeightage(
        array $request,
        string $employeeId,
        string $employeeName
    )
    {
        if(($request['status'] ?? '') !== 'pending'){
            return response()->json([
                'success' => false,
                'message' => 'Already processed'
            ], 422);
        }

        $this->supabase->patch(
            'kpis',
            ['id' => 'eq.' . $request['kpi_id']],
            [
                'weightage'  => round((float)($request['new_weightage'] ?? 0), 2),
                'updated_at' => now()->toDateTimeString(),
            ]
        );

        $this->history(
            $request['kpi_id'],
            'weightage_change',
            $request['old_weightage'] ?? 0,
            $request['new_weightage'] ?? 0,
            $employeeId,
            $employeeName
        );

        $this->supabase->patch(
            'kpi_target_change_requests',
            ['id' => 'eq.' . $request['id']],
            [
                'status'      => 'approved',
                'approved_by' => $employeeId,
                'approved_at' => now()->toDateTimeString(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function autoApproveQuarter(
        array $approval,
        string $employeeId,
        string $employeeName
    )
    {
        /*
        |--------------------------------------------------------------------------
        | UPDATE QUARTER
        |--------------------------------------------------------------------------
        */

        $updated = $this->supabase->safePatch(
            'kpi_quarters',
            [
                'id'
                    => 'eq.' .
                    $approval['quarter_id']
            ],
            [
                'quarter_actual'
                    => $approval['requested_actual'],

                'updated_at'
                    => now()
            ]
        );

        if(!$updated){

            return $this->fail(
                'Failed updating quarter'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RECALCULATE KPI
        |--------------------------------------------------------------------------
        */

        $quarters = $this->supabase->get(
            'kpi_quarters',
            [
                'kpi_id'
                    => 'eq.' .
                    $approval['kpi_id']
            ]
        ) ?? [];

        $overallActual = collect($quarters)
            ->sum(
                fn($q)
                =>
                (float)
                ($q['quarter_actual'] ?? 0)
            );

        $kpi = $this->kpi(
            $approval['kpi_id']
        );

        if(!$kpi){

            return $this->fail(
                'KPI not found'
            );
        }

        $achievement =
            $this->calculateAchievement(

                $kpi['base_target'] ?? 0,

                $kpi['stretch_target'] ?? 0,

                $overallActual
            );

        $updated = $this->supabase->safePatch(
            'kpis',
            [
                'id'
                    => 'eq.' .
                    $approval['kpi_id']
            ],
            [
                'actual_value'
                    => $overallActual,

                'achievement_percentage'
                    => $achievement,

                'updated_at'
                    => now()
            ]
        );

        if(!$updated){

            return $this->fail(
                'Failed updating KPI'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HISTORY
        |--------------------------------------------------------------------------
        */

        $saved = $this->history(

            $approval['kpi_id'],

            'quarter_actual',

            $approval['old_actual'] ?? 0,

            $approval['requested_actual'],

            $employeeId,

            $employeeName

        );

        if(!$saved){

            return $this->fail(
                'Failed saving history'
            );
        }

        $approved = $this->supabase->safePatch(
            'kpi_update_approvals',
            [
                'id'
                    => 'eq.' . $approval['id']
            ],
            [
                'status'
                    => 'approved',

                'approved_by'
                    => $employeeId,

                'approved_by_name'
                    => $employeeName,

                'approved_at'
                    => now(),

                'is_viewed'
                    => true,

                'viewed_at'
                    => now()
            ]
        );

        if(!$approved){

            return $this->fail(
                'Failed approving auto approval record'
            );
        }

        return true;
    }

    private function fail(
        string $message
    ){
        logger()->error('ApprovalActionService: ' . $message);
        throw new \RuntimeException($message);
    }
}
