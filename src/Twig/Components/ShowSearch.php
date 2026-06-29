<?php

namespace App\Twig\Components;

use App\Service\TvMazeClient;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ShowSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    public function __construct(private readonly TvMazeClient $client)
    {
    }

    /**
     * Re-runs on every render, i.e. each time the live `query` prop changes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getShows(): array
    {
        if (mb_strlen(trim($this->query)) < 2) {
            return [];
        }

        return $this->client->search($this->query);
    }
}
