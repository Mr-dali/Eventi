<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    private const API = 'https://api.openweathermap.org/data/2.5/';

    public function __invoke(Request $request)
    {
        $weather      = null;
        $forecastDays = [];
        $error        = null;

        if ($request->isMethod('post')) {
            $city = trim((string) $request->input('city'));

            if ($city === '') {
                $error = 'Entrer une ville !';
            } else {
                $params  = ['q' => $city, 'appid' => config('openweather.key'), 'units' => 'metric', 'lang' => 'fr'];
                $current = Http::timeout(8)->get(self::API . 'weather', $params);

                if ($current->successful() && (int) $current->json('cod') === 200) {
                    $weather  = $current->json();
                    $forecast = Http::timeout(8)->get(self::API . 'forecast', $params);

                    if ($forecast->successful()) {
                        $days = [];
                        foreach ($forecast->json('list', []) as $item) {
                            // un point par jour : la prévision de midi
                            if (substr($item['dt_txt'], 11, 2) === '12') {
                                $days[substr($item['dt_txt'], 0, 10)] = [
                                    'temp' => $item['main']['temp'],
                                    'icon' => $item['weather'][0]['icon'],
                                    'desc' => $item['weather'][0]['description'],
                                ];
                            }
                        }
                        $forecastDays = array_values($days);
                    }
                } else {
                    $error = 'Ville introuvable ❌';
                }
            }
        }

        return view('weather', compact('weather', 'forecastDays', 'error'));
    }
}
