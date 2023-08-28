<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

/**
 * @throws GuzzleException
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2): float|int
{
    $originLat = $lat1;
    $originLon = $lon1;
    $destinationLat = $lat2;
    $destinationLon = $lon2;

    $client = new Client();
    $response = $client->get('https://router.project-osrm.org/route/v1/driving/' . $originLon . ',' . $originLat . ';' . $destinationLon . ',' . $destinationLat . '?overview=false');
    $data = json_decode($response->getBody(), true);

    $distanceInKilometers = $data['routes'][0]['distance'] / 1000;
    return $distanceInKilometers * 1000;
}

function getTimezone($inputLat, $inputLong): string
{
    $baseUrl = config('app.timezone_api');
    $inputCoordinates = Http::get($baseUrl . '/' . $inputLat . '/' . $inputLong);
    $tz = '';
    foreach ($inputCoordinates->json() as $key => $value) {
        if ($key == 'tz') {
            $tz = $value;
        }
    }
    return $tz;
}

function getTimezoneV2($latitude,$longitude): string
{
    $inputCoordinates = Http::get("https://api.wheretheiss.at/v1/coordinates/" . $latitude . "," . $longitude);
    return $inputCoordinates->json()['timezone_id'];
}

function getClientTimezone(): string {
    $clientTimezone = (string) request()->header('X-Client-Timezone');
    if ($clientTimezone == "") {
        return "Asia/Jakarta";
    }

    return $clientTimezone;
}
