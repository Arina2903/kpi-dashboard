<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class ApprovalController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(
        SupabaseService $supabase
    ){

        $userId =
            session('employee_uuid');

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

                'approved_by' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'status' =>
                    'eq.pending',

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

                'approved_by' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'status' =>
                    'eq.pending',

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

                'approved_by' =>
                    'eq.' . $userId,

                'requested_by' =>
                    'neq.' . $userId,

                'status' =>
                    'eq.pending',

                'order' =>
                    'created_at.desc',

            ]

        ) ?? [];

        /*
        |--------------------------------------------------------------------------
        | ENRICH
        |--------------------------------------------------------------------------
        */

        $quarterApprovals =
            $this->enrichApprovals(
                $quarterApprovals,
                'quarter_update',
                $supabase
            );

        $targetRequests =
            $this->enrichApprovals(
                $targetRequests,
                'target_change',
                $supabase
            );

        $deleteRequests =
            $this->enrichApprovals(
                $deleteRequests,
                'delete_request',
                $supabase
            );

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

                'approvals' =>
                    $approvals

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

        if(!is_array($items)){

            return [];
        }

        foreach($items as &$item){

            $kpi = $supabase->first(

                'kpis',

                [
                    'id' =>
                        'eq.' . $item['kpi_id']
                ]

            ) ?? [];

            $item['kpi_title'] =
                $kpi['kpi_title']
                ?? 'Untitled KPI';

            $item['category'] =
                $kpi['category']
                ?? '-';

            $item['sub_category'] =
                $kpi['sub_category']
                ?? '-';

            $item['unit'] =
                $kpi['unit']
                ?? '';

            $item['type'] =
                $type;
        }

        return $items;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE APPROVER
    |--------------------------------------------------------------------------
    */

    protected function resolveApproverId(
        array $employee,
        SupabaseService $supabase
    ){

        /*
        |--------------------------------------------------------------------------
        | TOP MANAGEMENT
        |--------------------------------------------------------------------------
        */

        if(

            in_array(

                $employee['role'],

                [

                    'SLT',
                    'Admin',
                    'CCO',
                    'CCMO'

                ]

            )

        ){

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | REPORTING_TO PRIORITY
        |--------------------------------------------------------------------------
        */

        if(!empty($employee['reports_to'])){

            $reportingTo = $supabase->first(

                'employees',

                [

                    'employee_id' =>
                        'eq.' . $employee['reports_to']

                ]

            );

            if(

                $reportingTo
                &&
                $reportingTo['id']
                !== $employee['id']

            ){

                return $reportingTo['id'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EXECUTIVE
        |--------------------------------------------------------------------------
        */

        if($employee['role'] === 'Executive'){

            if(!empty($employee['manager_code'])){

                $manager = $supabase->first(

                    'employees',

                    [

                        'employee_id' =>
                            'eq.' . $employee['manager_code']

                    ]

                );

                if(

                    $manager
                    &&
                    $manager['id']
                    !== $employee['id']

                ){

                    return $manager['id'];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | FALLBACK VP
            |--------------------------------------------------------------------------
            */

            if(!empty($employee['vp_code'])){

                $vp = $supabase->first(

                    'employees',

                    [

                        'employee_id' =>
                            'eq.' . $employee['vp_code']

                    ]

                );

                if(

                    $vp
                    &&
                    $vp['id']
                    !== $employee['id']

                ){

                    return $vp['id'];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        */

        if($employee['role'] === 'Manager'){

            if(!empty($employee['vp_code'])){

                $vp = $supabase->first(

                    'employees',

                    [

                        'employee_id' =>
                            'eq.' . $employee['vp_code']

                    ]

                );

                if(

                    $vp
                    &&
                    $vp['id']
                    !== $employee['id']

                ){

                    return $vp['id'];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VP
        |--------------------------------------------------------------------------
        */

        if($employee['role'] === 'VP'){

            $slt = $supabase->first(

                'employees',

                [

                    'role' =>
                        'eq.SLT'

                ]

            );

            if(

                $slt
                &&
                $slt['id']
                !== $employee['id']

            ){

                return $slt['id'];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    public function approve(
        $id,
        Request $request,
        SupabaseService $supabase
    ){

        /*
        |--------------------------------------------------------------------------
        | QUARTER UPDATE
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(

            'kpi_update_approvals',

            [

                'id' =>
                    'eq.' . $id

            ]

        );

        if($approval){

            $supabase->patch(

                'kpi_update_approvals',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'approved',

                    'approved_by' =>
                        session('employee_uuid'),

                    'approved_by_name' =>
                        session('short_name'),

                    'approved_at' =>
                        now(),

                    'is_viewed' =>
                        true,

                    'viewed_at' =>
                        now(),

                ]

            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE KPI QUARTER
            |--------------------------------------------------------------------------
            */

            $supabase->patch(

                'kpi_quarters',

                [

                    'kpi_id' =>
                        'eq.' . $approval['kpi_id'],

                    'quarter' =>
                        'eq.' . $approval['quarter']

                ],

                [

                    'quarter_actual' =>
                        $approval['requested_actual'],

                    'remark' =>
                        $approval['request_remark'],

                    'updated_at' =>
                        now()

                ]

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

                'id' =>
                    'eq.' . $id

            ]

        );

        if($targetRequest){

            $supabase->patch(

                'kpi_target_change_requests',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'approved',

                    'approved_by' =>
                        session('employee_uuid'),

                    'approved_at' =>
                        now()

                ]

            );

            $updateData = [

                'updated_at' =>
                    now()

            ];

            if(

                $targetRequest['field_name']
                === 'base_target'

            ){

                $updateData['base_target'] =
                    $targetRequest['requested_value'];
            }

            if(

                $targetRequest['field_name']
                === 'stretch_target'

            ){

                $updateData['stretch_target'] =
                    $targetRequest['requested_value'];
            }

            $supabase->patch(

                'kpis',

                [

                    'id' =>
                        'eq.' . $targetRequest['kpi_id']

                ],

                $updateData

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

                'id' =>
                    'eq.' . $id

            ]

        );

        if($deleteRequest){

            $supabase->patch(

                'kpi_delete_requests',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'approved',

                    'approved_by' =>
                        session('employee_uuid'),

                    'approved_at' =>
                        now()

                ]

            );

            /*
            |--------------------------------------------------------------------------
            | DELETE CHILD TABLES
            |--------------------------------------------------------------------------
            */

            $tables = [

                'kpi_quarters',
                'kpi_histories',
                'kpi_assignments',
                'kpi_update_approvals',
                'kpi_target_change_requests',

            ];

            foreach($tables as $table){

                $supabase->delete(

                    $table,

                    [

                        'kpi_id' =>
                            'eq.' . $deleteRequest['kpi_id']

                    ]

                );
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE KPI
            |--------------------------------------------------------------------------
            */

            $supabase->delete(

                'kpis',

                [

                    'id' =>
                        'eq.' . $deleteRequest['kpi_id']

                ]

            );

            return response()->json([

                'success' => true

            ]);
        }

        return response()->json([

            'success' => false,

            'message' =>
                'Approval not found.'

        ], 404);
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

        $reason =
            $request->reason
            ?? 'Rejected';

        /*
        |--------------------------------------------------------------------------
        | QUARTER UPDATE
        |--------------------------------------------------------------------------
        */

        $approval = $supabase->first(

            'kpi_update_approvals',

            [

                'id' =>
                    'eq.' . $id

            ]

        );

        if($approval){

            $supabase->patch(

                'kpi_update_approvals',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'rejected',

                    'rejected_by' =>
                        session('employee_uuid'),

                    'rejected_by_name' =>
                        session('short_name'),

                    'rejected_at' =>
                        now(),

                    'approver_remark' =>
                        $reason,

                    'is_viewed' =>
                        true,

                    'viewed_at' =>
                        now(),

                ]

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

                'id' =>
                    'eq.' . $id

            ]

        );

        if($targetRequest){

            $supabase->patch(

                'kpi_target_change_requests',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'rejected',

                    'rejected_by' =>
                        session('employee_uuid'),

                    'rejected_at' =>
                        now(),

                    'approver_remark' =>
                        $reason,

                ]

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

                'id' =>
                    'eq.' . $id

            ]

        );

        if($deleteRequest){

            $supabase->patch(

                'kpi_delete_requests',

                [

                    'id' =>
                        'eq.' . $id

                ],

                [

                    'status' =>
                        'rejected',

                    'rejected_by' =>
                        session('employee_uuid'),

                    'rejected_at' =>
                        now(),

                    'approver_remark' =>
                        $reason,

                ]

            );

            return response()->json([

                'success' => true

            ]);
        }

        return response()->json([

            'success' => false,

            'message' =>
                'Approval not found.'

        ], 404);
    }
}
