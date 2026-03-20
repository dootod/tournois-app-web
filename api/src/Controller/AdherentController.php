<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Repository\AdherentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/adherents')]
class AdherentController extends AbstractController
{
    public function __construct(
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // GET /api/adherents
    #[Route('', name: 'adherent_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $adherents = $this->adherentRepository->findAll();
        $data = $this->serializer->serialize($adherents, 'json', ['groups' => 'adherent:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/adherents
    #[Route('', name: 'adherent_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = new Adherent();
        $adherent->setNom($data['nom'] ?? '');
        $adherent->setPrenom($data['prenom'] ?? '');
        $adherent->setCeinture($data['ceinture'] ?? '');
        $adherent->setPoids($data['poids'] ?? null);

        if (isset($data['date_naissance'])) {
            $adherent->setDateNaissance(new \DateTime($data['date_naissance']));
        }
        if (isset($data['date_adhesion'])) {
            $adherent->setDateAdhesion(new \DateTime($data['date_adhesion']));
        } else {
            $adherent->setDateAdhesion(new \DateTime());
        }

        $errors = $this->validator->validate($adherent);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($adherent);
        $this->em->flush();

        $result = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom'])) $adherent->setNom($data['nom']);
        if (isset($data['prenom'])) $adherent->setPrenom($data['prenom']);
        if (isset($data['ceinture'])) $adherent->setCeinture($data['ceinture']);
        if (isset($data['poids'])) $adherent->setPoids($data['poids']);
        if (isset($data['date_naissance'])) $adherent->setDateNaissance(new \DateTime($data['date_naissance']));
        if (isset($data['date_adhesion'])) $adherent->setDateAdhesion(new \DateTime($data['date_adhesion']));

        $errors = $this->validator->validate($adherent);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        $result = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // PATCH /api/adherents/{id}/renouvellement
    #[Route('/{id}/renouvellement', name: 'adherent_renouvellement', methods: ['PATCH'])]
    public function renouvellement(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $adherent->setDateAdhesion(new \DateTime());
        $this->em->flush();

        $result = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($adherent);
        $this->em->flush();

        return $this->json(['message' => 'Adhérent supprimé'], Response::HTTP_OK);
    }
}