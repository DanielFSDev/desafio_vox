<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\CompanyUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class PartnerController extends AbstractController
{
    #[Route('/empresa/{id}/partner', methods: ['GET'])]
    public function getPartners(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $company = $entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw $this->createNotFoundException('Empresa não encontrada.');
        }
        return new JsonResponse(
            [
                'partners' => $company->getPartners()->map(fn($partner) => [
                    'id' => $partner->getId(),
                    'userName' => $partner->getUsername(),
                    'email' => $partner->getEmail(),
                ])->toArray()
            ], Response::HTTP_OK
        );
    }

    #[Route('/empresa/{id}/partner', methods: ['POST'])]
    public function addPartner(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $company = $entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw $this->createNotFoundException('Empresa não encontrada.');
        }
        $data = json_decode($request->getContent(), true);
        $codUser = $data['codUser'];
        $user = $entityManager->getRepository(User::class)->find($codUser);
        if (!$user) {
            throw $this->createNotFoundException('Não foi encontrado esse usuário.');
        }
        $partnersCompany = $company->getPartners();
        foreach ($partnersCompany as $companyPartner) {
            if ($companyPartner->getId() === $user->getId()) {
                throw $this->createNotFoundException('Sócio já está vínculado com a empresa.');
            }
        }
        if ($company->getOwner()->getId() === $user->getId()) {
            throw $this->createNotFoundException('Esse sócio é o dono da empresa.');
        }
        $companyUser = new CompanyUser();
        $companyUser->setUser($user);
        $companyUser->setCompany($company);
        $companyUser->setRole('Sócio');
        $entityManager->persist($companyUser);
        $entityManager->flush();
        return new JsonResponse(['Sócio adicionado com sucesso: ', $user->getId()], Response::HTTP_OK);
    }
}