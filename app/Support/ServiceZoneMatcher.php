<?php

namespace App\Support;

use App\Models\ServiceZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ServiceZoneMatcher
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function matchGeocodedAddress(array $address): ?ServiceZone
    {
        return $this->matchAreaNames([
            $address['city_district'] ?? null,
            $address['suburb'] ?? null,
            $address['borough'] ?? null,
            $address['quarter'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            $address['municipality'] ?? null,
            $address['county'] ?? null,
        ]);
    }

    public function matchAddressText(string $address): ?ServiceZone
    {
        $zones = ServiceZone::query()->orderBy('id')->get();
        $matches = collect(explode(',', $address))
            ->map(function (string $area): string {
                $area = Str::lower(trim($area));
                $area = preg_replace('/^(?:kecamatan|kec\.?)\s+/u', '', $area) ?? $area;

                return trim($area, " \t\n\r\0\x0B.");
            })
            ->filter()
            ->map(fn (string $area): ?ServiceZone => $this->findZone($area, $zones))
            ->filter()
            ->unique('id');

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @param  iterable<mixed>  $areas
     */
    private function matchAreaNames(iterable $areas): ?ServiceZone
    {
        $zones = ServiceZone::query()->orderBy('id')->get();

        foreach ($areas as $area) {
            if (! is_string($area) || trim($area) === '') {
                continue;
            }

            $zone = $this->findZone(Str::lower(trim($area)), $zones);

            if ($zone) {
                return $zone;
            }
        }

        return null;
    }

    private function findZone(string $area, Collection $zones): ?ServiceZone
    {
        $matches = $zones->filter(function (ServiceZone $zone) use ($area): bool {
            $configuredAreas = preg_split('/\s*[,\/]\s*/u', $zone->area_description) ?: [];

            foreach ($configuredAreas as $configuredArea) {
                if (Str::lower(trim($configuredArea)) === $area) {
                    return true;
                }
            }

            return false;
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
