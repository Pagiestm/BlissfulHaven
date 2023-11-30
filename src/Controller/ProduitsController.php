<?php

namespace App\Controller;

use App\Repository\ProduitsRepository;
use App\Repository\StocksRepository;
use App\Repository\CategoriesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ProduitsController extends AbstractController
{
    /**
     * @Route("/", name="app_produits")
     */
    public function produits(ProduitsRepository $produitRepo, CategoriesRepository $categorieRepo, StocksRepository $stockRepo, Request $request): Response
    {
        $categories = $categorieRepo->findAll();
        $produits = $produitRepo->findAll();
        $stocks = $stockRepo->findAll();

        $categorieId = $request->query->get('categorie');
        $selectedCategorie = null;

        if ($categorieId) {
            $selectedCategorie = $categorieRepo->find($categorieId);

            if (!$selectedCategorie) {
                throw $this->createNotFoundException('La catégorie demandée n\'existe pas');
            }

            $produits = $produitRepo->findBy(['categorie' => $selectedCategorie]);
        }

        return $this->render('produits/produits.html.twig', [
            "categories" => $categories,
            "produits" => $produits,
            "stocks" => $stocks,
            "selectedCategorie" => $selectedCategorie,
        ]);
    }

    /**
     * @Route("/presentation/{id}", name="app_presentation")
     */
    public function presentation(int $id = 1, ProduitsRepository $produitRepo): Response
    {

        $produit = $produitRepo->find($id);

        return $this->render('produits/presentation.html.twig', [
            'id' => $id,
            "produit" => $produit,
        ]);
    }

    /**
     * @Route("/produits", name="app_historiqueProduits")
     */
    public function adminProduits(ProduitsRepository $produitRepo)
    {
        $user = $this->getUser();
        $produits = $produitRepo->findBy(['user' => $user]);

        return $this->render('historique/produits.html.twig', [
            'produits' => $produits,
        ]);
    }
}
