<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\SectorRepository;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubmissionController extends AbstractController
{
    #[Route('/api/submissions', methods: ['POST'])]
    public function save(
        Request $request,
        ValidatorInterface $validator,
        SectorRepository $sectors,
        SubmissionRepository $submissions,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $errors = $validator->validate($data, new Assert\Collection([
            'name' => new Assert\NotBlank(message: 'Name is required.'),
            'sectorIds' => [
                new Assert\NotNull(),
                new Assert\Count(min: 1, minMessage: 'Please select at least one sector.'),
            ],
            'agreeToTerms' => new Assert\IsTrue(message: 'You must agree to the terms.'),
        ]));

        if (count($errors) > 0) {
            $fieldErrors = [];
            foreach ($errors as $error) {
                $field = trim($error->getPropertyPath(), '[]');
                $fieldErrors[$field] = $error->getMessage();
            }
            return $this->json(['errors' => $fieldErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resolvedSectors = $sectors->findBy(['id' => $data['sectorIds']]);
        if (count($resolvedSectors) !== count(array_unique($data['sectorIds']))) {
            return $this->json(
                ['errors' => ['sectorIds' => 'One or more sector IDs are invalid.']],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $session = $request->getSession();
        $session->start();
        $sessionId = $session->getId();

        $submission = $submissions->findOneBySessionId($sessionId) ?? new Submission();
        $submission->setName($data['name']);
        $submission->setAgreeToTerms($data['agreeToTerms']);
        $submission->setSessionId($sessionId);
        $submission->setCreatedAt(new \DateTimeImmutable());

        // Replace sectors: clear old, add new.
        foreach ($submission->getSectors() as $old) {
            $submission->removeSector($old);
        }
        foreach ($resolvedSectors as $sector) {
            $submission->addSector($sector);
        }

        $em->persist($submission);
        $em->flush();

        return $this->json($this->toArray($submission), Response::HTTP_CREATED);
    }

    #[Route('/api/submissions/me', methods: ['GET'])]
    public function me(Request $request, SubmissionRepository $submissions): JsonResponse
    {
        $session = $request->getSession();
        $session->start();

        $submission = $submissions->findOneBySessionId($session->getId());

        if ($submission === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return $this->json($this->toArray($submission));
    }

    /** @return array{name: string, sectorIds: int[], agreeToTerms: bool} */
    private function toArray(Submission $submission): array
    {
        return [
            'name' => $submission->getName(),
            'sectorIds' => $submission->getSectors()->map(fn ($s) => $s->getId())->getValues(),
            'agreeToTerms' => $submission->isAgreeToTerms(),
        ];
    }
}
