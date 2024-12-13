<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    public function __construct(public EntityManagerInterface $entityManager) {}

    #[Route('/empresa', methods: ['POST'])]
    public function company(
        Request $request
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $address = $data['address'] ?? null;
        $id_user = $data['id_user'] ?? null;
        $this->validateFields($name, $address, $id_user);
        $user = $this->validateUserExistence($id_user);
        $this->validateCompanyNameUnique($name);
        try {
            $company = new Company();
            $company->setName($name);
            $company->setAddress($address);
            $company->setOwner($user);
            $this->entityManager->persist($company);
            $this->entityManager->flush();
            return new JsonResponse(
                [
                    'id_empresa' => $company->getId(),
                    'message' => 'Empresa cadastrada com sucesso.'
                ], Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    public function validateFields(?string $name, ?string $address, ?int $id_user): void
    {
        if (!$name || !$address || !$id_user) {
            throw new ValidationException('Nome, email, usuário e endereço são obrigatórios.');
        }
    }

    public function validateUserExistence(int $id_user): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($id_user);
        if (!$user) {
            throw new ValidationException('Usuário não encontrado.');
        }
        return $user;
    }

    public function validateCompanyNameUnique(string $name): void
    {
        $company = $this->entityManager->getRepository(Company::class)->findOneBy(['name' => $name]);
        if ($company) {
            throw new ValidationException('Já existe uma empresa com esse nome.');
        }
    }

    #[Route('/empresa/{id}', methods: ['DELETE'])]
    public function deleteCompany(
        int $id
    ): JsonResponse {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        $this->validateCompanyExistence($id);
        try {
            $this->entityManager->remove($company);
            $this->entityManager->flush();
            return new JsonResponse(
                ['message' => 'Empresa excluída com sucesso.'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    public function validateCompanyExistence(int $id): void
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new ValidationException('Empresa não encontrada.');
        }
    }

    #[Route('/empresa/{id}', methods: ['GET'])]
    public function getCompany(
        int $id
    ): JsonResponse {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new ValidationException('Empresa não encontrada.');
        }
        return new JsonResponse([
            'name' => $company->getName(),
            'address' => $company->getAddress(),
            'owner' => $company->getOwner()->getUsername()
        ], Response::HTTP_OK);
    }

    #[Route('/empresa/{id}', methods: ['PUT'])]
    public function updateCompany(
        int $id,
        Request $request
    ): JsonResponse {
        $company = $this->entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw new ValidationException('Empresa não encontrada.');
        }
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $address = $data['address'] ?? null;
        if (!$name && !$address) {
            throw new ValidationException('Nenhum dado para atualizar.');
        }
        try {
            if ($name) {
                $company->setName($name);
            }
            if ($address) {
                $company->setAddress($address);
            }
            $this->entityManager->flush();
            return new JsonResponse(
                ['message' => 'Empresa atualizada com sucesso.'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }
}