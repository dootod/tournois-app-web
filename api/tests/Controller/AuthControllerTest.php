<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private function createAdmin(EntityManagerInterface $em, UserPasswordHasherInterface $h): User
    {
        $u = new User();
        $u->setEmail('test-admin@example.com');
        $u->setRoles(['ROLE_ADMIN']);
        $u->setPassword($h->hashPassword($u, 'secret'));
        $em->persist($u);
        $em->flush();
        return $u;
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com', 'password' => 'x']));
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get('security.user_password_hasher');
        $this->createAdmin($em, $hasher);

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test-admin@example.com', 'password' => 'secret']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testProtectedEndpointRequiresToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testUsersEndpointRequiresAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users');
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
