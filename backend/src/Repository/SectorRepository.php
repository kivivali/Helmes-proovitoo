<?php

namespace App\Repository;

use App\Entity\Sector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sector::class);
    }

    public function findAsTree(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.id, s.name, IDENTITY(s.parent) AS parentId')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $byId = [];
        foreach ($rows as $row) {
            $byId[$row['id']] = ['id' => $row['id'], 'name' => $row['name'], 'children' => []];
        }

        $roots = [];
        foreach ($rows as $row) {
            if ($row['parentId'] === null) {
                $roots[] = &$byId[$row['id']];
            } else {
                $byId[$row['parentId']]['children'][] = &$byId[$row['id']];
            }
        }

        return $roots;
    }
}
