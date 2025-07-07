<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\PdfReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'owner']);
        Role::create(['name' => 'kasir']);
        Role::create(['name' => 'karyawan']);
    }

    /** @test */
    public function kasir_can_create_transactions()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $response = $this->actingAs($kasir)->get(route('penjualan.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function owner_cannot_create_transactions()
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $response = $this->actingAs($owner)->get(route('penjualan.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function kasir_can_manage_stock()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $barang = Barang::factory()->create();

        $response = $this->actingAs($kasir)->patch(route('barang.update-stock', $barang), [
            'adjustment' => 10,
            'reason' => 'Stock adjustment test'
        ]);
        
        $response->assertStatus(302); // Redirect after success
    }

    /** @test */
    public function admin_cannot_manage_stock()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $barang = Barang::factory()->create();

        $response = $this->actingAs($admin)->patch(route('barang.update-stock', $barang), [
            'adjustment' => 10,
            'reason' => 'Stock adjustment test'
        ]);
        
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_approve_reports()
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $report = PdfReport::factory()->create([
            'generated_by' => $kasir->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($owner)->post(route('reports.approval.process', $report), [
            'action' => 'approve',
            'notes' => 'Approved by owner'
        ]);
        
        $response->assertStatus(302);
        $this->assertEquals('approved', $report->fresh()->status);
    }

    /** @test */
    public function kasir_cannot_approve_reports()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $report = PdfReport::factory()->create([
            'generated_by' => $kasir->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($kasir)->post(route('reports.approval.process', $report), [
            'action' => 'approve',
            'notes' => 'Trying to approve own report'
        ]);
        
        $response->assertStatus(403);
    }

    /** @test */
    public function transaction_status_synchronization_works()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $penjualan = Penjualan::factory()->create([
            'status' => 'pending'
        ]);

        // Confirm payment
        $response = $this->actingAs($kasir)->post(route('penjualan.confirm-payment', $penjualan));
        $response->assertStatus(302);
        
        $this->assertEquals('dibayar', $penjualan->fresh()->status);
        $this->assertNotNull($penjualan->fresh()->payment_confirmed_at);
        $this->assertEquals($kasir->id, $penjualan->fresh()->payment_confirmed_by);
    }

    /** @test */
    public function ui_permissions_are_shared_correctly()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $response = $this->actingAs($kasir)->get(route('penjualan.index'));
        $response->assertStatus(200);
        
        // Check if UI permissions are in the response
        $response->assertInertia(fn ($page) => 
            $page->has('uiPermissions')
                ->where('uiPermissions.canCreateTransactions', true)
                ->where('uiPermissions.canManageStock', true)
                ->where('uiPermissions.isKasir', true)
        );
    }

    /** @test */
    public function owner_ui_permissions_are_correct()
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $response = $this->actingAs($owner)->get(route('penjualan.index'));
        $response->assertStatus(200);
        
        // Check if UI permissions are correct for owner
        $response->assertInertia(fn ($page) => 
            $page->has('uiPermissions')
                ->where('uiPermissions.canCreateTransactions', false)
                ->where('uiPermissions.canManageStock', false)
                ->where('uiPermissions.canApproveReports', true)
                ->where('uiPermissions.isOwner', true)
        );
    }

    /** @test */
    public function karyawan_has_limited_access()
    {
        $karyawan = User::factory()->create();
        $karyawan->assignRole('karyawan');

        // Can access barang index
        $response = $this->actingAs($karyawan)->get(route('barang.index'));
        $response->assertStatus(200);

        // Cannot access penjualan create
        $response = $this->actingAs($karyawan)->get(route('penjualan.create'));
        $response->assertStatus(403);

        // Cannot manage stock
        $barang = Barang::factory()->create();
        $response = $this->actingAs($karyawan)->patch(route('barang.update-stock', $barang), [
            'adjustment' => 10,
            'reason' => 'Test'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function report_approval_workflow_complete()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        // 1. Kasir creates report
        $response = $this->actingAs($kasir)->post(route('reports.store'), [
            'title' => 'Test Sales Report',
            'type' => 'sales',
            'description' => 'Monthly sales report',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ]);
        
        $response->assertStatus(302);
        
        $report = PdfReport::where('title', 'Test Sales Report')->first();
        $this->assertEquals('pending', $report->status);
        $this->assertEquals($kasir->id, $report->generated_by);

        // 2. Owner approves report
        $response = $this->actingAs($owner)->post(route('reports.approval.process', $report), [
            'action' => 'approve',
            'notes' => 'Report looks good'
        ]);
        
        $response->assertStatus(302);
        
        $report->refresh();
        $this->assertEquals('approved', $report->status);
        $this->assertEquals($owner->id, $report->approved_by);
        $this->assertNotNull($report->approved_at);
        $this->assertEquals('Report looks good', $report->approval_notes);

        // 3. Approved report can generate PDF
        $response = $this->actingAs($kasir)->get(route('reports.pdf', $report));
        $response->assertStatus(200);
    }

    /** @test */
    public function rejected_report_cannot_generate_pdf()
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $report = PdfReport::factory()->create([
            'generated_by' => $kasir->id,
            'status' => 'pending'
        ]);

        // Owner rejects report
        $response = $this->actingAs($owner)->post(route('reports.approval.process', $report), [
            'action' => 'reject',
            'notes' => 'Data incomplete'
        ]);
        
        $response->assertStatus(302);
        
        $report->refresh();
        $this->assertEquals('rejected', $report->status);

        // Cannot generate PDF for rejected report
        $response = $this->actingAs($kasir)->get(route('reports.pdf', $report));
        $response->assertStatus(403);
    }
}
