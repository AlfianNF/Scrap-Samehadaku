<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Symfony\Component\DomCrawler\Crawler;

class ScrapController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $client = new Client();
            $response = $client->get(env('BASE_URL'));
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            $animeList = [];
            $crawler->filter('.post-show ul li')->each(function (Crawler $node) use (&$animeList) {
                try {
                    $titleElement = $node->filter('.entry-title a');
                    $imageElement = $node->filter('.thumb img');

                    $animeList[] = [
                        'title' => $titleElement->text(),
                        'link' => $titleElement->attr('href'),
                        'image' => $imageElement->attr('src'),
                    ];
                } catch (\InvalidArgumentException $e) {
                    \Log::error("Error fetching anime element: " . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error("Unexpected error during scraping: " . $e->getMessage());
                }
            });

            return response()->json(['status' => 'success', 'anime_list' => $animeList], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $client = new Client();
            $url = env('BASE_URL') . '/anime/' . $slug . '/';
            $response = $client->get($url);
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            $animeData = [];

            try {
                $animeData['title'] = $crawler->filter('.entry-title')->text();
            } catch (\InvalidArgumentException $e) {
                $animeData['title'] = null;
            }

            try {
                $animeData['poster'] = $crawler->filter('.thumb img')->attr('src');
            } catch (\InvalidArgumentException $e) {
                $animeData['poster'] = null;
            }

            try {
                $animeData['sinopsis'] = $crawler->filter('.entry-content p')->each(function (Crawler $node) {
                    return $node->text();
                });
            } catch (\InvalidArgumentException $e) {
                $animeData['sinopsis'] = null;
            }

            try {
                $animeData['detail'] = $crawler->filter('.anime-info span')->each(function (Crawler $node) {
                    return trim($node->text());
                });
            } catch (\InvalidArgumentException $e) {
                $animeData['detail'] = null;
            }

            try {
                $animeData['episodes'] = $crawler->filter('.list-episode li a')->each(function (Crawler $node) {
                    return [
                        'title' => $node->text(),
                        'link' => $node->attr('href'),
                    ];
                });
            } catch (\InvalidArgumentException $e) {
                $animeData['episodes'] = null;
            }

            return response()->json(['status' => 'success', 'anime_data' => $animeData], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}