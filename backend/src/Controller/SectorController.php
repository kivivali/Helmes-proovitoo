<?php

namespace App\Controller;

use App\Repository\SectorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SectorController extends AbstractController
{
    #[Route('/api/sectors', methods: ['GET'])]
    public function list(SectorRepository $sectors): JsonResponse
    {
        return $this->json($sectors->findAsTree());
    }
}
