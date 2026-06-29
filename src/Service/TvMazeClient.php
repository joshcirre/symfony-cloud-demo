<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Thin wrapper around the free, key-less TVMaze API.
 *
 * @see https://www.tvmaze.com/api
 */
final class TvMazeClient
{
    private const BASE_URL = 'https://api.tvmaze.com';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Search shows by a free-text query.
     *
     * @return array<int, array<string, mixed>> A list of normalized show results
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $response = $this->httpClient->request('GET', self::BASE_URL.'/search/shows', [
            'query' => ['q' => $query],
        ]);

        // TVMaze returns: [{ "score": float, "show": {...} }, ...]
        $results = $response->toArray();

        return array_map(
            fn (array $result) => $this->normalizeShow($result['show']),
            $results,
        );
    }

    /**
     * Fetch a single show by id, with cast and seasons embedded.
     *
     * @return array<string, mixed>|null Null when the show does not exist
     */
    public function getShow(int $id): ?array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL.'/shows/'.$id, [
            'query' => ['embed' => ['cast', 'seasons']],
        ]);

        if (404 === $response->getStatusCode()) {
            return null;
        }

        $show = $response->toArray();
        $embedded = $show['_embedded'] ?? [];

        return $this->normalizeShow($show) + [
            'language' => $show['language'] ?? null,
            'runtime' => $show['averageRuntime'] ?? $show['runtime'] ?? null,
            'ended' => $show['ended'] ?? null,
            'officialSite' => $show['officialSite'] ?? null,
            'schedule' => $show['schedule'] ?? null,
            'type' => $show['type'] ?? null,
            'imageOriginal' => $show['image']['original'] ?? null,
            'seasonCount' => isset($embedded['seasons']) ? \count($embedded['seasons']) : null,
            'cast' => array_map(
                fn (array $member) => [
                    'person' => $member['person']['name'] ?? null,
                    'character' => $member['character']['name'] ?? null,
                    'image' => $member['person']['image']['medium'] ?? null,
                ],
                \array_slice($embedded['cast'] ?? [], 0, 12),
            ),
        ];
    }

    /**
     * Flatten the parts of a TVMaze show payload we actually display.
     *
     * @param array<string, mixed> $show
     *
     * @return array<string, mixed>
     */
    private function normalizeShow(array $show): array
    {
        return [
            'id' => $show['id'] ?? null,
            'name' => $show['name'] ?? 'Untitled',
            'genres' => $show['genres'] ?? [],
            'status' => $show['status'] ?? null,
            'premiered' => $show['premiered'] ?? null,
            'rating' => $show['rating']['average'] ?? null,
            'network' => $show['network']['name'] ?? ($show['webChannel']['name'] ?? null),
            'image' => $show['image']['medium'] ?? null,
            'url' => $show['url'] ?? null,
            // summary is a short HTML snippet from the API
            'summary' => $show['summary'] ?? null,
        ];
    }
}
