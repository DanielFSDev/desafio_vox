<?php

namespace App\Controller;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}