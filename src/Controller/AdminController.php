<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commandes;
use App\Entity\Produits;
use App\Entity\Stocks;
use App\Entity\Media;
use App\Form\EditUserType;
use App\Form\EditCommandeType;
use App\Form\EditStocksType;
use App\Form\ProductType;
use App\Repository\UserRepository;
use App\Repository\CommandesRepository;
use App\Repository\StocksRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AdminController extends AbstractController
{

    /**
     * @IsGranted("ROLE_ADMIN")
     * Panel de l'administrateur
     * 
     * @Route("/admin", name="app_panel")
     */
    public function Panel(UserRepository $users)
    {
        return $this->render("admin/panel.html.twig", [
            'users' => $users->findAll()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Liste des utilisateurs du site 
     * 
     * @Route("/admin/utilisateurs", name="app_utilisateurs")
     */
    public function usersList(UserRepository $users)
    {
        return $this->render("admin/users.html.twig", [
            'users' => $users->findAll()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Modifier un utilisateur
     * 
     * @Route("/admin/modifierUtilisateur/{id}", name="app_modifierUtilisateurs")
     */
    public function editUser(User $user, Request $request, ManagerRegistry $doctrine)
    {
        $form = $this->createForm(EditUserType::class, $user);

        $manager = $doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setDateInscription(new \DateTime());
            $manager->persist($user);
            $manager->flush();

            $this->addFlash('message', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('app_utilisateurs');
        }

        return $this->render('admin/editUser.html.twig', [
            'userForm' => $form->createView()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Liste des commandes
     * 
     * @Route("/admin/commandes", name="app_commandes")
     */
    public function commandeList(commandesRepository $commandes)
    {
        return $this->render("admin/commandes.html.twig", [
            'commandes' => $commandes->findAll()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Modifier une Commande
     * 
     * @Route("/admin/modifierCommande/{id}", name="app_modifierCommandes")
     */
    public function editCommande(Commandes $commandes, Request $request, ManagerRegistry $doctrine)
    {
        $form = $this->createForm(EditCommandeType::class, $commandes);

        $manager = $doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandes = $form->getData();
            $manager->persist($commandes);
            $manager->flush();

            $this->addFlash('message', 'Commande modifié avec succès');
            return $this->redirectToRoute('app_commandes');
        }

        return $this->render('admin/editCommande.html.twig', [
            'commandeForm' => $form->createView()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Liste des stocks par magasins
     * 
     * @Route("/admin/stock", name="app_stock")
     */
    public function StockList(StocksRepository $stocks)
    {
        return $this->render("admin/stock.html.twig", [
            'stocks' => $stocks->findAll()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Modifier les stocks
     * 
     * @Route("/admin/modifierStock/{id}", name="app_modifierStock")
     */
    public function editStock(Stocks $stocks, Request $request, ManagerRegistry $doctrine)
    {
        $form = $this->createForm(EditStocksType::class, $stocks);

        $manager = $doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stocks = $form->getData();
            $manager->persist($stocks);
            $manager->flush();

            $this->addFlash('message', 'Stock modifié avec succès');
            return $this->redirectToRoute('app_stock');
        }

        return $this->render('admin/editStock.html.twig', [
            'stockForm' => $form->createView()
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * Créer un nouveau produit
     * 
     * @Route("/admin/creerProduit", name="app_creerProduit")
     */
    public function createProduct(Request $request, ManagerRegistry $doctrine)
    {
        $product = new Produits();

        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form['image']->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            
                // Get the current year
                $year = date('Y');
            
                // Create a new directory for the year if it doesn't exist
                $uploadDirectory = $this->getParameter('images_directory') . '/' . $year;
                if (!file_exists($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }
            
                try {
                    $file->move(
                        $uploadDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            
                // Create and set Media entity
                $media = new Media();
                $media->setPath($year . '/' . $newFilename); // Update the path to include the year
                $media->setAlt($newFilename);
                $media->setCategories($product->getCategorie()); // Set the category of the image to be the same as the product's category
                $product->setImage($media);
            }

            $manager = $doctrine->getManager();
            $manager->persist($product);
            $manager->flush();

            $stock = new Stocks();
            $quantite = $form['quantite']->getData();
            $stock->setQuantite($quantite);
            $stock->setProduit($product);
            $stock->setStockCritique($quantite <= 10); // Set stock_critique to true if quantity is 10 or less, false otherwise
            $stock->setMagasin($form['magasin']->getData());

            $manager->persist($stock);
            $manager->flush();

            $this->addFlash('message', 'Produit créé avec succès');
            return $this->redirectToRoute('app_produits');
        }

        return $this->render('admin/createProduct.html.twig', [
            'productForm' => $form->createView()
        ]);
    }
}
