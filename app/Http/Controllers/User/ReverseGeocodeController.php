<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReverseGeocodeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $coordinates = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $response = Http::acceptJson()
                ->withUserAgent(config('services.nominatim.user_agent'))
                ->connectTimeout(3)
                ->timeout(8)
                ->get(config('services.nominatim.url').'/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $coordinates['latitude'],
                    'lon' => $coordinates['longitude'],
                    'zoom' => 18,
                    'addressdetails' => 1,
                    ...array_filter([
                        'email' => config('services.nominatim.email'),
                    ], fn (?string $email): bool => $email !== null && $email !== ''),
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Nominatim reverse geocoding request failed.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Layanan pencarian alamat sedang tidak tersedia. Anda tetap dapat mengisi alamat secara manual.',
            ], 502);
        }

        $result = $response->json();
        $fullAddress = is_array($result) ? trim((string) ($result['display_name'] ?? '')) : '';

        if ($fullAddress === '') {
            return response()->json([
                'message' => 'Alamat untuk titik ini tidak ditemukan. Silakan isi alamat secara manual.',
            ], 404);
        }

        $label = is_array($result) ? trim((string) ($result['name'] ?? '')) : '';

        return response()->json([
            'full_address' => $fullAddress,
            'label' => $label !== '' ? $label : null,
        ]);
    }
}
