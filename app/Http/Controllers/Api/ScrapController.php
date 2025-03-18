<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Http\Request; 

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
            $url = env('BASE_URL') . '/anime/' . $slug . '/';

            $client = new Client();
            $response = $client->get($url);
            $html = (string) $response->getBody();

            $crawler = new Crawler($html);

            $anime = [
                'title' => $crawler->filter('h1.entry-title')->count() ? $crawler->filter('h1.entry-title')->text() : null,
                'image' => $crawler->filter('.thumb img')->count() ? $crawler->filter('.thumb img')->attr('src') : null,
                'synopsis' => $crawler->filter('.entry-content p')->count() ? $crawler->filter('.entry-content p')->text() : null,
                'latest_episode' => null,
                'episodes' => [],
            ];

            if ($crawler->filter('.play-new-episode')->count()) {
                $latestEpisodeElement = $crawler->filter('.play-new-episode')->first();
                $anime['latest_episode'] = [
                    'title' => trim($latestEpisodeElement->text()) ?: 'Latest Episode',
                    'link'  => $latestEpisodeElement->attr('href'),                ];
            }

            $crawler->filter('.epsright, .epsleft')->each(function (Crawler $node) use (&$anime) {
                try {
                    $episodeElement = $node->filter('.eps a, .lchx a');
                    $dateElement = $node->filter('.date');

                    if ($episodeElement->count()) {
                        $anime['episodes'][] = [
                            'title' => $episodeElement->text(),
                            'link'  => $episodeElement->attr('href'),
                            'release_date' => $dateElement->count() ? $dateElement->text() : null,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error("Error fetching episode: " . $e->getMessage());
                }
            });

            return response()->json(['status' => 'success', 'anime' => $anime], 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    public function latestAnime(Request $request): JsonResponse
    {
        try {
            $page = $request->query('page', 1);
            if ($page < 1) {
                return response()->json(['status' => 'error', 'message' => 'Invalid page number'], 400);
            }

            $url = ($page > 1)
                ? env('BASE_URL') . "/anime-terbaru/page/{$page}/"
                : env('BASE_URL') . "/anime-terbaru/";

            $client = new Client();
            $response = $client->get($url);
            $html = (string) $response->getBody();

            $crawler = new Crawler($html);

            $animeList = [];
            $crawler->filter('li[itemtype="http://schema.org/CreativeWork"]')->each(function (Crawler $node) use (&$animeList, $page) {
                try {
                    $titleElement = $node->filter('.entry-title a');
                    $imageElement = $node->filter('.thumb img');
                    $episodeElement = $node->filter('span b:contains("Episode") + author');
                    $authorElement = $node->filter('span b:contains("Posted by") + author');
                    $releaseDateElement = $node->filter('span i.dashicons-calendar + b + span');

                    $animeList[] = [
                        'title' => $titleElement->count() ? $titleElement->text() : null,
                        'link' => $titleElement->count() ? $titleElement->attr('href') : null,
                        'image' => $imageElement->count() ? $imageElement->attr('src') : null,
                        'episode' => $episodeElement->count() ? $episodeElement->text() : null,
                        'posted_by' => $authorElement->count() ? $authorElement->text() : null,
                        'release_date' => $releaseDateElement->count() ? $releaseDateElement->text() : null,
                    ];
                } catch (\InvalidArgumentException $e) {
                    \Log::warning("Element not found on page {$page}: " . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error("Error fetching anime on page {$page}: " . $e->getMessage());
            }
        });

            return response()->json([
                'status' => 'success',
                'page' => $page,
                'anime_list' => $animeList
            ], 200);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }
}