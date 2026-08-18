<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\PlatformAuthorization;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Performix Platform Blueprint, Phase 9: the shared KPI template library
 * (spec section 20) — deliberately not scoped to a company, since
 * `kpi_templates`/`kpi_template_items` carry no `company_id` at all (see
 * their migration's docblock). Only the Center curates this library
 * (`kpi_templates_write`/`kpi_template_items_write` require
 * `auth_is_richworks_super_admin()`); any signed-in Platform user may browse
 * it, which is what lets a Company Admin pick a template on their own
 * company's KPI page (`KpiController::applyTemplate()`).
 */
class KpiTemplateController extends Controller
{
    use PlatformAuthorization;

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $templates = $supabase->get('kpi_templates', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        $templateIds = array_column($templates, 'id');

        $items = empty($templateIds)
            ? []
            : $supabase->get('kpi_template_items', [
                'template_id' => 'in.(' . implode(',', $templateIds) . ')',
                'select' => '*',
                'order' => 'created_at.asc',
            ]);

        return Inertia::render('Platform/KpiTemplates/Index', [
            'templates' => $templates,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->insert('kpi_templates', [
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create template: ' . $e->getMessage());
        }

        return back()->with('success', 'Template "' . $request->name . '" created.');
    }

    public function destroy(Request $request, string $template)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->delete('kpi_templates', ['id' => 'eq.' . $template]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete template: ' . $e->getMessage());
        }

        return back()->with('success', 'Template deleted.');
    }

    public function storeItem(Request $request, string $template)
    {
        $this->ensureSuperAdmin($request);

        $request->validate([
            'category_name' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,custom',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->insert('kpi_template_items', [
                'template_id' => $template,
                'category_name' => $request->category_name ?: null,
                'name' => $request->name,
                'description' => $request->description,
                'target' => $request->target,
                'unit' => $request->unit,
                'frequency' => $request->frequency,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not add item: ' . $e->getMessage());
        }

        return back()->with('success', 'Item added to template.');
    }

    public function destroyItem(Request $request, string $template, string $item)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->delete('kpi_template_items', ['id' => 'eq.' . $item, 'template_id' => 'eq.' . $template]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not remove item: ' . $e->getMessage());
        }

        return back()->with('success', 'Item removed.');
    }
}
