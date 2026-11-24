<?php

namespace App\Support;

use App\Models\Courier;
use Illuminate\Support\Str;

class CourierUsernameGenerator
{
    public function generate(Courier $courier): string
    {
        $firstName = Str::of($courier->name)->ascii()->lower()->explode(' ')->first();
        $namePart = preg_replace('/[^a-z0-9]/', '', $firstName) ?: 'kurir';
        $depotCode = Str::of(config('courier.depot_code'))->ascii()->upper()->replaceMatches('/[^A-Z0-9]/', '');

        return $namePart.str_pad((string) $courier->id, 2, '0', STR_PAD_LEFT).$depotCode;
    }
}
