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
}
