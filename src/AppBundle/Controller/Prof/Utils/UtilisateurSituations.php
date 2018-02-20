<?php
/**
 * Created by PhpStorm.
 * User: jeanlucbompard
 * Date: 15/02/18
 * Time: 12:07
 */

namespace AppBundle\Controller\Prof\Utils;


use AppBundle\Entity\Situation;
use AppBundle\Entity\Utilisateur;

class UtilisateurSituations
{
    /**
     * @var Situation[]
     */
    private $situations = array();
    /**
     * @var \AppBundle\Entity\Utilisateur
     */
    private $utilisateur;

    /**
     * @return Utilisateur
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }

    /**
     * @param Utilisateur $utilisateur
     */
    public function setUtilisateur(Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;
    }

    /**
     * @return \AppBundle\Entity\Situation[]
     */
    public function getSituations()
    {
        return $this->situations;
    }

    /**
     * @param $situations \AppBundle\Entity\Situation[]
     */
    public function setSituation($situations)
    {
        $this->situations = $situations;
    }

    /**
     * @param Situation $situation
     */
    public function addSituation(Situation $situation)
    {
        $this->situations[] = $situation;
    }

    public function verifierSituation()
    {
        $langages = array();
        $frameworks = array();
        $oss = array();
        $services = array();
        $activites = array();

        $nbE4 = 0;
        $id = 0;
        // Obtention du parcours
        $parcours = $this->getUtilisateur()->getNumparcours()->getNomenclature();

        $recommandations = null;
        if (count($this->situations) > 0) {
            $recommandations = array();
            foreach ($this->situations as $situation) {
                // Situation E4
                if ($situation->getRefe4())
                    $nbE4 += 1;
                // Nombre d'activité
                $activites[$situation->getReference()] = count($situation->getIdactivite());

                switch (trim($parcours)) {
                    case 'SLAM':
                        // Variété des frameworks et langages
                        if ($situation->getCodelangage()) {
                            $id = $situation->getCodelangage()->getId();
                            if (array_key_exists($id, $langages))
                                $langages[$id] += 1;
                            else
                                $langages[$id] = 0;

                        }
                        if ($situation->getCodeframework()) {

                            $id = $situation->getCodeframework()->getId();
                            if (array_key_exists($id, $frameworks))
                                $frameworks[$id] += 1;
                            else
                                $frameworks[$id] = 0;

                        }
                        break;

                    case 'SISR':
                        // Variété des OS et Services
                        if ($situation->getCodeos()) {
                            $id = $situation->getCodeos()->getId();
                            if (array_key_exists($id, $oss))
                                $oss[$id] += 1;
                            else
                                $oss[$id] = 0;

                        }
                        if ($situation->getCodeservice()) {
                            $id = $situation->getCodeservice()->getId();
                            if (array_key_exists($id, $services))
                                $services[$id] += 1;
                            else
                                $services[$id] = 0;

                        }
                        break;
                    default:
                        // Pas de recommendations
                        break;
                }
            }

            if ($this->getUtilisateur()->getClasse() == 'B1') {
                // Sinon aucune contrainte
                $nbE4 = 2;
            }

            // Situation E4
            if ($nbE4 < 2)
                $recommandations[] = "Vous devez avoir 2 situations E4";
            // Activités
            foreach ($activites as $activite) {
                if ($activite < 5) {
                    $recommandations[] = "Une situation doit avoir 5 activités minimum";
                    break;
                }
            }
            if (trim($parcours) == 'SLAM') {
                if (count($langages) < 2) {
                    $recommandations[] = "Vous devez avoir 2 langages différents";
                }
                if (count($frameworks) < 2) {
                    $recommandations[] = "Vous devez avoir 2 Framework différents";
                }
            }
            if (trim($parcours) == 'SISR') {
                if (count($oss) < 2) {
                    $recommandations[] = "Vous devez avoir 2 OS différents";
                }
                if (count($services) < 4) {
                    $recommandations[] = "Vous devez avoir 4 services différents";
                }
            }
        }

        return $recommandations;
    }
}