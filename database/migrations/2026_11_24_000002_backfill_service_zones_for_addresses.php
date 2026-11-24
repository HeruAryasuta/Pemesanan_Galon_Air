<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $zones = DB::table('service_zones')
            ->select(['id', 'area_description'])
            ->orderBy('id')
            ->get();
        $addresses = DB::table('addresses')
            ->whereNull('service_zone_id')
            ->select(['id', 'full_address'])
            ->get();

        foreach ($addresses as $address) {
            $segments = array_map(function (string $segment): string {
                $segment = Str::lower(trim($segment));
                $segment = preg_replace('/^(?:kecamatan|kec\.?)\s+/u', '', $segment) ?? $segment;

                return trim($segment, " \t\n\r\0\x0B.");
            }, explode(',', $address->full_address));
            $matchingZoneIds = [];

            foreach ($zones as $zone) {
                $areas = preg_split('/\s*[,\/]\s*/u', $zone->area_description) ?: [];

                foreach ($areas as $area) {
                    if (in_array(Str::lower(trim($area)), $segments, true)) {
                        $matchingZoneIds[] = $zone->id;
                        break;
                    }
                }
            }

            $matchingZoneIds = array_unique($matchingZoneIds);

            if (count($matchingZoneIds) === 1) {
                DB::table('addresses')
                    ->where('id', $address->id)
                    ->whereNull('service_zone_id')
                    ->update(['service_zone_id' => reset($matchingZoneIds)]);
            }
        }
    }

    public function down(): void {}
};
