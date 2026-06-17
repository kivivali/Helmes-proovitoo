<?php

namespace App\Repository;

use App\Entity\Sector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sector>
 */
class SectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sector::class);
    }

    /**
     * Fetches every sector in one query, then builds the tree in PHP memory.
     * Returns only root nodes; children are nested under 'children'.
     * Returns plain arrays (not entities) to avoid serialization cycles.
     *
     * @return array<int, array{id: int, name: string, children: array}>
     */
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
