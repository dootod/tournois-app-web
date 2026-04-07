<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comptes')]
class UserController extends AbstractController
{
    public function __construct(private ApiClient $api) {}

    #[Route('', name: 'app_users', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users'     => $this->api->getUsers(),
            'adherents' => $this->api->getAdherents(),
        ]);
    }

    #[Route('/nouveau', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $roles = [];
            if ($request->request->get('role') === 'admin') {
                $roles = ['ROLE_ADMIN'];
            } else {
                $roles = ['ROLE_USER'];
            }
            $data = [
                'email'       => $request->request->get('email'),
                'password'    => $request->request->get('password'),
                'roles'       => $roles,
                'adherent_id' => $request->request->get('adherent_id') ?: null,
            ];
            $result = $this->api->createUser($data);
            if ($result) {
                $this->addFlash('success', 'Compte créé.');
                return $this->redirectToRoute('app_users');
            }
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur.');
        }
        return $this->render('user/form.html.twig', [
            'user'      => null,
            'adherents' => $this->api->getAdherents(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->api->getUser($id);
        if (!$user) {
            $this->addFlash('error', 'Compte introuvable.');
            return $this->redirectToRoute('app_users');
        }
        if ($request->isMethod('POST')) {
            $roles = $request->request->get('role') === 'admin' ? ['ROLE_ADMIN'] : ['ROLE_USER'];
            $data = [
                'email'       => $request->request->get('email'),
                'roles'       => $roles,
                'adherent_id' => $request->request->get('adherent_id') ?: null,
            ];
            if ($request->request->get('password')) {
                $data['password'] = $request->request->get('password');
            }
            if ($this->api->updateUser($id, $data)) {
                $this->addFlash('success', 'Compte modifié.');
                return $this->redirectToRoute('app_users');
            }
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur.');
        }
        return $this->render('user/form.html.twig', [
            'user'      => $user,
            'adherents' => $this->api->getAdherents(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_user_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->api->deleteUser($id);
        $this->addFlash('success', 'Compte supprimé.');
        return $this->redirectToRoute('app_users');
    }
}
