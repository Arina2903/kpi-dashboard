<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected AiService $ai;

    public function __construct(AiService $ai)
    {
        $this->ai = $ai;
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGEST KPI DESCRIPTION
    |--------------------------------------------------------------------------
    */

    public function suggestDescription(Request $request)
    {
        $request->validate([
            'kpi_title' => 'required|string|max:255',
        ]);

        $employee   = session('employee', []);
        $department = $employee['department'] ?? '';
        $role       = $employee['role']       ?? '';

        try {

            $description = $this->ai->suggestKpiDescription(
                $request->kpi_title,
                $department,
                $role
            );

            return response()->json([
                'success'     => true,
                'description' => $description,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'AI suggestion failed. Please try again.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT BOT
    |--------------------------------------------------------------------------
    */

    public function chat(Request $request)
    {
        $request->validate([
            'messages'         => 'required|array|min:1|max:20',
            'messages.*.role'  => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:1000',
        ]);

        $employee = session('employee', []);

        try {

            $reply = $this->ai->chat($request->messages, $employee);

            return response()->json([
                'success' => true,
                'reply'   => $reply,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Bot is unavailable. Please try again.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SCORE KPI DESCRIPTION
    |--------------------------------------------------------------------------
    */

    public function scoreDescription(Request $request)
    {
        $request->validate([
            'kpi_title'       => 'required|string|max:255',
            'kpi_description' => 'required|string',
            'base_target'     => 'nullable|numeric',
            'stretch_target'  => 'nullable|numeric',
            'unit'            => 'nullable|string|max:20',
            'weightage'       => 'nullable|numeric',
            'category'        => 'nullable|string|max:100',
            'sub_category'    => 'nullable|string|max:100',
            'quarter_targets' => 'nullable|array',
        ]);

        try {

            $result = $this->ai->scoreKpiDescription(
                $request->kpi_title,
                $request->kpi_description,
                $request->base_target,
                $request->stretch_target,
                $request->unit,
                $request->weightage,
                $request->category,
                $request->sub_category,
                $request->quarter_targets,
            );

            return response()->json([
                'success'  => true,
                'score'    => $result['score']    ?? 0,
                'feedback' => $result['feedback'] ?? '',
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Scoring failed. Please try again.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGEST QUARTERLY TARGETS
    |--------------------------------------------------------------------------
    */

    public function suggestTargets(Request $request)
    {
        $request->validate([
            'kpi_title'     => 'required|string|max:255',
            'annual_target' => 'required|numeric',
            'unit'          => 'nullable|string|max:50',
        ]);

        try {

            $targets = $this->ai->suggestQuarterlyTargets(
                $request->kpi_title,
                (float) $request->annual_target,
                $request->unit ?? ''
            );

            return response()->json([
                'success' => true,
                'targets' => $targets,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'AI suggestion failed. Please try again.',
            ], 500);
        }
    }
}
