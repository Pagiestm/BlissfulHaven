<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\UtilisateursAdresses;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UtilisateurAdresseType;
use App\Repository\UtilisateursAdressesRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LivraisonController extends AbstractController
{

    /**
     * @Route("/panier/livraison", name="app_livraison")
     */
    public function livraison(UtilisateursAdressesRepository $utilisateuradresseRepo, Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $utilisateurAdresses = $utilisateuradresseRepo->findby(['user'=>$user]);

        $utilisateurAdresse = new UtilisateursAdresses();
        $form = $this->createForm(UtilisateurAdresseType::class, $utilisateurAdresse);

        $manager = $doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateurAdresse = $form->getData();
            $utilisateurAdresse->setUser($this->getUser()); // Ajout de l'utilisateur connecté
            $manager->persist($utilisateurAdresse);
            $manager->flush();

            return $this->redirectToRoute('app_livraison');
        }

        return $this->renderForm('panier/livraison.html.twig', [
            'utilisateurAdresse' => $form,
            "utilisateurAdresses" => $utilisateurAdresses,
        ]);
    }

    /**
     * @Route("/panier/livraison/validation", name="app_livraison_validation")
     */
    public function livraisonValidation(UtilisateursAdressesRepository $utilisateuradresseRepo, Request $request, SessionInterface $session): Response
    {
        $selectedAddressId = $request->request->get('selectedAddress');
        $selectedAddress = $utilisateuradresseRepo->find($selectedAddressId);
        if ($selectedAddress) {
            $session->set('selectedAddress', $selectedAddress);
        }

        return $this->redirectToRoute('app_validation_commande');
    }

    /**
     * @Route("/{id}/supprimer", name="supprimerAdresse")
     */
    public function supprimer(UtilisateursAdresses $adresse, ManagerRegistry $doctrine): Response
    {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($adresse);
            $entityManager->flush();

        return $this->redirectToRoute('app_livraison');
    }
}