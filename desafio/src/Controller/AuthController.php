<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthController extends AbstractController
{
    public function __construct(
        public UserPasswordHasherInterface $passwordHasher,
        public EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/registrar', name: 'registrar_usuario', methods: ['POST'])]
    public function register(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userName = $data['username'];
        $userPassword = $data['password'];
        $userEmail = $data['email'];
        $this->validFieldsRegister($userName, $userEmail, $userPassword);
        $this->isUsernameAndEmailAvailable($userName, $userEmail);
        try {
            $user = $this->cadasterUser($userName, $userEmail, $userPassword);
            return new JsonResponse([
                'message' => 'Usuário criado com sucesso.',
                'codUser' => $user->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    private function isUsernameAndEmailAvailable(string $userName, string $userEmail): void
    {
        $userWithUserNameOrEmail = $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => $userName,
            'email' => $userEmail
        ]);
        if ($userWithUserNameOrEmail) {
            throw new ValidationException('Já existe alguém com esse nome ou e-mail.');
        }
    }

    private function validFieldsRegister(string $userName, string $userEmail, string $userPassword): void
    {
        if (empty($userName) || empty($userEmail) || empty($userPassword)) {
            throw new ValidationException('Você não preencheu todos os campos.');
        }
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('O e-mail informado é inválido.');
        }
    }

    private function cadasterUser(string $userName, string $email, string $password): User
    {
        $user = new User();
        $user->setUsername($userName);
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    #[Route('/logar', name: 'logar', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userName = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $this->validateUsernameAndPassword($userName, $password);
        $user = $this->findUserByUsername($userName, $entityManager);
        $this->validatePassword($user, $password);
        return new JsonResponse(['message' => 'Logado com sucesso.'], Response::HTTP_OK);
    }

    private function validateUsernameAndPassword(string $username, string $password): void
    {
        if (empty($username) || empty($password)) {
            throw new ValidationException('Nome do usuário e senha são obrigatórios.');
        }
    }

    private function findUserByUsername(string $username, EntityManagerInterface $entityManager): ?User
    {
        return $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    private function validatePassword(User $user, string $password): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new ValidationException('Senha incorreta.');
        }
    }
}
