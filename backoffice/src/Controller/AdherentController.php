<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/adherents')]
class AdherentController extends AbstractController
{
    public function __construct(private ApiClient $api) {}

    #[Route('', name: 'app_adherents')]
    public function index(): Response
    {
        $adherents = $this->api->getAdherents();
        return $this->render('adherent/index.html.twig', [
            'adherents' => $adherents,
        ]);
    }

    #[Route('/nouveau', name: 'app_adherent_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'nom'            => $request->request->get('nom'),
                'prenom'         => $request->request->get('prenom'),
                'date_naissance' => $request->request->get('date_naissance'),
                'ceinture'       => $request->request->get('ceinture'),
                'poids'          => $request->request->get('poids') ?: null,
            ];

            $result = $this->api->createAdherent($data);

            if ($result && isset($result['id'])) {
                $this->addFlash('success', 'Adhérent créé avec succès.');
                return $this->redirectToRoute('app_adherents');
            }

            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur lors de la création de l\'adhérent.');
        }

        return $this->render('adherent/form.html.twig', [
            'adherent' => null,
            'mode'     => 'create',
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_adherent_edit')]
    public function edit(int $id, Request $request): Response
    {
        $adherent = $this->api->getAdherent($id);
        if (!$adherent) {
            $this->addFlash('error', 'Adhérent introuvable.');
            return $this->redirectToRoute('app_adherents');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'nom'            => $request->request->get('nom'),
                'prenom'         => $request->request->get('prenom'),
                'date_naissance' => $request->request->get('date_naissance'),
                'ceinture'       => $request->request->get('ceinture'),
                'poids'          => $request->request->get('poids') ?: null,
            ];

            $result = $this->api->updateAdherent($id, $data);

            if ($result && isset($result['id'])) {
                $this->addFlash('success', 'Adhérent mis à jour.');
                return $this->redirectToRoute('app_adherents');
            }

            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur lors de la mise à jour.');
        }

        return $this->render('adherent/form.html.twig', [
            'adherent' => $adherent,
            'mode'     => 'edit',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_adherent_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->api->deleteAdherent($id);
        $this->addFlash('success', 'Adhérent supprimé.');
        return $this->redirectToRoute('app_adherents');
    }
}