<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AdherentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private AdherentRepository $adherents,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'user_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $json = $this->serializer->serialize($this->users->findAll(), 'json', ['groups' => ['user:read', 'adherent:read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->users->find($id);
        if (!$user) return $this->json(['error' => 'Not found'], 404);
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => ['user:read', 'adherent:read']]), 200, [], true);
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $roles = $data['roles'] ?? ['ROLE_USER'];
        $adherentId = $data['adherent_id'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'email and password required'], 400);
        }
        if ($this->users->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already used'], 409);
        }
        if (in_array('ROLE_USER', $roles) && !in_array('ROLE_ADMIN', $roles) && !$adherentId) {
            return $this->json(['error' => 'A user account must be linked to an adherent'], 400);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        if ($adherentId) {
            $adh = $this->adherents->find($adherentId);
            if (!$adh) return $this->json(['error' => 'Adherent not found'], 404);
            $user->setAdherent($adh);
        }
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => ['user:read', 'adherent:read']]), 201, [], true);
    }

    #[Route('/{id}', name: 'user_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->users->find($id);
        if (!$user) return $this->json(['error' => 'Not found'], 404);

        $data = json_decode($request->getContent(), true) ?: [];
        if (isset($data['email'])) $user->setEmail(trim($data['email']));
        if (isset($data['roles']) && is_array($data['roles'])) $user->setRoles($data['roles']);
        if (!empty($data['password'])) {
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setApiToken(null); // force re-login
        }
        if (array_key_exists('adherent_id', $data)) {
            if ($data['adherent_id'] === null) {
                $user->setAdherent(null);
            } else {
                $adh = $this->adherents->find($data['adherent_id']);
                if (!$adh) return $this->json(['error' => 'Adherent not found'], 404);
                $user->setAdherent($adh);
            }
        }
        $this->em->flush();
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => ['user:read', 'adherent:read']]), 200, [], true);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->users->find($id);
        if (!$user) return $this->json(['error' => 'Not found'], 404);
        $this->em->remove($user);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }
}
