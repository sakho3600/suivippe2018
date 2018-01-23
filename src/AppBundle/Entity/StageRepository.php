<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StageRepository extends EntityRepository
{

    public function countStages($login)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s)');
        $qb->where('s.login=:userLogin');

        return $qb
            ->getQuery()
            ->setParameter(":userLogin", $login)
            ->getSingleScalarResult();
    }
}
