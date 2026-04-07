<?php

namespace App\Tests\Controller;

use App\Entity\Adherent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MeControllerTest extends WebTestCase
{
    private function seedUserWithAdherent(EntityManagerInterface $em, $hasher): User
    {
        $adh = new Adherent();
        $adh->setNom('Test')->setPrenom('User')
            ->setDateNaissance(new \DateTime('2000-01-01'))
            ->setDateAdhesion(new \DateTime())
            ->setCeinture('blanche');
        $em->persist($adh);

        $u = new User();
        $u->setEmail('user-me@example.com')
            ->setRoles(['ROLE_USER'])
            ->setPassword($hasher->hashPassword($u, 'secret'))
            ->setApiToken('test-token-' . bin2hex(random_bytes(8)))
            ->setAdherent($adh);
        $em->persist($u);
        $em->flush();
        return $u;
    }

    public function testMeReturnsCurrentUser(): void
    {
        $client = static::createClient();
        $c = static::getContainer();
        $user = $this->seedUserWithAdherent(
            $c->get(EntityManagerInterface::class),
            $c->get('security.user_password_hasher')
        );

        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user->getApiToken(),
        ]);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user-me@example.com', $data['email']);
    }

    public function testUpdateAdherent(): void
    {
        $client = static::createClient();
        $c = static::getContainer();
        $user = $this->seedUserWithAdherent(
            $c->get(EntityManagerInterface::class),
            $c->get('security.user_password_hasher')
        );

        $client->request('PUT', '/api/me/adherent', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user->getApiToken(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['ceinture' => 'noire']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('noire', $data['ceinture']);
    }
}
