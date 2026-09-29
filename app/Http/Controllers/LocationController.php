<?php

namespace App\Http\Controllers;

use App\Models\CustomLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    /**
     * Get list of locations filtered by parent_code.
     */
    public function getLocations(Request $request): JsonResponse
    {
        $parentCode = $request->query('parent_code');

        $allLocations = Cache::rememberForever('master_locations_flat', function () {
            $fileSby = base_path('data/master_lokasi_sby.csv');
            $fileAll = base_path('data/master_lokasi_all.csv');

            $locations = [];

            if (file_exists($fileSby)) {
                foreach (file($fileSby, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $line) {
                    if ($i === 0) {
                        continue;
                    }
                    $parts = explode(';', $line);
                    if (count($parts) >= 2) {
                        $code = trim($parts[0], ' "');
                        $name = trim($parts[1], ' "');
                        $locations[$code] = $name;
                    }
                }
            }

            if (file_exists($fileAll)) {
                foreach (file($fileAll, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $line) {
                    if ($i === 0) {
                        continue;
                    }
                    $parts = explode(';', $line);
                    if (count($parts) >= 2) {
                        $code = trim($parts[0], ' "');
                        $name = trim($parts[1], ' "');
                        if (! isset($locations[$code])) {
                            $locations[$code] = $name;
                        }
                    }
                }
            }

            // Also load custom locations added via Master Lokasi
            foreach (CustomLocation::all() as $cl) {
                $locations[$cl->code] = $cl->name;
            }

            return $locations;
        });

        $result = [];

        if (! $parentCode) {
            // Level 1: Provinces (2 digits)
            foreach ($allLocations as $code => $name) {
                $codeStr = (string) $code;
                if (strlen($codeStr) === 2) {
                    $result[] = ['code' => $codeStr, 'name' => $name];
                }
            }
        } else {
            $parentCodeStr = (string) $parentCode;
            $parentLen = strlen($parentCodeStr);
            $targetLen = match ($parentLen) {
                2 => 5,    // Level 2: Kota / Kabupaten (5 chars, e.g. 35.78)
                5 => 8,    // Level 3: Kecamatan (8 chars, e.g. 35.78.03)
                8 => 13,   // Level 4: Kelurahan (13 chars, e.g. 35.78.03.1006)
                default => 0,
            };

            $prefix = $parentCodeStr.'.';
            foreach ($allLocations as $code => $name) {
                $codeStr = (string) $code;
                if (strlen($codeStr) === $targetLen && str_starts_with($codeStr, $prefix)) {
                    $result[] = ['code' => $codeStr, 'name' => $name];
                }
            }
        }

        usort($result, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json($result);
    }
}
