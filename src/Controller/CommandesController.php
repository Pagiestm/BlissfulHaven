<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProduitsRepository;
use App\Repository\StocksRepository;
use App\Repository\UtilisateursAdressesRepository;
use App\Entity\Commandes;
use App\Repository\CommandesRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommandesController extends AbstractController
{

    /**
     * @Route("/panier/commande/validation", name="app_validation_commande")
     */
    public function validationCommande(SessionInterface $session, ProduitsRepository $produitRepo)
    {
        // Récupérez les données du panier à partir de la session
        $panier = $session->get("panier", []);

        // Récupérez les informations sur les produits du panier
        $dataPanier = [];
        $total = 0;
        $quantiteTotale = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepo->find($id);
            if (!$produit) {
                throw $this->createNotFoundException('Le produit demandé n\'existe pas');
            }

            // On ajoute les informations du produit dans $dataPanier
            $dataPanier[] = [
                "produit" => $produit,
                "quantite" => $quantite,
            ];

            $total += $produit->getPrix() * $quantite;

            // On ajoute la quantité du produit à la quantité totale
            $quantiteTotale += $quantite;
        }

        // Récupérer l'adresse sélectionnée à partir de la variable de session
        $selectedAddress = $session->get('selectedAddress');

        return $this->render('panier/validation_commande.html.twig', [
            'dataPanier' => $dataPanier,
            'total' => $total,
            'quantiteTotale' => $quantiteTotale,
            'selectedAddress' => $selectedAddress,
        ]);
    }

    /**
     * @Route("/create-checkout-session", name="create_checkout_session")
     */
    public function createCheckoutSession(SessionInterface $session, ProduitsRepository $produitRepo)
    {
        Stripe::setApiKey('sk_test_51Oj7TkFHB1pRascdzx9iYvWezJ4qdHh0uGNSz0EL8DXC1qqWONVWhJpzcYeKsYnJUDBChZLtrBJ894983uZwcDHc00v6kuegaP');

        $panier = $session->get("panier", []);
        $line_items = [];

        foreach ($panier as $id => $quantite) {
            $produit = $produitRepo->find($id);
            if (!$produit) {
                throw $this->createNotFoundException('Le produit demandé n\'existe pas');
            }

            $line_items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $produit->getNom(),
                    ],
                    'unit_amount' => $produit->getPrix() * 100, // Stripe attend le montant en centimes
                ],
                'quantity' => $quantite,
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    /**
     * @Route("/success", name="payment_success")
     */
    public function paymentSuccess(SessionInterface $session, ProduitsRepository $produitRepo, StocksRepository $stocksRepo, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UtilisateursAdressesRepository $addressRepo)
    {

        // Gérez le succès du paiement
        $panier = $session->get("panier", []);
        $dataPanier = [];
        $total = 0;
        $quantiteTotale = 0;

        $commande = new Commandes;

        // Sélectionner l'adresse de livraison
        $selectedAddressId = $session->get("selectedAddress");
        $selectedAddress = $addressRepo->find($selectedAddressId);

        // On boucle sur chaque produit du panier
        foreach ($panier as $id => $quantite) {
            $produit = $stocksRepo->find($id);
            if (!$produit) {
                throw $this->createNotFoundException('Le produit demandé n\'existe pas');
            }

            $stockDisponible = $produit->getQuantite();
            if ($stockDisponible < $quantite) {
                throw new \Exception("Il n'y a pas assez de stock disponible");
            }

            $produit->setQuantite($stockDisponible - $quantite);
            $produit->setStockCritique($produit->isStockCritique());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($produit);

            // On ajoute les informations du produit dans $dataPanier
            $dataPanier[] = [
                "produit" => $produit,
                "quantite" => $quantite,
            ];

            $produit = $produitRepo->find($id);
            // On ajoute le prix total pour tous les produits
            $total += $produit->getPrix() * $quantite;

            // On ajoute la quantité du produit à la quantité totale
            $quantiteTotale += $quantite;

            // On ajoute le produit à la commande
            $commande->addProduit($produit);
            $commande->setEtat("En cours")
                ->setUser($this->getUser())
                ->setTotal($total)
                ->setDate(new \DateTimeImmutable());
        }

        // On définit la quantité totale de tous les produits dans la commande
        $commande->setQuantite($quantiteTotale);

        // Set the delivery address for the order
        $commande->setAdresseLivraison($selectedAddress);

        // On persiste la commande
        $entityManager->persist($commande);
        $entityManager->flush();

        // On supprime ce qu'il y a dans le panier
        $session->set('panier', []);

        $this->addFlash('success', 'La commande a été transmise avec succès.');

        return $this->render('payment/success.html.twig');
    }

    /**
     * @Route("/cancel", name="payment_cancel")
     */
    public function paymentCancel()
    {
        // Gérez l'annulation du paiement ici
        return $this->render('payment/cancel.html.twig');
    }

    /**
     * @Route("/commandes", name="app_historiqueCommandes")
     */
    public function commandes(CommandesRepository $commandeRepos)
    {
        $user = $this->getUser();
        $commandes = $commandeRepos->findBy(['user' => $user]);

        return $this->render('historique/commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }
}
