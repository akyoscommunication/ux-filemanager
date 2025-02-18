<?php

namespace Akyos\UXFileManager\Repository;

use Akyos\UXFileManager\Entity\File;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function save(File $file, bool $flush = false): void
    {
        $this->getEntityManager()->persist($file);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(File $file, bool $flush = false): void
    {
        $this->getEntityManager()->remove($file);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findFileInDirectory(string $path)
    {
        $qb = $this->createQueryBuilder('f');

        return $qb
            ->andWhere(
                $qb->expr()->like('f.path', ':path')
            )
            ->setParameter('path', $path.'%')
        ;
    }
}
