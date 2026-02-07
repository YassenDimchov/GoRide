<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class OrsController extends Controller
{

    private function orsGeocodeAutocomplete(string $q, $lat, $lng): array
    {
        $key = config('services.ors.key');
        $url = 'https://api.openrouteservice.org/geocode/autocomplete';

        $params = [
            'api_key' => $key,
            'text' => $q,
            'size' => 6,
            'boundary.circle.lat' => $lat,
            'boundary.circle.lon' => $lng,
            'boundary.circle.radius' => 20,
            'boundary.country' => 'BG',
            'layers' => 'address,street,venue',
        ];

        return $this->orsGeocode($url, $params);
    }

    private function orsGeocodeSearch(string $q, $lat, $lng): array
    {
        $key = config('services.ors.key');
        $url = 'https://api.openrouteservice.org/geocode/search';

        $params = [
            'api_key' => $key,
            'text' => $q,
            'size' => 6,
            'focus.point.lat' => $lat,
            'focus.point.lon' => $lng,
            'boundary.country' => 'BG',
            'layers' => 'address,street',
        ];

        return $this->orsGeocode($url, $params);
    }

    private function orsGeocode(string $url, array $params): array
    {
        $full = $url . '?' . http_build_query($params);

        $ch = curl_init($full);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) return [];

        $json = json_decode($raw, true);
        $features = $json['features'] ?? [];

        $out = [];
        foreach ($features as $f) {
            $p = $f['properties'] ?? [];
            $g = $f['geometry']['coordinates'] ?? null;
            if (!$g || count($g) < 2) continue;

            $label = $p['label'] ?? $p['name'] ?? null;
            if (!$label) continue;

            $out[] = [
                'label' => $label,
                'lat' => (float) $g[1],
                'lng' => (float) $g[0],
                'layer' => $p['layer'] ?? null,
                'confidence' => $p['confidence'] ?? null,
            ];
        }

        return $out;
    }


    public function autocomplete(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['data' => []]);
        }

        $lat = $request->query('lat', 42.6977);
        $lng = $request->query('lng', 23.3219);

        $hasNumber = preg_match('/\d/', $q) === 1;

        $auto = $this->orsGeocodeAutocomplete($q, $lat, $lng);
        $search = $hasNumber ? $this->orsGeocodeSearch($q, $lat, $lng) : [];

        $all = array_merge($auto, $search);
        $unique = [];
        $seen = [];

        foreach ($all as $item) {
            $key = $item['label'] . '|' . $item['lat'] . '|' . $item['lng'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = $item;
        }

        return response()->json(['data' => array_slice($unique, 0, 8)], 200);
    }

    public function directions(Request $request)
    {
        $request->validate([
            'start_lat' => ['required', 'numeric'],
            'start_lng' => ['required', 'numeric'],
            'end_lat'   => ['required', 'numeric'],
            'end_lng'   => ['required', 'numeric'],
        ]);

        $key = config('services.ors.key');
        if (!$key) return response()->json(['message' => 'ORS key missing'], 500);

        $start = [(float)$request->start_lng, (float)$request->start_lat];
        $end   = [(float)$request->end_lng, (float)$request->end_lat];

        $body = [
            'coordinates' => [$start, $end],
        ];

        $res = Http::withHeaders([
            'Authorization' => $key,
        ])->asJson()
        ->post('https://api.openrouteservice.org/v2/directions/driving-car/geojson', $body);


        if (!$res->ok()) {
            \Log::warning('ORS directions failed', [
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);

            return response()->json([
                'message' => 'Directions failed',
            ], 502);
        }

        return response()->json($res->json());
    }


}