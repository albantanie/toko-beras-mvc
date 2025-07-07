<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PayrollConfiguration;

class PayrollConfigurationController extends Controller
{
    /**
     * Display payroll configurations
     */
    public function index(Request $request)
    {
        $configurations = PayrollConfiguration::when($request->category, function($query, $category) {
                return $query->where('config_category', $category);
            })
            ->when($request->type, function($query, $type) {
                return $query->where('config_type', $type);
            })
            ->when($request->applies_to, function($query, $appliesTo) {
                return $query->where('applies_to', $appliesTo);
            })
            ->orderBy('config_category')
            ->orderBy('config_name')
            ->paginate(20);

        return Inertia::render('financial/payroll-configuration', [
            'configurations' => $configurations,
            'filters' => $request->only(['category', 'type', 'applies_to']),
        ]);
    }

    /**
     * Store new configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'config_key' => 'required|string|unique:payroll_configurations,config_key',
            'config_name' => 'required|string|max:255',
            'config_type' => 'required|in:salary,allowance,deduction,rate,percentage,amount',
            'config_category' => 'required|in:basic,overtime,bonus,allowance,tax,insurance,deduction',
            'applies_to' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        PayrollConfiguration::create($validated);

        return redirect()->back()->with('success', 'Konfigurasi payroll berhasil dibuat');
    }

    /**
     * Update configuration
     */
    public function update(Request $request, PayrollConfiguration $configuration)
    {
        $validated = $request->validate([
            'config_name' => 'required|string|max:255',
            'config_type' => 'required|in:salary,allowance,deduction,rate,percentage,amount',
            'config_category' => 'required|in:basic,overtime,bonus,allowance,tax,insurance,deduction',
            'applies_to' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $configuration->update($validated);

        return redirect()->back()->with('success', 'Konfigurasi payroll berhasil diupdate');
    }

    /**
     * Delete configuration
     */
    public function destroy(PayrollConfiguration $configuration)
    {
        $configuration->delete();

        return redirect()->back()->with('success', 'Konfigurasi payroll berhasil dihapus');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(PayrollConfiguration $configuration)
    {
        $configuration->update([
            'is_active' => !$configuration->is_active
        ]);

        $status = $configuration->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Konfigurasi payroll berhasil {$status}");
    }

    /**
     * Get configurations by category for API
     */
    public function getByCategory($category)
    {
        $configurations = PayrollConfiguration::active()
            ->where('config_category', $category)
            ->get();

        return response()->json($configurations);
    }

    /**
     * Get configurations by role for API
     */
    public function getByRole($role)
    {
        $configurations = PayrollConfiguration::active()
            ->appliesTo($role)
            ->get()
            ->groupBy('config_category');

        return response()->json($configurations);
    }
}
