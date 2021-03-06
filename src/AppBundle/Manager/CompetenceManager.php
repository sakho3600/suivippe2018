<?php
/**
 * Created by PhpStorm.
 * User: jeanluc.bompard
 * Date: 07/02/2017
 * Time: 11:56
 */

namespace AppBundle\Manager;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Competence;
use AppBundle\Entity\CompetenceRepository;

class CompetenceManager
{
    protected $entityManager;
    /**
     * @var CompetenceRepository
     */
    protected $repository;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
        $this->repository = $em->getRepository('AppBundle:Competence');
    }

    /**
     * @return \AppBundle\Entity\Competence[]
     */
    public function loadAllCompetences()
    {
        return $this->repository->findAll();
    }

    /**
     * @return \AppBundle\Entity\Competence[]
     */
    public function loadCompetences($activiteId)
    {
        $entities = $this->repository->findAllByIdAsArray($activiteId);
        return $entities;
    }

}