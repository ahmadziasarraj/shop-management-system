<?php

namespace App\Actions\Resources;

use App\Models\UnitMeasurement;
use Illuminate\Support\Facades\DB;

final class UnitMeasurementActions
{

    // public function create(array $data): UnitMeasurement
    // {

    // }

    public function insertDefaultUnits(): void
    {

        DB::transaction(function () {

            $units = [
                [
                    'name' => 'Tire',
                    'symbol' => 'tire',
                ],
                [
                    'name' => 'Piece',
                    'symbol' => 'pcs',
                ],
                [
                    'name' => 'Pair',
                    'symbol' => 'pair',
                ],
                [
                    'name' => 'Set',
                    'symbol' => 'set',
                ],
                [
                    'name' => 'Tube',
                    'symbol' => 'tube',
                ],
                [
                    'name' => 'Wheel',
                    'symbol' => 'wheel',
                ],
                [
                    'name' => 'Rim',
                    'symbol' => 'rim',
                ],
                [
                    'name' => 'Kilogram',
                    'symbol' => 'kg',
                ],
                [
                    'name' => 'Pack',
                    'symbol' => 'pack',
                ],
                [
                    'name' => 'Carton',
                    'symbol' => 'carton',
                ]
            ];

            foreach ($units as $unit) {
                $this->createUnite($unit);
            }
        });
    }

    private function createUnite(array $data ): UnitMeasurement {

        $account = UnitMeasurement::create([

            'name' => $data['name'],
            'symbol' => $data['symbol']
        ]);

        return $account;
    }
}
