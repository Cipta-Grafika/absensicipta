<?php

namespace Tests\Feature\Payroll;

use App\Exports\BcaMatPayrollExport;
use App\Models\PaymentMethod;
use App\Models\Payroll;
use App\Models\User;
use Tests\TestCase;

class BcaMatExportTest extends TestCase
{
    public function test_bca_mat_export_generates_valid_spreadsheet_format()
    {
        $export = new BcaMatPayrollExport('2026-07', '2026-08-30', 'BCA', 'Gaji Jul 2026');
        $spreadsheet = $export->generateSpreadsheet();

        $sheet = $spreadsheet->getSheetByName('Data');
        $this->assertNotNull($sheet);

        // Header tests
        $this->assertEquals('No', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Transaction ID', $sheet->getCell('B1')->getValue());
        $this->assertStringContainsString('Transfer Type', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Beneficiary ID', $sheet->getCell('D1')->getValue());
        $this->assertEquals('Credited Account', $sheet->getCell('E1')->getValue());
        $this->assertEquals('Receiver Name', $sheet->getCell('F1')->getValue());
        $this->assertEquals('Amount', $sheet->getCell('G1')->getValue());
        $this->assertEquals('NIP', $sheet->getCell('H1')->getValue());
        $this->assertEquals('Remark', $sheet->getCell('I1')->getValue());
        $this->assertEquals('Beneficiary email address', $sheet->getCell('J1')->getValue());
        $this->assertEquals('Receiver Swift Code', $sheet->getCell('K1')->getValue());
        $this->assertEquals('Receiver Cust Type', $sheet->getCell('L1')->getValue());
        $this->assertEquals('Receiver Cust Residence', $sheet->getCell('M1')->getValue());

        // Check Legend sheet exists
        $legend = $spreadsheet->getSheetByName('Legend');
        $this->assertNotNull($legend);

        // Check data rows if any payrolls exist
        $payrolls = $export->getPayrolls();
        if ($payrolls->isNotEmpty()) {
            $row2TxId = $sheet->getCell('B2')->getValue();
            $this->assertMatchesRegularExpression('/^\d{8}-\d{3}$/', $row2TxId);
            $this->assertEquals('30082026-001', $row2TxId);

            $this->assertEquals('BCA', $sheet->getCell('C2')->getValue());
            $this->assertIsNumeric($sheet->getCell('G2')->getValue());

            // Check number format for amount
            $format = $sheet->getStyle('G2')->getNumberFormat()->getFormatCode();
            $this->assertStringContainsString('#,##0.00', $format);

            // Check NIP format is text @
            $nipFormat = $sheet->getStyle('H2')->getNumberFormat()->getFormatCode();
            $this->assertEquals('@', $nipFormat);

            // Check Cust Type and Residence are 1
            $this->assertEquals(1, $sheet->getCell('L2')->getValue());
            $this->assertEquals(1, $sheet->getCell('M2')->getValue());
        }
    }

    public function test_payroll_user_can_access_export_bank_page()
    {
        $user = User::where('group', 'payroll')->first();
        if (!$user) {
            $user = User::factory()->create(['group' => 'payroll', 'status' => 'active']);
        }
        $this->actingAs($user);

        $response = $this->get(route('payroll.export-bank'));
        $response->assertStatus(200);
        $response->assertSee('Export Transfer Bank (BCA MAT)');
    }

    public function test_superadmin_can_access_export_bank_page()
    {
        $user = User::where('group', 'superadmin')->first();
        if (!$user) {
            $user = User::factory()->create(['group' => 'superadmin', 'status' => 'active']);
        }
        $this->actingAs($user);

        $response = $this->get(route('payroll.export-bank'));
        $response->assertStatus(200);
        $response->assertSee('Export Transfer Bank (BCA MAT)');
    }

    public function test_regular_user_cannot_access_export_bank_page()
    {
        $employee = User::where('group', 'user')->first();
        if (!$employee) {
            $employee = User::factory()->create(['group' => 'user', 'status' => 'active']);
        }
        $this->actingAs($employee);

        $response = $this->get(route('payroll.export-bank'));
        $response->assertStatus(403);
    }

    public function test_bca_mat_export_orders_by_division_then_oldest_employee()
    {
        $divOnline = \App\Models\Division::firstOrCreate(['name' => 'Online']);
        $divGraha = \App\Models\Division::firstOrCreate(['name' => 'Graha']);

        // Create 2 employees in Online (newer created first, older created second)
        $empOnlineNewer = User::factory()->create([
            'name' => 'Online Newer Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $divOnline->id,
            'created_at' => now()->subDays(10),
        ]);
        $empOnlineOlder = User::factory()->create([
            'name' => 'Online Older Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $divOnline->id,
            'created_at' => now()->subDays(50),
        ]);

        // Create 2 employees in Graha (newer created first, older created second)
        $empGrahaNewer = User::factory()->create([
            'name' => 'Graha Newer Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $divGraha->id,
            'created_at' => now()->subDays(20),
        ]);
        $empGrahaOlder = User::factory()->create([
            'name' => 'Graha Older Employee',
            'group' => 'user',
            'status' => 'active',
            'division_id' => $divGraha->id,
            'created_at' => now()->subDays(100),
        ]);

        // Create payrolls for month 2026-09
        $prGrahaOlder = Payroll::create([
            'employee_id' => $empGrahaOlder->id,
            'period_month' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'net_salary' => 2000000,
            'status' => 'draft',
        ]);
        $prGrahaNewer = Payroll::create([
            'employee_id' => $empGrahaNewer->id,
            'period_month' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'net_salary' => 2100000,
            'status' => 'draft',
        ]);
        $prOnlineOlder = Payroll::create([
            'employee_id' => $empOnlineOlder->id,
            'period_month' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'net_salary' => 2200000,
            'status' => 'draft',
        ]);
        $prOnlineNewer = Payroll::create([
            'employee_id' => $empOnlineNewer->id,
            'period_month' => '2026-09',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'net_salary' => 2300000,
            'status' => 'draft',
        ]);

        $export = new BcaMatPayrollExport('2026-09', '2026-09-30', 'BCA', 'Gaji Sep 2026');
        $payrolls = $export->getPayrolls();

        // Expected order:
        // 1. Graha (G comes before O) -> Graha Older first, then Graha Newer
        // 2. Online -> Online Older first, then Online Newer
        $employeeNamesInOrder = $payrolls->pluck('employee.name')->toArray();

        $this->assertEquals([
            'Graha Older Employee',
            'Graha Newer Employee',
            'Online Older Employee',
            'Online Newer Employee',
        ], $employeeNamesInOrder);
    }
}
