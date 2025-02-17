<?php

namespace Akyos\UXFileManager\Repository;

use Akyos\UXFileManager\File;
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
        $this->_em->persist($file);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
