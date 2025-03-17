<?php

namespace App\Http\Controllers;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class ScrapController extends Controller
{
    public function index()
    {
        $host = 'http://localhost:52989'; 
        $driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());

        $driver->get('https://samehadaku.rest');

        sleep(3);

        $animeList = [];
        $elements = $driver->findElements(WebDriverBy::cssSelector('.post-show ul li'));

        foreach ($elements as $element) {
            try {
                $titleElement = $element->findElement(WebDriverBy::cssSelector('.entry-title a'));
                $imageElement = $element->findElement(WebDriverBy::cssSelector('.thumb img'));

                $animeList[] = [
                    'title' => $titleElement->getText(),
                    'link'  => $titleElement->getAttribute('href'),
                    'image' => $imageElement->getAttribute('src'),
                ];
            } catch (\Exception $e) {
                continue; 
            }
        }

        $driver->quit();

        return response()->json(['anime_list' => $animeList]);
    }
}
