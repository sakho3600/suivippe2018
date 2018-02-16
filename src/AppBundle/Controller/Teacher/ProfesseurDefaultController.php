<?php

namespace AppBundle\Controller\Teacher;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ProfesseurDefaultController extends Controller
{

    /**
     * @Route("/prof", name="prof_homepage")
     */
    public function indexAction()
    {
        return $this->render('prof/default/index.html.twig', array('name' => 'prof'));
    }
}
