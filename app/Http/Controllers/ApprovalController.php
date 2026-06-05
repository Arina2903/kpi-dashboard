<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use App\Services\ApprovalActionService;

class ApprovalController extends Controller
{
    protected ApprovalActionService $approvalActionService;

    public function __construct(
        ApprovalActionService $approvalActionService
    ){
        $this->approvalActionService =
            $approvalActionService;
    }



    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(
        SupabaseService $supabase
    ){

        $userId = session('employee_uuid');

        if(!$userId){

            return redirect()
                ->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | QUARTER APPROVALS
        |--------------------------------------------------------------------------
        */

        $quarterApprovals = $supabase->get(

            'kpi_update_approvals',

            [

                'approver_id' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'order' =>
                    'created_at.desc',

            ]

        ) ?? [];

        /*
        |--------------------------------------------------------------------------
        | TARGET CHANGE REQUESTS
        |--------------------------------------------------------------------------
        */

        $targetRequests = $supabase->get(

            'kpi_target_change_requests',

            [

                'approver_id' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'order' =>
                    'created_at.desc',

            ]

        ) ?? [];

        /*
        |--------------------------------------------------------------------------
        | DELETE REQUESTS
        |--------------------------------------------------------------------------
        */

        $deleteRequests = $supabase->get(

            'kpi_delete_requests',

            [

                'approver_id' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'order' =>
                    'created_at.desc',

            ]

        ) ?? [];

        /*
        |--------------------------------------------------------------------------
        | ENRICH
        |--------------------------------------------------------------------------
        */

        $quarterApprovals = $this->enrichApprovals(
            $quarterApprovals,
            'quarter_update',
            $supabase
        );

        $targetRequests = $this->enrichApprovals(
            $targetRequests,
            'target_change',
            $supabase
        );

        $deleteRequests = $this->enrichApprovals(
            $deleteRequests,
            'delete_request',
            $supabase
        );

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $quarterCount =
            count(
                array_filter(
                    $quarterApprovals,
                    fn($x)
                        => ($x['status'] ?? '')
                        === 'pending'
                )
            );

        $targetCount =
            count(
                array_filter(
                    $targetRequests,
                    fn($x)
                        => ($x['status'] ?? '')
                        === 'pending'
                )
            );

        $deleteCount =
            count(
                array_filter(
                    $deleteRequests,
                    fn($x)
                        => ($x['status'] ?? '')
                        === 'pending'
                )
            );

        $totalPending =
            $quarterCount +
            $targetCount +
            $deleteCount;

        /*
        |--------------------------------------------------------------------------
        | COMBINE
        |--------------------------------------------------------------------------
        */

        $approvals = array_merge(
            $quarterApprovals,
            $targetRequests,
            $deleteRequests
        );

        /*
        |--------------------------------------------------------------------------
        | SORT
        |--------------------------------------------------------------------------
        */

        usort($approvals, function($a, $b){

            return strtotime(
                $b['created_at'] ?? now()
            ) <=> strtotime(
                $a['created_at'] ?? now()
            );
        });

        return view(
            'kpi.approval',
            [
                'approvals' => $approvals,
                'quarterCount' => $quarterCount,
                'targetCount' => $targetCount,
                'deleteCount' => $deleteCount,
                'totalPending' => $totalPending,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ENRICH APPROVALS
    |--------------------------------------------------------------------------
    */

    protected function enrichApprovals(
        array $items,
        string $type,
        SupabaseService $supabase
    ){

        if(empty($items)){
            return [];
        }

        $kpiIds = collect($items)
            ->pluck('kpi_id')
            ->filter()
            ->unique()
            ->values();

        if($kpiIds->isEmpty()){
            return $items;
        }

        $kpiIds = $kpiIds->implode(',');

        $kpis = $supabase->get(

            'kpis',

            [

                'id' => 'in.(' . $kpiIds . ')'

            ]

        ) ?? [];

        $kpiMap = collect($kpis)
            ->keyBy('id');

        foreach($items as &$item){

            $kpi = $kpiMap[
                $item['kpi_id']
            ] ?? [];

            $item['kpi_title']
                = $kpi['kpi_title']
                ?? 'Untitled KPI';

            $item['category']
                = $kpi['category']
                ?? '-';

            $item['sub_category']
                = $kpi['sub_category']
                ?? '-';

            $item['unit']
                = $kpi['unit']
                ?? '';

            $item['type'] = $type;
                if(
                    empty($item['priority'])
                ){
                    $item['priority'] = match($type){
                        'delete_request' => 'critical',
                        'target_change' => 'high',
                        default => 'normal'
                    };
                }
        }

        return $items;
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        string $id
    )
    {
        try {

            $approval =
                $this->findApprovalById(
                    $id
                );

            if(!$approval){

                return response()->json([
                    'success' => false,
                    'message' => 'Approval not found'
                ],404);
            }

            if(
                ($approval['status'] ?? '')
                !== 'pending'
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Already processed'
                ],422);
            }

            $type =
                $approval['type']
                ?? null;

            if(
                $type === 'delete_request'
            ){

                return $this
                    ->approvalActionService
                    ->approveDelete(

                        $approval,

                        session(
                            'employee_uuid'
                        ),

                        session(
                            'short_name'
                        )

                    );
            }

            if(
                $type === 'target_change'
            ){

                return $this
                    ->approvalActionService
                    ->approveTarget(

                        $approval,

                        session(
                            'employee_uuid'
                        ),

                        session(
                            'short_name'
                        )

                    );
            }

            if(
                $type === 'quarter_update'
            ){

                return $this
                    ->approvalActionService
                    ->approveQuarter(

                        $approval,

                        session(
                            'employee_uuid'
                        ),

                        session(
                            'short_name'
                        )

                    );
            }

            return response()->json([
                'success' => false,
                'message' => 'Unknown approval type'
            ],422);

        } catch (\Throwable $e) {

            dd([
                'ERROR' => $e->getMessage(),
                'LINE'  => $e->getLine(),
                'FILE'  => $e->getFile(),
            ]);

        }
    }

    protected function findApprovalById(
        string $id
    ): ?array
    {
        $supabase = app(
            \App\Services\SupabaseService::class
        );

        /*
        |--------------------------------------------------------------------------
        | QUARTER APPROVAL
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(
            'kpi_update_approvals',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($approval){

            $approval['type']
                = 'quarter_update';

            return $approval;
        }

        /*
        |--------------------------------------------------------------------------
        | TARGET CHANGE
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(
            'kpi_target_change_requests',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($approval){

            $approval['type']
                = 'target_change';

            return $approval;
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE REQUEST
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(
            'kpi_delete_requests',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($approval){

            $approval['type']
                = 'delete_request';

            return $approval;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    public function reject(
        $id,
        Request $request,
        SupabaseService $supabase
    ){

        $reason = $request->reason ?? 'Rejected';

        /*
        |--------------------------------------------------------------------------
        | QUARTER APPROVAL
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(
            'kpi_update_approvals',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($approval){

            if(
                ($approval['status'] ?? '')
                !== 'pending'
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Already processed'
                ],422);
            }

            $this->approvalActionService->reject(
                'kpi_update_approvals',
                $id,
                session('employee_uuid'),
                session('short_name'),
                $reason
            );

            $this->approvalActionService->history(
                $approval['kpi_id'],
                'quarter_rejected',
                $approval['requested_actual'],
                'REJECTED',
                session('employee_uuid'),
                session('short_name')
            );

            return response()->json([
                'success' => true
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | TARGET CHANGE
        |--------------------------------------------------------------------------
        */

        $targetRequest = $supabase->first(
            'kpi_target_change_requests',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($targetRequest){

            if(
                ($targetRequest['status'] ?? '')
                !== 'pending'
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Already processed'
                ],422);
            }

            $this->approvalActionService->history(
                $targetRequest['kpi_id'],
                'target_rejected',
                $targetRequest['old_value'],
                $targetRequest['requested_value'],
                session('employee_uuid'),
                session('short_name')
            );

            $this->approvalActionService->reject(
                'kpi_target_change_requests',
                $id,
                session('employee_uuid'),
                session('short_name'),
                $reason
            );

            return response()->json([
                'success' => true
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE REQUEST
        |--------------------------------------------------------------------------
        */

        $deleteRequest = $supabase->first(
            'kpi_delete_requests',
            [
                'id' => 'eq.' . $id,
                'approver_id' => 'eq.' . session('employee_uuid'),
            ]
        );

        if($deleteRequest){

            if(
                ($deleteRequest['status'] ?? '')
                !== 'pending'
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Already processed'
                ],422);
            }

            $this->approvalActionService->reject(
                'kpi_delete_requests',
                $id,
                session('employee_uuid'),
                session('short_name'),
                $reason
            );

            $this->approvalActionService->history(
                $deleteRequest['kpi_id'],
                'delete_rejected',
                'PENDING_DELETE',
                'REJECTED',
                session('employee_uuid'),
                session('short_name')
            );

            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Approval not found.'
        ],404);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECTED HISTORY
    |--------------------------------------------------------------------------
    */

    public function rejected(
        SupabaseService $supabase
    ){

        $userId = session('employee_uuid');

        $records = array_merge(

            $supabase->get(
                'kpi_update_approvals',
                [
                    'approver_id' =>
                        'eq.' . $userId,

                    'status' =>
                        'eq.rejected',

                    'order' =>
                        'rejected_at.desc',
                ]
            ) ?? [],

            $supabase->get(
                'kpi_target_change_requests',
                [
                    'approver_id' =>
                        'eq.' . $userId,

                    'status' =>
                        'eq.rejected',

                    'order' =>
                        'rejected_at.desc',
                ]
            ) ?? [],

            $supabase->get(
                'kpi_delete_requests',
                [
                    'approver_id' =>
                        'eq.' . $userId,

                    'status' =>
                        'eq.rejected',

                    'order' =>
                        'rejected_at.desc',
                ]
            ) ?? []

        );

        usort($records,function($a,$b){

            return strtotime(
                $b['rejected_at'] ?? now()
            ) <=> strtotime(
                $a['rejected_at'] ?? now()
            );

        });

        return view(
            'approval.rejected',
            compact('records')
        );
    }
}
