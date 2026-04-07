<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(private ApiClient $api) {}

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        $adherents  = $this->api->getAdherents();
        $tournois   = $this->api->getTournois();
        $participants = $this->api->getParticipants();

        // Stats rapides
        $tournoiOuverts = array_filter($tournois, fn($t) => $t['etat'] === 'ouvert');
        $tournoiEnCours = array_filter($tournois, fn($t) => $t['etat'] === 'en_cours');

        return $this->render('dashboard/index.html.twig', [
            'nb_adherents'    => count($adherents),
            'nb_tournois'     => count($tournois),
            'nb_participants' => count($participants),
            'tournois_ouverts' => count($tournoiOuverts),
            'tournois_en_cours' => count($tournoiEnCours),
            'tournois_recents' => array_slice($tournois, 0, 5),
            'adherents_recents' => array_slice($adherents, 0, 5),
        ]);
    }
}