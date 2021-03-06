<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Prof\Utils\UtilisateurStages;
use AppBundle\Form\TemplateProcessorImage;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use PhpOffice\PhpWord\TemplateProcessor;

use AppBundle\Manager\ActiviteManager;
use AppBundle\Entity\Stage;
use AppBundle\Manager\StageManager;
use AppBundle\Form\StageType;
use AppBundle\Form\WordResponse;

class StageController extends Controller
{
    private function getManager()
    {
        return new StageManager($this->get('doctrine')->getManager());
    }
    private function getActiviteManager()
    {
        return new ActiviteManager($this->get('doctrine')->getManager());
    }

    /**
     * @Route("/stage", name="stage_index")
     */
    public function indexAction()
    {
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();
        // Obtention des stages
        $stages = $this->getManager()->loadStages($user->getUsername());
        // Nombre de stages
        $nbStages = $this->getNbStages($user->getUsername());

        return $this->render('stage/index.html.twig', array("arrayStages" => $stages, 'nbStages' => $nbStages));
    }

    /**
     * @Route("/stage/count", name="stage_count")
     */
    public function countAction()
    {
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();
        $count = $this->getManager()->countStages($user->getUsername());

        return new JsonResponse(array('count' => $count));
    }

    /**
     * @Route("/stage/add", name="stage_add")
     */
    public function addAction(Request $request)
    {
        // Création d'un nouveau stage
        $stage = new Stage();

        // Création du modèle du formulaire qui est lié à l'entité stage
        $model = $this->get('form.factory')->create(StageType::class, $stage);
        // Obtention du manager
        $manager = $this->getManager();

        $user = $this->getUser();

        // Obtention de l'utilisateur Ldap
        $serviceLdap = $this->get('security.user.provider.concrete.ldap_provider');
        if (! $userLdap = $serviceLdap->loadUserLdapByLogin($user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }

        // Typologies
        $typologies = $manager->loadTypologies();
        // Nombre de stages
        $nbStages = $this->getNbStages($user->getUsername());

        // Si l'utilisateur soumet le formulaire
        if ($request->getMethod() == 'POST')
        {
            // Attachement du modèle à l'objet "request"
            $model->handleRequest($request);
            // Validation du modèle
            if ($model->isValid())
            {
                // Le login est celui de l'utilisateur connecté
                $stage->setLogin($userLdap->getLogin());
                // La date de modification
                $stage->setDatemodif(new \DateTime('now'));

                // Obtention du fichier
                /*$file = $stage->getEntrepriseLogo();
                if ($file) {
                    // Generate a unique name for the file before saving it
                    $fileName = 'logo'. $stage->getId() . '.' . $file->guessExtension();

                    // Move the file to the directory where brochures are stored
                    $file->move(
                        $this->getParameter('entrepriselogo_directory'),
                        $fileName
                    );

                    // Update the 'brochure' property to store the PDF file name
                    // instead of its contents
                    $stage->setEntrepriseLogo($fileName);
                }*/

                $typologies = $manager->loadTypologies();

                // Obtention des situations obligatoires: Typologie
                $mandatory = $request->request->get('mandatory');
                $manager->saveTypologies($stage, $mandatory);

                // Validation de l'entité
                $manager->saveStage($stage);

                return $this->redirectToRoute('stage_edit', array('id' => $stage->getId(),
                    'nbStages' => $nbStages,
                    'typologies' => $typologies));
            }
        }

        return $this->render('stage/add.html.twig', array('form' => $model->createView(),
                                'nbStages' => $nbStages,
                                'typologies' => $typologies));
    }

    /**
     * @Route("/stage/edit/{id}/{page}", defaults={"page" = 1},name="stage_edit")
     */
    public function editAction(Request $request, $id, $page)
    {
        // Obtention du manager
        $manager = $this->getManager();
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();
        // Recherche du stage
        if (!$stage = $manager->loadStage($id, $user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }

        // Création du modèle du formulaire
        $model = $this->get('form.factory')->create(StageType::class, $stage);
        // Obtention du manager
        $manager = $this->getManager();

        // Si l'utilisateur soumet le formulaire
        if ($request->getMethod() == 'POST')
        {
            // Conserver l'image actuelle
            $originalFileName = $stage->getEntrepriseLogo();

            // Attachement du modèle à l'objet "request"
            $model->handleRequest($request);
            // Validation du modèle
            if ($model->isValid())
            {
                // Obtention du fichier
                /*
                 * La génération du document Word avec l'image ne fonctionne pas
                 *
                $file = $stage->getEntrepriseLogo();
                if ($file) {
                    // Generate a unique name for the file before saving it
                    $fileName = 'logo'. $stage->getId() . '.' . $file->guessExtension();

                    // Move the file to the directory where images are stored
                    $file->move(
                        $this->getParameter('entrepriselogo_directory'),
                        $fileName
                    );

                    // Update the 'brochure' property to store the PDF file name
                    // instead of its contents
                    $stage->setEntrepriseLogo($fileName);
                }
                else {
                    $stage->setEntrepriseLogo($originalFileName);
                }*/

                // Obtention des situations obligatoires: Typologie
                $mandatory = $request->request->get('mandatory');
                $manager->saveTypologies($stage, $mandatory);

                // Validation de l'entité
                $manager->saveStage($stage);
            }
        }

        // Obtention des activités
        $intitulesActivites = $manager->loadStageIntitules($id);
        // Typologies
        $typologies = $manager->loadTypologies();

        $utilisateurStages = new UtilisateurStages();
        foreach ($intitulesActivites as $stagesIntitule) {
            $stage->addIntitulesActivites($stagesIntitule);
        }
        $utilisateurStages->addStage($stage);
        $recommandations = $utilisateurStages->verifierStage();

        // Nom duf fichier word
        $filename    = $this->getParameter('word_template_filename');

        return $this->render('stage/edit.html.twig', array(
            'form' => $model->createView(),
            'stage' => $stage,
            'page' => $page,
            'filename' => $filename,
            'intitulesActivites' => $intitulesActivites,
            'typologies' => $typologies,
            'recommandations' => $recommandations,
            ));
    }

    /**
     * @Route("/stage/delete", name="stage_delete")
     */
    public function deleteAction(Request $request) {
        // Si l'utilisateur appelle bien la suppresion en AJAX - POST
        // Récupération des infos
        $idStage = $request->request->get('idStage');

        if ($request->getMethod() == 'POST') {
            // Obtention du manager
            $manager = $this->getManager();
            // Obtention de l'utilisateur connecté
            $user = $this->getUser();
            // Recherche du stage
            if ($stage = $manager->loadStage($idStage, $user->getUsername())) {
                $message = "Le stage a été supprimé";
                $status = 1;
                // Suppression du film
                try {
                    $manager->removeStage($stage);
                } catch (\Exception $e) {
                    $message = sprintf("L'erreur suivante est survenue lors de la suppression du stage: %s",
                        $e->getMessage());
                    $status = -1;
                }
            } else {
                $message = "Le stage n'existe pas";
                $status = -1;
            }
        }
        else
        {
            $message = "L'appel de la méthode de suppression est incorrecte";
            $status = $id = -1;
        }

        // Retour du résultat en Json
        /*$response = new Response(json_encode(array('status' => $status, 'message' => $message, 'id' => $idStage)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;*/
        return new JsonResponse(array('status' => $status, 'message' => $message, 'id' => $idStage));
    }

    /**
     * @Route("/stage/addIntitule", name="stage_intitule_add")
     */
    public function addIntituleAction(Request $request) {

        $idStage = -1;
        $idIntitule = -1;

        // Si l'utilisateur appelle bien l'ajout en AJAX - POST
        if ($request->getMethod() == 'POST') {

            // Obtention du manager
            $manager = $this->getManager();

            // Récupération des infos
            $idStage = $request->request->get('idStage');
            $intitule = $request->request->get('intitule');
            // Obtention de l'utilisateur connecté
            $user = $this->getUser();

            // Recherche du stage
            if ($stage = $manager->loadStage($idStage, $user->getUsername()))
            {
                $message = "l'intitulé a été ajouté";
                $status = 1;

                // Création de l'intitulé
                try {
                    $idIntitule = $manager->addStageIntitule($idStage, $intitule);
                    if ($idIntitule == 0) {
                        $message = "Impossible d'ajouter l'intitulé";
                        $status = -1;
                    }
                } catch (\Exception $e) {
                    $message = sprintf("L'erreur suivante est survenue lors de l'ajout de l'intitulé: %s",
                        $e->getMessage());
                    $status = -1;
                }
            } else {
                $message = "L'intitulé n'existe pas";
                $status = -1;
            }
        }
        else
        {
            $message = "L'appel de la méthode de suppression est incorrecte";
            $status = $id = -1;
        }

        // Retour du résultat en Json
        return new JsonResponse(array('status' => $status, 'message' => $message,
                        'idStage' => $idStage, 'idIntitule' => $idIntitule, 'intitule' => $intitule));
    }

    /**
     * @Route("/stage/deleteIntitule", name="stage_intitule_delete")
     */
    public function deleteIntituleAction(Request $request) {
        // Récupération des infos
        $idStage = $request->request->get('idStage');
        $idIntitule = $request->request->get('idIntitule');

        $status = -1;
        // Si l'utilisateur appelle bien la suppresion en AJAX - POST
        if ($request->getMethod() == 'POST') {

            // Obtention du manager
            $manager = $this->getManager();
            // Recherche de l'intitulé
            if ($stageIntitule = $manager->loadStageIntitule($idStage, $idIntitule)) {
                $message = "L'intitulé a été supprimé";
                $status = 1;
                // Suppression du film
                try {
                    $manager->removeStageIntitule($stageIntitule);
                } catch (\Exception $e) {
                    $message = sprintf("L'erreur suivante est survenue lors de la suppression de l'intitulé: %s",
                        $e->getMessage());
                    $status = -1;
                }
            } else {
                $message = "L'intitulé n'existe pas";
                $status = -1;
            }
        }
        else
        {
            $message = "L'appel de la méthode de suppression est incorrecte";
            $status = $id = -1;
        }

        // Retour du résultat en Json
        /*$response = new Response(json_encode(array('status' => $status, 'message' => $message, 'idStage' => $idStage, 'idIntitule' => $idIntitule)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;*/
        return new JsonResponse(array('status' => $status, 'message' => $message, 'idStage' => $idStage, 'idIntitule' => $idIntitule));
    }

    /**
     * @Route("/stage/editIntitule/{id}/{idintitule}", name="stage_edit_intitule")
     */
    public function editIntituleAction(Request $request, $id, $idintitule)
    {
        // Obtention du manager
        $manager = $this->getManager();
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();

        // Obtention de l'utilisateur Ldap
        $serviceLdap = $this->get('security.user.provider.concrete.ldap_provider');
        if (! $userLdap = $serviceLdap->loadUserLdapByLogin($user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }

        // Recherche du stage
        if (!$stage = $manager->loadStage($id, $user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }
        // Recherche de l'intitule de ce stage
        if (!$intitule = $manager->loadStageIntitule($id, $idintitule))
        {
            return $this->render("Exception/error404.html.twig");
        }
        // Obtention de toutes les activités
        $activites = $this->getActiviteManager()->loadActivites();

        // Si l'utilisateur soumet le formulaire
        if ($request->getMethod() == 'POST')
        {
            // Obtention de l'intitulé
            $libelle = $request->request->get('intitule');
            $intitule->setIntitule($libelle);
            // Validation de l'entité
            $manager->saveStageIntitule($intitule);
        }

        return $this->render('stage/editintitule.html.twig', array('stage' => $stage, 'intitule' => $intitule,
            'activites' => $activites, 'idParcours' => $userLdap->getNumparcours()));
    }


    /**
     * @Route("/stage/addactivite", name="stage_addactivite")
     */
    public function addActiviteAction(Request $request)
    {
        $idActivite = 0;
        $status = -1;

        // Obtention de l'objet "request"
        //$request = $this->get('request');
        // Si l'utilisateur soumet le formulaire
        if ($request->getMethod() == 'POST')
        {
            // Récupération de l'ID du stage à supprimer
            $idActivite = $request->request->get('id');
            $idStage = $request->request->get('idstage');
            $idIntitule = $request->request->get('idintitule');

            // Obtention du manager
            $manager = $this->getManager();
            // Recherche du stage intitule
            if ($stage = $manager->loadStageIntitule($idStage, $idIntitule)) {
                $status = 1;
                // Ajout de l'activité
                try
                {
                    $manager->addStageActivite($idStage, $idIntitule, $idActivite);
                }
                catch (\Exception $e)
                {
                    $status = -1;
                }
            }

        }

        // Retour du résultat en Json
        return new JsonResponse(array('status' => $status, 'idActivite' => $idActivite));
    }

    /**
     * @Route("/stage/removeactivite", name="stage_removeactivite")
     */
    public function removeActiviteAction(Request $request)
    {
        $idActivite = 0;
        $status = -1;

        // Si l'utilisateur soumet le formulaire
        if ($request->getMethod() == 'POST')
        {
            // Récupération de l'ID du stage à supprimer
            $idActivite = $request->request->get('id');
            $idStage = $request->request->get('idstage');
            $idIntitule = $request->request->get('idintitule');

            // Obtention du manager
            $manager = $this->getManager();
            // Recherche du stage
            if ($stage = $manager->loadStageIntitule($idStage, $idIntitule)) {
                $status = 1;
                // Ajout de l'activité
                try
                {
                    $manager->removeStageActivite($idStage, $idIntitule, $idActivite);
                }
                catch (\Exception $e)
                {
                    $status = -1;
                }
            }

        }

        // Retour du résultat en Json
        return new JsonResponse(array('status' => $status, 'idActivite' => $idActivite));
    }



    /**
     * @Route("/stage/documentword/{id}", name="stage_document_word")
     */
    public function documentWordAction(Request $request, $id)
    {
        // Obtention du manager
        $manager = $this->getManager();
        // Obtention de l'utilisateur connecté
        $user = $this->getUser();

        // Obtention de l'utilisateur Ldap
        $serviceLdap = $this->get('security.user.provider.concrete.ldap_provider');
        if (! $userLdap = $serviceLdap->loadUserLdapByLogin($user->getUsername()))
        {
            return $this->render("Exception/error404.html.twig");
        }

        // Recherche du stage
        if (!$stage = $manager->loadStage($id, $userLdap->getUsername()))
        {
            return $this->render("TException/error404.html.twig");
        }

        // Obtention du template Attestation de stage
        $path = $this->getParameter('kernel.root_dir');
        $path .= "/Resources/word/";
        $filename    = $this->getParameter('word_template_filename');
        $templateProcessor = new TemplateProcessorImage($path . $filename);

        // On charge le valeurs dans le document
        /** VALEURS A RECUPERER **/
        $userNom = $userLdap->getNom();
        $userPrenom = $userLdap->getPrenom();
        $userMail = $userLdap->getMail();
        $userParcours = $userLdap->getParcoursLibelle();
        $templateProcessor->setValue('userNom', $userNom);
        $templateProcessor->setValue('userPrenom', $userPrenom);
        $templateProcessor->setValue('userMail', $userMail);
        $templateProcessor->setValue('userParcours', $userParcours);

        // RGPD ??
        $templateProcessor->setValue('userDateNaissance', '');
        $templateProcessor->setValue('userSexe', '');
        $templateProcessor->setValue('userAdresse', '');
        $userSexe = ($userLdap->getSexe()==2 ? "M" : "F");
        $templateProcessor->setValue('userSexe', $userSexe);
        /*
        $userDateNaissance = $utilisateur->getDateNaissance()->format("d/m/Y");
        $userAdresse = $utilisateur->getAdresse();
        */
        /*
        $templateProcessor->setValue('userDateNaissance', $userDateNaissance);
        $templateProcessor->setValue('userAdresse', $userAdresse);
        */

        // Entreprise
        $entrepriseNom = $stage->getEntreprisenom();
        $entrepriseAdresse = $stage->getEntrepriseadresse();

        $stageDateDebut = $stage->getDatedebut();
        $stageDateFin = $stage->getDatefin();
        $stageDuree = $stage->getDuree();
        $stageMontant = $stage->getMontant();
        /* Image
        $imageName = $stage->getEntrepriselogo();
        if ($imageName != null && !empty($imageName))
        {
            $imagePath = $this->getParameter('entrepriselogo_directory');
            $templateProcessor->setImage('entrepriseLogo', $imagePath . DIRECTORY_SEPARATOR . $imageName,
                                        $imageName, 100, 100);
        }*/
        $templateProcessor->setValue('entrepriseNom', $entrepriseNom);
        $templateProcessor->setValue('entrepriseAdresse', $entrepriseAdresse);
        $templateProcessor->setValue('stageDateDebut', $stageDateDebut->format("d/m/Y"));
        $templateProcessor->setValue('stageDateFin', $stageDateFin->format("d/m/Y"));
        $templateProcessor->setValue('stageDuree', $stageDuree);
        $templateProcessor->setValue('stageMontant', $stageMontant);

        $dateDuJour = new \DateTime("now");
        $templateProcessor->setValue('dateDuJour', $dateDuJour->format("d/m/Y"));

        // Obtention des activités
        $intitulesActivites = $manager->loadStageIntitules($id);
        $count = count($intitulesActivites);
        $templateProcessor->cloneRow('stageIntituleActivite', $count);

        $cpt = 1;
        foreach ($intitulesActivites as $intitulesActivite) {
            $templateProcessor->setValue('stageIntitule#'.$cpt, $intitulesActivite->getIntitule());

            $activitesChaine = "";
            foreach ($intitulesActivite->getIdactivite() as $activite) {
                $activitesChaine .= $activite->getNomenclature() . "-" . $activite->getLibelle()."</w:t><w:br/><w:t>";
            }
            $templateProcessor->setValue('stageIntituleActivite#'.$cpt, $activitesChaine);

            $cpt++;
        }

        return new WordResponse($templateProcessor, "attestation-stage-".$userNom . "-".$dateDuJour->format("Ymd").".docx");
    }

    private function getNbStages($login)
    {
        // Obtention des stages
        $stages = $this->getManager()->loadStages($login);
        return count($stages);

        /*$arrayStagesAnnees = array('stage1' => true, 'stage2' => true);
        $stage1 = 0;
        $stage2 = 0;
        // Obtention des stages
        $stages = $this->getManager()->loadStages($login);
        foreach ($stages as $stage)
        {
            if ($stage->getAnnee() == 1)
                $stage1 += 1;
            else
                $stage2 += 1;
        }

        $arrayStagesAnnees['stage1'] = ($stage1 < 2);
        $arrayStagesAnnees['stage2'] = ($stage2 < 2);
        if ($classe == "B1")
        {
            // Un B1 ne peut pas créer de stage de 2ème année
            $arrayStagesAnnees['stage2'] = false;
        }
        return $arrayStagesAnnees;*/
    }
    private function getListeStages($classe)
    {

        $arrayStagesAnnees = array('stage1' => true, 'stage2' => true);
        if ($classe == "B1")
        {
            // Un B1 ne peut pas créer de stage de 2ème année
            $arrayStagesAnnees['stage2'] = false;
        }
        return $arrayStagesAnnees;
    }
}
