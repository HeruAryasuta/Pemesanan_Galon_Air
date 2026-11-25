<?php

return [
    'minimum_fee' => (int) env('DELIVERY_MINIMUM_FEE', 5000),
    'per_km_rates' => [
        'Sepeda motor' => (int) env('DELIVERY_RATE_MOTOR_PER_KM', 500),
        'Tosa' => (int) env('DELIVERY_RATE_TOSA_PER_KM', 1000),
        'Pick up' => (int) env('DELIVERY_RATE_PICKUP_PER_KM', 1000),
        'Truck' => (int) env('DELIVERY_RATE_TRUCK_PER_KM', 2000),
    ],
];
