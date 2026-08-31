<?php

namespace Database\Seeders;

use App\Models\TaxMaster;
use Illuminate\Database\Seeder;

class TaxMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brackets = [
            ['min' => 0, 'max' => 5400000, 'rate' => 0.00],
            ['min' => 5400001, 'max' => 5650000, 'rate' => 0.25],
            ['min' => 5650001, 'max' => 5950000, 'rate' => 0.50],
            ['min' => 5950001, 'max' => 6300000, 'rate' => 0.75],
            ['min' => 6300001, 'max' => 6750000, 'rate' => 1.00],
            ['min' => 6750001, 'max' => 7500000, 'rate' => 1.25],
            ['min' => 7500001, 'max' => 8550000, 'rate' => 1.50],
            ['min' => 8550001, 'max' => 9650000, 'rate' => 1.75],
            ['min' => 9650001, 'max' => 10050000, 'rate' => 2.00],
            ['min' => 10050001, 'max' => 10350000, 'rate' => 2.25],
            ['min' => 10350001, 'max' => 10700000, 'rate' => 2.50],
            ['min' => 10700001, 'max' => 11050000, 'rate' => 3.00],
            ['min' => 11050001, 'max' => 11600000, 'rate' => 3.50],
            ['min' => 11600001, 'max' => 12500000, 'rate' => 4.00],
            ['min' => 12500001, 'max' => 13750000, 'rate' => 5.00],
            ['min' => 13750001, 'max' => 15100000, 'rate' => 6.00],
            ['min' => 15100001, 'max' => 16950000, 'rate' => 7.00],
            ['min' => 16950001, 'max' => 19750000, 'rate' => 8.00],
            ['min' => 19750001, 'max' => 24150000, 'rate' => 9.00],
            ['min' => 24150001, 'max' => 26450000, 'rate' => 10.00],
            ['min' => 26450001, 'max' => 28000000, 'rate' => 11.00],
            ['min' => 28000001, 'max' => 30050000, 'rate' => 12.00],
            ['min' => 30050001, 'max' => 32400000, 'rate' => 13.00],
            ['min' => 32400001, 'max' => 35400000, 'rate' => 14.00],
            ['min' => 35400001, 'max' => 39100000, 'rate' => 15.00],
            ['min' => 39100001, 'max' => 43850000, 'rate' => 16.00],
            ['min' => 43850001, 'max' => 47800000, 'rate' => 17.00],
            ['min' => 47800001, 'max' => 51400000, 'rate' => 18.00],
            ['min' => 51400001, 'max' => 56300000, 'rate' => 19.00],
            ['min' => 56300001, 'max' => 62200000, 'rate' => 20.00],
            ['min' => 62200001, 'max' => 68600000, 'rate' => 21.00],
            ['min' => 68600001, 'max' => 77500000, 'rate' => 22.00],
            ['min' => 77500001, 'max' => 89000000, 'rate' => 23.00],
            ['min' => 89000001, 'max' => 103000000, 'rate' => 24.00],
            ['min' => 103000001, 'max' => 125000000, 'rate' => 25.00],
            ['min' => 125000001, 'max' => 157000000, 'rate' => 26.00],
            ['min' => 157000001, 'max' => 206000000, 'rate' => 27.00],
            ['min' => 206000001, 'max' => 337000000, 'rate' => 28.00],
            ['min' => 337000001, 'max' => 454000000, 'rate' => 29.00],
            ['min' => 454000001, 'max' => 550000000, 'rate' => 30.00],
            ['min' => 550000001, 'max' => 695000000, 'rate' => 31.00],
            ['min' => 695000001, 'max' => 910000000, 'rate' => 32.00],
            ['min' => 910000001, 'max' => 1400000000, 'rate' => 33.00],
            ['min' => 1400000001, 'max' => null, 'rate' => 34.00],
        ];

        foreach ($brackets as $index => $b) {
            $seq = sprintf('%02d', $index + 1);
            $code = "TER-A-{$seq}";

            if ($b['max'] === null) {
                $name = "TER A - Di atas Rp " . number_format($b['min'] - 1, 0, ',', '.') . " ({$b['rate']}%)";
            } elseif ($b['min'] <= 0) {
                $name = "TER A - s.d Rp " . number_format($b['max'], 0, ',', '.') . " ({$b['rate']}%)";
            } else {
                $name = "TER A - Rp " . number_format($b['min'], 0, ',', '.') . " s.d Rp " . number_format($b['max'], 0, ',', '.') . " ({$b['rate']}%)";
            }

            TaxMaster::updateOrCreate(
                ['code' => $code],
                [
                    'category' => 'TER A',
                    'name' => $name,
                    'min_gross_income' => $b['min'],
                    'max_gross_income' => $b['max'],
                    'rate_percentage' => $b['rate'],
                    'description' => "Tarif Efektif Bulanan Kategori A Karyawan (PP 58/2023 - {$b['rate']}%)",
                ]
            );
        }
    }
}
