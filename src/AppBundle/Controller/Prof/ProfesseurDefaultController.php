<?php

namespace AppBundle\Controller\Prof;

use AppBundle\Controller\Prof\Utils\UtilisateursVerification;
use AppBundle\Manager\SituationManager;
use AppBundle\Manager\StageManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProfesseurDefaultController extends Controller
{

    /**
     * @Route("/prof", name="prof_homepage")
     */
    public function indexAction()
    {
        return $this->render('prof/default/index.html.twig', array('name' => 'prof'));
    }
    /**
     * @Route("/utilisateurs", name="prof_utilisateurs")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function utilisateursAction(Request $request)
    {
        // Obtention du service Ldap
        $serviceLdap = $this->get('security.user.provider.concrete.ldap_provider');
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();
        // Obtention de l'utilisateur Ldap
        if (! $userLdap = $serviceLdap->loadUserLdapByLogin($user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }

        $classe = null;
        if ($request->getMethod() == 'POST') {

            // Récupération des infos
            $classe = $request->request->get('classeSearch');
            if ($classe == 'Toutes')
                $classe = null;
        }
        $canDelete = $userLdap->isInRole("ROLE_SUPER_ADMIN");

        // Chargement des utilisateurs
        $users = $serviceLdap->loadUsersByClasse($classe);
        // Stages
        $stageManager = new StageManager($this->get('doctrine')->getManager());
        $stages = $stageManager->loadAllStages();
        // Situations
        $situationManager = new SituationManager($this->get('doctrine')->getManager());
        $situations = $situationManager->loadAllSituations();

        // Liste des utilisateurs et de leurs stages/situations
        $utilisateursVerification = new UtilisateursVerification();
        // Répartition utilisateurs / Stages / Situations
        $utilisateursVerification->compute($users, $stages, $situations);


        return $this->render(':prof/default:utilisateurs.html.twig', array(
                'utilisateursVerification' => $utilisateursVerification,
                'classe' => $classe,
                'canDelete' => $canDelete)
        );
    }

    /**
     * @Route("/utilisateurs/delete", name="utilisateurs_delete")
     */
    public function deleteAction(Request $request) {
        // Si l'utilisateur appelle bien la suppresion en AJAX - POST
        // Récupération des infos
        $login = $request->request->get('login');

        if ($request->getMethod() == 'POST')
        {
            try
            {
                $entityManager = $this->get('doctrine')->getManager();
                $stmt = $entityManager
                    ->getConnection()
                    ->prepare('CALL deleteUtilisateur (:login);');
                $stmt->bindValue('login', $login);
                $stmt->execute();

                $message = "L'utilisateur a été supprimé";
                $status = 1;
            }
            catch (\Exception $exception)
            {
                $message = "L'appel de la méthode de suppression est incorrecte";
                $status = $id = -1;
            }
        }
        else
        {
            $message = "L'appel de la méthode de suppression est incorrecte";
            $status = $id = -1;
        }

        // Retour du résultat en Json
        return new JsonResponse(array('status' => $status, 'message' => $message, 'id' => $login));
    }
}
