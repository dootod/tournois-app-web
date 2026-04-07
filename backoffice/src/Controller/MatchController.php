<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/matchs')]
class MatchController extends AbstractController
{
    public function __construct(private ApiClient $api) {}

    #[Route('', name: 'app_matchs')]
    public function index(): Response
    {
        $matchs = $this->api->getMatchs();
        return $this->render('match/index.html.twig', [
            'matchs' => $matchs,
        ]);
    }

    #[Route('/{id}', name: 'app_match_show')]
    public function show(int $id): Response
    {
        $match = $this->api->getMatch($id);
        if (!$match) {
            $this->addFlash('error', 'Match non trouvé.');
            return $this->redirectToRoute('app_matchs');
        }

        return $this->render('match/show.html.twig', [
            'match' => $match,
        ]);
    }
}
