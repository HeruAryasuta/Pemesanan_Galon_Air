<?php

namespace App\Services\Routing;

use App\Models\Delivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsrmTspService
{
    /**
     * @return array{
     *     distance_meters: int,
     *     duration_seconds: int,
     *     geometry: array<int, array{0: float, 1: float}>
     * }
     */
    public function routeFromDepot(Delivery $delivery): array
    {
        $depotLatitude = filter_var(config('routing.depot.latitude'), FILTER_VALIDATE_FLOAT);
        $depotLongitude = filter_var(config('routing.depot.longitude'), FILTER_VALIDATE_FLOAT);
        if ($depotLatitude === false || $depotLongitude === false
            || $depotLatitude < -90 || $depotLatitude > 90
            || $depotLongitude < -180 || $depotLongitude > 180) {
            throw new RouteOptimizationException('Koordinat depo belum dikonfigurasi dengan benar.');
        }

        $address = $delivery->order->address;
        if ($address === null || $address->latitude === null || $address->longitude === null) {
            throw new RouteOptimizationException('Alamat tujuan belum memiliki koordinat.');
        }

        $destinationLatitude = (float) $address->latitude;
        $destinationLongitude = (float) $address->longitude;
        $coordinates = [
            [(float) $depotLongitude, (float) $depotLatitude],
            [$destinationLongitude, $destinationLatitude],
        ];
        $baseUrl = rtrim((string) config('services.osrm.url'), '/');
        if ($baseUrl === '') {
            throw new RouteOptimizationException('URL server OSRM belum dikonfigurasi.');
        }

        $route = $this->request($baseUrl.'/route/v1/driving/'.$this->coordinatePath($coordinates), [
            'overview' => 'full',
            'geometries' => 'geojson',
            'steps' => 'false',
        ]);
        $roadRoute = $route['routes'][0] ?? null;
        if (($route['code'] ?? null) !== 'Ok' || ! is_array($roadRoute)) {
            throw new RouteOptimizationException('OSRM tidak menemukan rute jalan dari depo ke alamat tujuan.');
        }

        $geometry = $roadRoute['geometry']['coordinates'] ?? null;
        if (! is_array($geometry) || count($geometry) < 2
            || ! is_numeric($roadRoute['distance'] ?? null) || ! is_numeric($roadRoute['duration'] ?? null)
            || $roadRoute['distance'] < 0 || $roadRoute['duration'] < 0) {
            throw new RouteOptimizationException('Respons rute dari OSRM tidak lengkap.');
        }

        foreach ($geometry as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2
                || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])
                || $coordinate[0] < -180 || $coordinate[0] > 180
                || $coordinate[1] < -90 || $coordinate[1] > 90) {
                throw new RouteOptimizationException('Geometri rute dari OSRM tidak valid.');
            }
        }

        return [
            'distance_meters' => (int) round($roadRoute['distance']),
            'duration_seconds' => (int) round($roadRoute['duration']),
            'geometry' => array_map(
                fn (array $coordinate): array => [(float) $coordinate[0], (float) $coordinate[1]],
                $geometry,
            ),
        ];
    }

    public function roundTripDistanceMeters(float $destinationLatitude, float $destinationLongitude): int
    {
        $depotLatitude = filter_var(config('routing.depot.latitude'), FILTER_VALIDATE_FLOAT);
        $depotLongitude = filter_var(config('routing.depot.longitude'), FILTER_VALIDATE_FLOAT);
        if ($depotLatitude === false || $depotLongitude === false
            || $depotLatitude < -90 || $depotLatitude > 90
            || $depotLongitude < -180 || $depotLongitude > 180) {
            throw new RouteOptimizationException('Koordinat depo belum dikonfigurasi dengan benar.');
        }

        if ($destinationLatitude < -90 || $destinationLatitude > 90
            || $destinationLongitude < -180 || $destinationLongitude > 180) {
            throw new RouteOptimizationException('Koordinat alamat pengantaran tidak valid.');
        }

        $baseUrl = rtrim((string) config('services.osrm.url'), '/');
        if ($baseUrl === '') {
            throw new RouteOptimizationException('URL server OSRM belum dikonfigurasi.');
        }

        $depot = [(float) $depotLongitude, (float) $depotLatitude];
        $destination = [$destinationLongitude, $destinationLatitude];
        $route = $this->request($baseUrl.'/route/v1/driving/'.$this->coordinatePath([
            $depot,
            $destination,
            $depot,
        ]), [
            'overview' => 'false',
            'steps' => 'false',
        ]);
        $roadRoute = $route['routes'][0] ?? null;
        if (($route['code'] ?? null) !== 'Ok' || ! is_array($roadRoute)
            || ! is_numeric($roadRoute['distance'] ?? null) || $roadRoute['distance'] < 0
            || ! is_array($roadRoute['legs'] ?? null) || count($roadRoute['legs']) !== 2) {
            throw new RouteOptimizationException('OSRM tidak dapat menghitung jarak pulang-pergi dari depo ke alamat.');
        }

        return (int) round($roadRoute['distance']);
    }

    /**
     * @param  Collection<int, Delivery>  $deliveries
     * @return array{
     *     deliveries: Collection<int, Delivery>,
     *     segments: array<int, array{distance_meters: int, duration_seconds: int}>,
     *     distance_meters: int,
     *     duration_seconds: int,
     *     manual_distance_meters: int|null,
     *     optimized_distance_meters: int,
     *     geometry: array<int, array{0: float, 1: float}>
     * }
     */
    public function optimize(Collection $deliveries): array
    {
        if ($deliveries->isEmpty()) {
            throw new RouteOptimizationException('Pilih minimal satu pengantaran untuk menghitung rute.');
        }

        $baseUrl = rtrim((string) config('services.osrm.url'), '/');
        if ($baseUrl === '') {
            throw new RouteOptimizationException('URL server OSRM belum dikonfigurasi.');
        }

        $depotLatitude = filter_var(config('routing.depot.latitude'), FILTER_VALIDATE_FLOAT);
        $depotLongitude = filter_var(config('routing.depot.longitude'), FILTER_VALIDATE_FLOAT);
        if ($depotLatitude === false || $depotLongitude === false
            || $depotLatitude < -90 || $depotLatitude > 90
            || $depotLongitude < -180 || $depotLongitude > 180) {
            throw new RouteOptimizationException('Koordinat depo belum dikonfigurasi dengan benar.');
        }

        $coordinates = [[(float) $depotLongitude, (float) $depotLatitude]];
        foreach ($deliveries as $delivery) {
            $longitude = (float) $delivery->order->address->longitude;
            $latitude = (float) $delivery->order->address->latitude;

            if ($longitude < -180 || $longitude > 180 || $latitude < -90 || $latitude > 90) {
                throw new RouteOptimizationException('Koordinat salah satu alamat pengantaran tidak valid.');
            }

            $coordinates[] = [$longitude, $latitude];
        }

        $coordinatePath = $this->coordinatePath($coordinates);
        $table = $this->request($baseUrl.'/table/v1/driving/'.$coordinatePath, [
            'annotations' => 'distance',
        ]);
        $distanceMatrix = $table['distances'] ?? null;
        if (($table['code'] ?? null) !== 'Ok' || ! $this->isValidDistanceMatrix($distanceMatrix, count($coordinates))) {
            throw new RouteOptimizationException('OSRM tidak dapat menghitung matriks jarak jalan untuk titik yang dipilih.');
        }

        $manualOrder = range(1, count($deliveries));
        $manualDistance = $this->cycleDistance($distanceMatrix, $manualOrder);
        $deliveryOrder = $this->heuristicShortestPath($distanceMatrix);
        $optimizedDistance = $this->cycleDistance($distanceMatrix, $deliveryOrder);

        $orderedCoordinates = array_merge(
            [$coordinates[0]],
            array_map(fn (int $index): array => $coordinates[$index], $deliveryOrder),
            [$coordinates[0]],
        );
        $route = $this->request($baseUrl.'/route/v1/driving/'.$this->coordinatePath($orderedCoordinates), [
            'overview' => 'full',
            'geometries' => 'geojson',
            'steps' => 'false',
            'annotations' => 'distance,duration',
        ]);
        $roadRoute = $route['routes'][0] ?? null;
        if (($route['code'] ?? null) !== 'Ok' || ! is_array($roadRoute)) {
            throw new RouteOptimizationException('OSRM tidak dapat membangun geometri jalan untuk urutan TSP ini.');
        }

        $legs = $roadRoute['legs'] ?? null;
        if (! is_array($legs) || count($legs) !== count($deliveries) + 1
            || ! is_numeric($roadRoute['distance'] ?? null) || ! is_numeric($roadRoute['duration'] ?? null)
            || $roadRoute['distance'] < 0 || $roadRoute['duration'] < 0) {
            throw new RouteOptimizationException('OSRM tidak mengembalikan jarak atau durasi untuk setiap segmen rute.');
        }

        $segments = [];
        foreach ($legs as $leg) {
            if (! is_array($leg) || ! is_numeric($leg['distance'] ?? null) || ! is_numeric($leg['duration'] ?? null)
                || $leg['distance'] < 0 || $leg['duration'] < 0) {
                throw new RouteOptimizationException('Jarak atau durasi segmen dari OSRM tidak valid.');
            }

            $segments[] = [
                'distance_meters' => (int) round($leg['distance']),
                'duration_seconds' => (int) round($leg['duration']),
            ];
        }

        $geometry = $roadRoute['geometry']['coordinates'] ?? null;
        if (! is_array($geometry) || count($geometry) < 2) {
            throw new RouteOptimizationException('OSRM tidak mengembalikan geometri jalan untuk rute.');
        }

        foreach ($geometry as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2
                || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])
                || $coordinate[0] < -180 || $coordinate[0] > 180
                || $coordinate[1] < -90 || $coordinate[1] > 90) {
                throw new RouteOptimizationException('Geometri rute dari OSRM tidak valid.');
            }
        }

        return [
            'deliveries' => collect(array_map(
                fn (int $index): Delivery => $deliveries->values()[$index - 1],
                $deliveryOrder,
            )),
            'segments' => $segments,
            'distance_meters' => (int) round($roadRoute['distance']),
            'duration_seconds' => (int) round($roadRoute['duration']),
            'manual_distance_meters' => is_finite($manualDistance) ? (int) round($manualDistance) : null,
            'optimized_distance_meters' => (int) round($optimizedDistance),
            'geometry' => array_map(
                fn (array $coordinate): array => [(float) $coordinate[0], (float) $coordinate[1]],
                $geometry,
            ),
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $coordinates
     */
    private function coordinatePath(array $coordinates): string
    {
        return collect($coordinates)
            ->map(fn (array $coordinate): string => number_format($coordinate[0], 7, '.', '').','.number_format($coordinate[1], 7, '.', ''))
            ->implode(';');
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $url, array $query): array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->get($url, $query)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('OSRM routing request failed.', [
                'exception' => $exception::class,
            ]);

            throw new RouteOptimizationException('Server OSRM tidak dapat dihubungi. Pastikan layanan berjalan dan URL-nya benar.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RouteOptimizationException('OSRM mengembalikan respons dengan format tidak valid.');
        }

        return $payload;
    }

    private function isValidDistanceMatrix(mixed $matrix, int $size): bool
    {
        if (! is_array($matrix) || count($matrix) !== $size) {
            return false;
        }

        foreach ($matrix as $row) {
            if (! is_array($row) || count($row) !== $size) {
                return false;
            }

            foreach ($row as $distance) {
                if ($distance !== null && (! is_numeric($distance) || $distance < 0)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Nearest-neighbor construction followed by 2-opt improvement for any delivery set size.
     *
     * @param  array<int, array<int, float|null>>  $distanceMatrix
     * @return array<int, int>
     */
    private function heuristicShortestPath(array $distanceMatrix): array
    {
        $deliveryCount = count($distanceMatrix) - 1;
        $remaining = range(1, $deliveryCount);
        $order = [];
        $current = 0;

        while ($remaining !== []) {
            $nearest = null;
            $nearestDistance = INF;
            foreach ($remaining as $candidate) {
                $distance = $distanceMatrix[$current][$candidate];
                if ($distance !== null && $distance < $nearestDistance) {
                    $nearest = $candidate;
                    $nearestDistance = $distance;
                }
            }

            if ($nearest === null) {
                throw new RouteOptimizationException('OSRM tidak menemukan jalur yang menghubungkan semua titik pengantaran.');
            }

            $order[] = $nearest;
            $current = $nearest;
            $remaining = array_values(array_diff($remaining, [$nearest]));
        }

        for ($pass = 0; $pass < 20; $pass++) {
            $improved = false;
            $currentDistance = $this->cycleDistance($distanceMatrix, $order);

            for ($start = 0; $start < $deliveryCount - 1 && ! $improved; $start++) {
                for ($end = $start + 1; $end < $deliveryCount; $end++) {
                    $candidate = $order;
                    $reversed = array_reverse(array_slice($candidate, $start, $end - $start + 1));
                    array_splice($candidate, $start, count($reversed), $reversed);

                    if ($this->cycleDistance($distanceMatrix, $candidate) < $currentDistance) {
                        $order = $candidate;
                        $improved = true;
                        break;
                    }
                }
            }

            if (! $improved) {
                break;
            }
        }

        $optimizedDistance = $this->cycleDistance($distanceMatrix, $order);
        if (! is_finite($optimizedDistance)) {
            throw new RouteOptimizationException('OSRM tidak menemukan rute pulang-pergi yang menghubungkan depo dan semua titik pengantaran.');
        }

        return $order;
    }

    /**
     * @param  array<int, array<int, float|null>>  $distanceMatrix
     * @param  array<int, int>  $order
     */
    private function cycleDistance(array $distanceMatrix, array $order): float
    {
        $distance = 0.0;
        $previous = 0;
        foreach ($order as $delivery) {
            $segment = $distanceMatrix[$previous][$delivery];
            if ($segment === null) {
                return INF;
            }
            $distance += $segment;
            $previous = $delivery;
        }

        $returnDistance = $distanceMatrix[$previous][0];

        return $returnDistance === null ? INF : $distance + $returnDistance;
    }
}
