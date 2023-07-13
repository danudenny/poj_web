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
    return $inputCoordinates->json()['tz'];
}
