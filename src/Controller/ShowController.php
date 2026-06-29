<?php

namespace App\Controller;

use App\Service\TvMazeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowController extends AbstractController
{
    public function __construct(private readonly TvMazeClient $client)
    {
    }

    #[Route('/show/{id<\d+>}', name: 'app_show')]
    public function show(int $id): Response
    {
        $show = $this->client->getShow($id);

        if (null === $show) {
            throw $this->createNotFoundException('Show not found.');
        }

        return $this->render('show/index.html.twig', [
            'show' => $show,
        ]);
    }
}
