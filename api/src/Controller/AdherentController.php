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
    private const GENRES_VALIDES = ['masculin', 'feminin', 'mixte'];

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
        try {
            $adherents = $this->adherentRepository->findAll();
            $data = $this->serializer->serialize($adherents, 'json', ['groups' => 'adherent:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération',
                'message' => 'Une erreur s\'est produite lors du chargement des adhérents'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $adherent = $this->adherentRepository->find($id);
            if (!$adherent) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'L\'adhérent avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // POST /api/adherents
    #[Route('', name: 'adherent_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);

            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON invalide',
                    'message' => 'Les données envoyées ne sont pas au format JSON valide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation des champs obligatoires
            if (empty($data['nom'])) {
                return $this->json([
                    'error' => 'Champ manquant',
                    'message' => 'Le nom est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (empty($data['prenom'])) {
                return $this->json([
                    'error' => 'Champ manquant',
                    'message' => 'Le prénom est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (empty($data['ceinture'])) {
                return $this->json([
                    'error' => 'Champ manquant',
                    'message' => 'La ceinture est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du genre si fourni
            if (isset($data['genre']) && !in_array($data['genre'], self::GENRES_VALIDES)) {
                return $this->json([
                    'error' => 'Genre invalide',
                    'message' => 'Genre acceptés: ' . implode(', ', self::GENRES_VALIDES)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du poids si fourni
            if (isset($data['poids'])) {
                if (!is_numeric($data['poids']) || $data['poids'] <= 0) {
                    return $this->json([
                        'error' => 'Poids invalide',
                        'message' => 'Le poids doit être un nombre positif'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $adherent = new Adherent();
            $adherent->setNom(trim($data['nom']));
            $adherent->setPrenom(trim($data['prenom']));
            $adherent->setCeinture(trim($data['ceinture']));
            $adherent->setPoids($data['poids'] ?? null);
            $adherent->setGenre($data['genre'] ?? null);

            // Validation des dates
            if (isset($data['date_naissance'])) {
                try {
                    $dateNaissance = new \DateTime($data['date_naissance']);
                    $today = new \DateTime();
                    if ($dateNaissance > $today) {
                        return $this->json([
                            'error' => 'Date invalide',
                            'message' => 'La date de naissance ne peut pas être dans le futur'
                        ], Response::HTTP_BAD_REQUEST);
                    }
                    $adherent->setDateNaissance($dateNaissance);
                } catch (\Exception $e) {
                    return $this->json([
                        'error' => 'Format de date invalide',
                        'message' => 'Utilisez le format YYYY-MM-DD'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($data['date_adhesion'])) {
                try {
                    $adherent->setDateAdhesion(new \DateTime($data['date_adhesion']));
                } catch (\Exception $e) {
                    return $this->json([
                        'error' => 'Format de date invalide',
                        'message' => 'Utilisez le format YYYY-MM-DD'
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $adherent->setDateAdhesion(new \DateTime());
            }

            // Validation Symfony
            $errors = $this->validator->validate($adherent);
            if (count($errors) > 0) {
                $errMessages = [];
                foreach ($errors as $error) {
                    $errMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return $this->json([
                    'error' => 'Validation échouée',
                    'errors' => $errMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->em->persist($adherent);
            $this->em->flush();

            $result = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
            return new JsonResponse($result, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la création',
                'message' => 'Une erreur s\'est produite lors de la création de l\'adhérent'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // PUT /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $adherent = $this->adherentRepository->find($id);
            if (!$adherent) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'L\'adhérent avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON invalide',
                    'message' => 'Les données envoyées ne sont pas au format JSON valide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Mise à jour avec validation
            if (isset($data['nom'])) {
                $nom = trim($data['nom']);
                if (empty($nom)) {
                    return $this->json(['error' => 'Le nom ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
                }
                $adherent->setNom($nom);
            }

            if (isset($data['prenom'])) {
                $prenom = trim($data['prenom']);
                if (empty($prenom)) {
                    return $this->json(['error' => 'Le prénom ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
                }
                $adherent->setPrenom($prenom);
            }

            if (isset($data['ceinture'])) {
                $ceinture = trim($data['ceinture']);
                if (empty($ceinture)) {
                    return $this->json(['error' => 'La ceinture ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
                }
                $adherent->setCeinture($ceinture);
            }

            if (isset($data['poids'])) {
                if (!is_numeric($data['poids']) || $data['poids'] <= 0) {
                    return $this->json(['error' => 'Le poids doit être un nombre positif'], Response::HTTP_BAD_REQUEST);
                }
                $adherent->setPoids($data['poids']);
            }

            if (isset($data['genre'])) {
                if (!in_array($data['genre'], self::GENRES_VALIDES)) {
                    return $this->json([
                        'error' => 'Genre invalide',
                        'message' => 'Genre acceptés: ' . implode(', ', self::GENRES_VALIDES)
                    ], Response::HTTP_BAD_REQUEST);
                }
                $adherent->setGenre($data['genre']);
            }

            if (isset($data['date_naissance'])) {
                try {
                    $dateNaissance = new \DateTime($data['date_naissance']);
                    $today = new \DateTime();
                    if ($dateNaissance > $today) {
                        return $this->json(['error' => 'La date de naissance ne peut pas être dans le futur'], Response::HTTP_BAD_REQUEST);
                    }
                    $adherent->setDateNaissance($dateNaissance);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($data['date_adhesion'])) {
                try {
                    $adherent->setDateAdhesion(new \DateTime($data['date_adhesion']));
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
                }
            }

            $errors = $this->validator->validate($adherent);
            if (count($errors) > 0) {
                $errMessages = [];
                foreach ($errors as $error) {
                    $errMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return $this->json([
                    'error' => 'Validation échouée',
                    'errors' => $errMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->em->flush();

            $result = $this->serializer->serialize($adherent, 'json', ['groups' => 'adherent:read']);
            return new JsonResponse($result, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => 'Une erreur s\'est produite'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // DELETE /api/adherents/{id}
    #[Route('/{id}', name: 'adherent_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $adherent = $this->adherentRepository->find($id);
            if (!$adherent) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'L\'adhérent n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($adherent);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Adhérent supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression',
                'message' => 'Une erreur s\'est produite'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
