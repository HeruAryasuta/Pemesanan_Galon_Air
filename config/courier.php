<?php

$vehicleCapacities = [
    'Tosa' => 10,
    'Sepeda motor' => 2,
    'Truck' => 40,
    'Pick up' => 20,
];

return [
    'depot_code' => env('COURIER_DEPOT_CODE', 'PWD'),
    'vehicle_types' => array_keys($vehicleCapacities),
    'vehicle_capacities' => $vehicleCapacities,
    'capacity_unit' => 'unit barang',
];
