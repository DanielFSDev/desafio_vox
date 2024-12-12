<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse([
                'error' => 'Você não preencheu todos os campos.'
            ], Response::HTTP_BAD_REQUEST);
        }
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Já existe alguém com esse nome de usuário'
            ], Response::HTTP_CONFLICT);
        }
        try {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_USER']);
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();
            return new JsonResponse(['message' => 'User created successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        if (!$username || !$password) {
            return new JsonResponse(
                ['error' => 'Nome do usuário e senha são obrigatórios.'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            return new JsonResponse(
                ['error' => 'Conta não encontrada'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        if (!$passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(
                ['error' => 'Nome do usuário ou senha estão incorretos.'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        return new JsonResponse(['message' => 'Logado com sucesso.'], Response::HTTP_OK);
    }
}
