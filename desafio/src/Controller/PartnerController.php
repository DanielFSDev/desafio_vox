<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ParternerController extends AbstractController
{
    #[Route('/empresa/{id}/parterner', methods: ['GET'])]
    public function getParterners(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $company = $entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw $this->createNotFoundException('Empresa não encontrada.');
        }
        return new JsonResponse(['parterners' => $company->getPartners()->toArray()], Response::HTTP_OK);
    }

    #[Route('/empresa/{id}/parterner', methods: ['POST'])]
    public function addParterner(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $company = $entityManager->getRepository(Company::class)->find($id);
        if (!$company) {
            throw $this->createNotFoundException('Empresa não encontrada.');
        }
        $data = json_decode($request->getContent(), true);
        $codPartner = $data['codPartner'];
        $partner = $entityManager->getRepository(User::class)->find($codPartner);
        if (!$partner) {
            throw $this->createNotFoundException('Não foi encontrado esse usuário.');
        }
        $partnersCompany = $company->getPartners();
        foreach ($partnersCompany as $companyPartner) {
            if ($companyPartner->getId() === $partner->getId()) {
                throw $this->createNotFoundException('Sócio já está vínculado com a empresa.');
            }
        }
        if ($company->getOwner()->getId() === $partner->getId()) {
            throw $this->createNotFoundException('Esse sócio é o dono da empresa.');
        }
        $company->addPartner($partner);
        $entityManager->flush();
        return new JsonResponse(['Sócio adicionado com sucesso: ', $partner->getId()], Response::HTTP_OK);
    }
}