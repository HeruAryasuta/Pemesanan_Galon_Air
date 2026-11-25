<?php

namespace App\Services;

use App\Services\Routing\OsrmTspService;
use App\Support\CourierCapacity;
use RuntimeException;

class DeliveryFeeCalculator
{
    public function __construct(
        private readonly OsrmTspService $osrmTspService,
        private readonly CourierCapacity $courierCapacity,
    ) {}

    /**
     * @return array{
     *     delivery_fee: int,
     *     distance_meters: int,
     *     billed_kilometers: int,
     *     vehicle_type: string,
     *     rate_per_km: int,
     *     minimum_fee: int
     * }
     */
    public function quote(int $load, float $latitude, float $longitude): array
    {
        $vehicleType = $this->courierCapacity->smallestVehicleTypeForLoad($load);
        if ($vehicleType === null) {
            throw new RuntimeException('Jumlah barang melebihi kapasitas kendaraan terbesar. Kurangi jumlah barang atau hubungi admin.');
        }

        $distanceMeters = $this->osrmTspService->roundTripDistanceMeters($latitude, $longitude);
        $billedKilometers = (int) ceil($distanceMeters / 1000);
        $ratePerKilometer = (int) config('delivery.per_km_rates.'.$vehicleType, 0);
        $deliveryFee = max(
            (int) config('delivery.minimum_fee', 5000),
            $billedKilometers * $ratePerKilometer,
        );

        return [
            'delivery_fee' => $deliveryFee,
            'distance_meters' => $distanceMeters,
            'billed_kilometers' => $billedKilometers,
            'vehicle_type' => $vehicleType,
            'rate_per_km' => $ratePerKilometer,
            'minimum_fee' => (int) config('delivery.minimum_fee', 5000),
        ];
    }
}
