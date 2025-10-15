<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Adresse;
use App\Entity\Blog;
use App\Entity\ShippingMethod;
use App\Repository\CommandeRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private CommandeRepository $orders) {}

    public function index(): Response
    {
        $now     = new \DateTimeImmutable('today');
        $since7  = $now->sub(new \DateInterval('P7D'));
        $since30 = $now->sub(new \DateInterval('P30D'));

        $metrics = [
            'orders_total'  => $this->orders->countAll(),
            'revenue_total' => $this->orders->sumTotalAll(),
            'revenue_7d'    => $this->orders->sumTotalSince($since7),
            'revenue_30d'   => $this->orders->sumTotalSince($since30),
            'by_status'     => $this->orders->countByStatus(),
        ];

        $latest = $this->orders->findLatest(10);

        // --- Courbe CA quotidien (30 jours) ---
        $ordersForChart = $this->orders->findSinceWithStatuses(
            $since30,
            ['paid', 'processing', 'shipped', 'delivered']
        );

        // Initialise chaque jour à 0
        $labels = [];
        $values = [];
        $map = []; // 'Y-m-d' => float total
        for ($i = 0; $i <= 30; $i++) {
            $d = $since30->add(new \DateInterval('P' . $i . 'D'));
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $map[$key] = 0.0;
        }

        // Agrège par jour
        foreach ($ordersForChart as $order) {
            /** @var \App\Entity\Commande $order */
            $k = $order->getCreatedAt()->format('Y-m-d');
            // total est mappé string DECIMAL => cast en float
            $map[$k] = ($map[$k] ?? 0) + (float) $order->getTotal();
        }

        // Remplit $values dans l’ordre des labels
        $cursor = \DateTimeImmutable::createFromInterface($since30);
        for ($i = 0; $i <= 30; $i++) {
            $k = $cursor->format('Y-m-d');
            $values[] = round($map[$k] ?? 0, 2);
            $cursor = $cursor->add(new \DateInterval('P1D'));
        }

        return $this->render('admin/dashboard.html.twig', [
            'metrics' => $metrics,
            'latest'  => $latest,
            'chart_labels' => $labels,
            'chart_values' => $values,
        ]);
    }


    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Ecommerce');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-receipt', \App\Entity\Commande::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-box', Produit::class);
        yield MenuItem::linkToCrud('Categories', 'fas fa-tags', Category::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Adresses', 'fas fa-home', Adresse::class);
        yield MenuItem::linkToCrud('Blog', 'fas fa-pen', Blog::class);
        yield MenuItem::linkToCrud('Livraisons', 'fa fa-truck', ShippingMethod::class);
    }
}
