<?php

namespace App\DataFixtures;

use App\Entity\Appointment;
use App\Entity\CartItem;
use App\Entity\Employee;
use App\Entity\EmployeeService;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Promotion;
use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Entity\Setting;
use App\Entity\User;
use App\Entity\UserSession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    private $faker;
    private $users = [];
    private $employees = [];
    private $serviceCategories = [];
    private $services = [];
    private $productCategories = [];
    private $products = [];

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer les utilisateurs
        $this->createUsers($manager);
        
        // 2. Créer les employés
        $this->createEmployees($manager);
        
        // 3. Créer les catégories de services
        $this->createServiceCategories($manager);
        
        // 4. Créer les services
        $this->createServices($manager);
        
        // 5. Créer les associations employé-service
        $this->createEmployeeServices($manager);
        
        // 6. Créer les catégories de produits
        $this->createProductCategories($manager);
        
        // 7. Créer les produits
        $this->createProducts($manager);
        
        // 8. Créer les rendez-vous
        $this->createAppointments($manager);
        
        // 9. Créer les commandes
        $this->createOrders($manager);
        
        // 10. Créer les paniers
        $this->createCartItems($manager);
        
        // 11. Créer les promotions
        $this->createPromotions($manager);
        
        // 12. Créer les notifications
        $this->createNotifications($manager);
        
        // 13. Créer les sessions utilisateur
        $this->createUserSessions($manager);
        
        // 14. Créer les paramètres
        $this->createSettings($manager);

        $manager->flush();
    }

    private function createUsers(ObjectManager $manager): void
    {
        // Créer un admin
        $admin = new User();
        $admin->setEmail('admin@salon.fr')
              ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'))
              ->setFirstName('Admin')
              ->setLastName('Système')
              ->setPhone('01 23 45 67 89')
              ->setAddress('123 Rue de l\'Administration, 75001 Paris')
              ->setRoles(['ROLE_ADMIN'])
              ->setIsActive(true);
        
        $manager->persist($admin);
        $this->users[] = $admin;

        // Créer des employés
        for ($i = 0; $i < 5; $i++) {
            $employee = new User();
            $employee->setEmail($this->faker->unique()->email)
                     ->setPassword($this->passwordHasher->hashPassword($employee, 'password123'))
                     ->setFirstName($this->faker->firstName)
                     ->setLastName($this->faker->lastName)
                     ->setPhone($this->faker->phoneNumber)
                     ->setAddress($this->faker->address)
                     ->setRoles(['ROLE_EMPLOYEE'])
                     ->setIsActive(true);
            
            $manager->persist($employee);
            $this->users[] = $employee;
        }

        // Créer des clients
        for ($i = 0; $i < 30; $i++) {
            $client = new User();
            $client->setEmail($this->faker->unique()->email)
                   ->setPassword($this->passwordHasher->hashPassword($client, 'password123'))
                   ->setFirstName($this->faker->firstName)
                   ->setLastName($this->faker->lastName)
                   ->setPhone($this->faker->phoneNumber)
                   ->setAddress($this->faker->address)
                   ->setRoles(['ROLE_USER'])
                   ->setIsActive($this->faker->boolean(90)); // 90% actifs
            
            $manager->persist($client);
            $this->users[] = $client;
        }
    }

    private function createEmployees(ObjectManager $manager): void
    {
        // Créer des profils employés pour les utilisateurs avec ROLE_EMPLOYEE
        $employeeUsers = array_filter($this->users, fn($user) => in_array('ROLE_EMPLOYEE', $user->getRoles()));
        
        foreach ($employeeUsers as $user) {
            $employee = new Employee();
            $employee->setUser($user)
                     ->setBio($this->faker->paragraph(3))
                     ->setSpecialties($this->faker->words(3, true))
                     ->setIsAvailable($this->faker->boolean(80))
                     ->setWorkingHours([
                         'monday' => ['start' => '09:00', 'end' => '18:00'],
                         'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                         'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                         'thursday' => ['start' => '09:00', 'end' => '18:00'],
                         'friday' => ['start' => '09:00', 'end' => '19:00'],
                         'saturday' => ['start' => '10:00', 'end' => '17:00'],
                         'sunday' => null
                     ]);
            
            $manager->persist($employee);
            $this->employees[] = $employee;
        }
    }

    private function createServiceCategories(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Coiffure', 'description' => 'Services de coiffure professionnels', 'icon' => 'scissors'],
            ['name' => 'Onglerie', 'description' => 'Soins des ongles et nail art', 'icon' => 'hand'],
            ['name' => 'Extension de cils', 'description' => 'Pose et retouche d\'extensions de cils', 'icon' => 'eye'],
            ['name' => 'Maquillage', 'description' => 'Maquillage professionnel pour tous événements', 'icon' => 'palette'],
            ['name' => 'Massage & Soins', 'description' => 'Massages et soins du cuir chevelu', 'icon' => 'spa'],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = new ServiceCategory();
            $category->setName($categoryData['name'])
                     ->setDescription($categoryData['description'])
                     ->setIcon($categoryData['icon'])
                     ->setIsActive(true)
                     ->setSortOrder($index + 1);
            
            $manager->persist($category);
            $this->serviceCategories[] = $category;
        }
    }

    private function createServices(ObjectManager $manager): void
    {
        $servicesData = [
            // Coiffure
            ['Coupe femme', 'Coupe de cheveux pour femme avec shampoing', 45.00, 60],
            ['Coupe homme', 'Coupe de cheveux pour homme', 25.00, 30],
            ['Coloration', 'Coloration complète des cheveux', 85.00, 120],
            ['Mèches', 'Pose de mèches', 65.00, 90],
            ['Brushing', 'Brushing professionnel', 30.00, 45],
            
            // Onglerie
            ['Manucure simple', 'Soin des ongles avec vernis', 25.00, 45],
            ['Pose gel', 'Pose de vernis gel semi-permanent', 35.00, 60],
            ['Nail art', 'Décoration artistique des ongles', 45.00, 75],
            ['Pédicure', 'Soin complet des pieds', 40.00, 60],
            
            // Extension de cils
            ['Pose volume', 'Pose d\'extensions volume russe', 80.00, 120],
            ['Pose classique', 'Pose d\'extensions cil à cil', 60.00, 90],
            ['Retouche', 'Retouche d\'extensions existantes', 40.00, 60],
            
            // Maquillage
            ['Maquillage jour', 'Maquillage naturel pour la journée', 35.00, 45],
            ['Maquillage soirée', 'Maquillage sophistiqué pour événement', 55.00, 60],
            ['Maquillage mariée', 'Maquillage complet pour mariage', 85.00, 90],
            
            // Massage & Soins
            ['Massage cuir chevelu', 'Massage relaxant du cuir chevelu', 30.00, 30],
            ['Soin hydratant', 'Soin hydratant intensif', 45.00, 45],
        ];

        foreach ($servicesData as $index => $serviceData) {
            $service = new Service();
            $categoryIndex = $index < 5 ? 0 : ($index < 9 ? 1 : ($index < 12 ? 2 : ($index < 15 ? 3 : 4)));
            
            $service->setCategory($this->serviceCategories[$categoryIndex])
                    ->setName($serviceData[0])
                    ->setDescription($serviceData[1])
                    ->setPrice((string) $serviceData[2])
                    ->setDurationMinutes($serviceData[3])
                    ->setIsActive(true);
            
            $manager->persist($service);
            $this->services[] = $service;
        }
    }

    private function createEmployeeServices(ObjectManager $manager): void
    {
        // Associer chaque employé à plusieurs services aléatoirement
        foreach ($this->employees as $employee) {
            $randomServices = $this->faker->randomElements($this->services, $this->faker->numberBetween(3, 8));
            
            foreach ($randomServices as $service) {
                $employeeService = new EmployeeService();
                $employeeService->setEmployee($employee)
                               ->setService($service);
                
                $manager->persist($employeeService);
            }
        }
    }

    private function createProductCategories(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Perruques', 'description' => 'Perruques naturelles et synthétiques'],
            ['name' => 'Soins capillaires', 'description' => 'Produits de soin pour tous types de cheveux'],
            ['name' => 'Accessoires', 'description' => 'Accessoires pour cheveux et coiffure'],
            ['name' => 'Maquillage', 'description' => 'Produits de maquillage professionnel'],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = new ProductCategory();
            $category->setName($categoryData['name'])
                     ->setDescription($categoryData['description'])
                     ->setIsActive(true)
                     ->setSortOrder($index + 1);
            
            $manager->persist($category);
            $this->productCategories[] = $category;
        }
    }

    private function createProducts(ObjectManager $manager): void
    {
        $productsData = [
            // Perruques
            ['Perruque Lace Front Naturelle', 'Perruque 100% cheveux naturels avec lace front', 150.00, 'PER001'],
            ['Perruque Bob Synthétique', 'Perruque synthétique coupe bob moderne', 45.00, 'PER002'],
            ['Perruque Longue Bouclée', 'Perruque longue avec boucles naturelles', 89.00, 'PER003'],
            
            // Soins capillaires
            ['Shampooing Hydratant', 'Shampooing pour cheveux secs et abîmés', 15.00, 'SOIN001'],
            ['Masque Réparateur', 'Masque intensif pour cheveux très abîmés', 25.00, 'SOIN002'],
            ['Huile Argan Bio', 'Huile d\'argan pure pour nourrir les cheveux', 18.00, 'SOIN003'],
            ['Sérum Anti-Frisottis', 'Sérum lissant pour contrôler les frisottis', 22.00, 'SOIN004'],
            
            // Accessoires
            ['Brosse Démêlante', 'Brosse spéciale pour démêler sans casser', 12.00, 'ACC001'],
            ['Élastiques Satin', 'Élastiques en satin pour protéger les cheveux', 8.00, 'ACC002'],
            ['Bonnet de Nuit Satin', 'Bonnet de protection nocturne en satin', 15.00, 'ACC003'],
            
            // Maquillage
            ['Palette Fards à Paupières', 'Palette 12 couleurs haute pigmentation', 35.00, 'MAQ001'],
            ['Fond de Teint Liquide', 'Fond de teint longue tenue toutes carnations', 28.00, 'MAQ002'],
            ['Rouge à Lèvres Mat', 'Rouge à lèvres mat longue durée', 16.00, 'MAQ003'],
        ];

        foreach ($productsData as $index => $productData) {
            $product = new Product();
            $categoryIndex = $index < 3 ? 0 : ($index < 7 ? 1 : ($index < 10 ? 2 : 3));
            
            $product->setCategory($this->productCategories[$categoryIndex])
                    ->setName($productData[0])
                    ->setDescription($productData[1])
                    ->setPrice((string) $productData[2])
                    ->setSku($productData[3])
                    ->setStockQuantity($this->faker->numberBetween(5, 50))
                    ->setImages([
                        '/images/products/' . strtolower(str_replace(' ', '-', $productData[0])) . '.jpg'
                    ])
                    ->setIsActive(true);
            
            $manager->persist($product);
            $this->products[] = $product;
        }
    }

    private function createAppointments(ObjectManager $manager): void
    {
        $clients = array_filter($this->users, fn($user) => in_array('ROLE_USER', $user->getRoles()));
        
        for ($i = 0; $i < 50; $i++) {
            $appointment = new Appointment();
            $client = $this->faker->randomElement($clients);
            $service = $this->faker->randomElement($this->services);
            $employee = $this->faker->randomElement($this->employees);
            
            // Dates aléatoires entre il y a 1 mois et dans 2 mois
            $appointmentDate = $this->faker->dateTimeBetween('-1 month', '+2 months');
            
            $appointment->setClient($client)
                        ->setService($service)
                        ->setEmployee($employee)
                        ->setAppointmentDate($appointmentDate)
                        ->setStatus($this->faker->randomElement([
                            Appointment::STATUS_PENDING,
                            Appointment::STATUS_CONFIRMED,
                            Appointment::STATUS_COMPLETED,
                            Appointment::STATUS_CANCELLED
                        ]))
                        ->setPrice($service->getPrice())
                        ->setNotes($this->faker->optional(0.3)->sentence());
            
            $manager->persist($appointment);
        }
    }

    private function createOrders(ObjectManager $manager): void
    {
        $clients = array_filter($this->users, fn($user) => in_array('ROLE_USER', $user->getRoles()));
        
        for ($i = 0; $i < 25; $i++) {
            $order = new Order();
            $client = $this->faker->randomElement($clients);
            
            $order->setUser($client)
                  ->setStatus($this->faker->randomElement([
                      Order::STATUS_PENDING,
                      Order::STATUS_PROCESSING,
                      Order::STATUS_SHIPPED,
                      Order::STATUS_DELIVERED,
                      Order::STATUS_CANCELLED
                  ]))
                  ->setShippingAddress([
                      'name' => $client->getFullName(),
                      'address' => $this->faker->streetAddress,
                      'city' => $this->faker->city,
                      'postal_code' => $this->faker->postcode,
                      'country' => 'France'
                  ])
                  ->setBillingAddress([
                      'name' => $client->getFullName(),
                      'address' => $this->faker->streetAddress,
                      'city' => $this->faker->city,
                      'postal_code' => $this->faker->postcode,
                      'country' => 'France'
                  ]);
            
            $manager->persist($order);
            
            // Créer des items pour cette commande
            $numItems = $this->faker->numberBetween(1, 5);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $this->faker->randomElement($this->products);
                $quantity = $this->faker->numberBetween(1, 3);
                $unitPrice = floatval($product->getPrice());
                
                $orderItem = new OrderItem();
                $orderItem->setOrder($order)
                          ->setProduct($product)
                          ->setQuantity($quantity)
                          ->setUnitPrice((string) $unitPrice)
                          ->setTotalPrice((string) ($unitPrice * $quantity));
                
                $subtotal += $unitPrice * $quantity;
                $manager->persist($orderItem);
            }
            
            $taxAmount = $subtotal * 0.20; // 20% TVA
            $shippingCost = $subtotal > 50 ? 0 : 5.90; // Gratuit si > 50€
            $totalAmount = $subtotal + $taxAmount + $shippingCost;
            
            $order->setSubtotal((string) $subtotal)
                  ->setTaxAmount((string) $taxAmount)
                  ->setShippingCost((string) $shippingCost)
                  ->setTotalAmount((string) $totalAmount);
        }
    }

    private function createCartItems(ObjectManager $manager): void
    {
        $clients = array_filter($this->users, fn($user) => in_array('ROLE_USER', $user->getRoles()));
        
        // Quelques clients ont des items dans leur panier
        $clientsWithCart = $this->faker->randomElements($clients, 8);
        
        foreach ($clientsWithCart as $client) {
            $numItems = $this->faker->numberBetween(1, 4);
            $productsInCart = $this->faker->randomElements($this->products, $numItems);
            
            foreach ($productsInCart as $product) {
                $cartItem = new CartItem();
                $cartItem->setUser($client)
                         ->setProduct($product)
                         ->setQuantity($this->faker->numberBetween(1, 3));
                
                $manager->persist($cartItem);
            }
        }
    }

    private function createPromotions(ObjectManager $manager): void
    {
        $promotions = [
            [
                'name' => 'Bienvenue 10%',
                'code' => 'WELCOME10',
                'type' => Promotion::TYPE_PERCENTAGE,
                'value' => 10,
                'minimum_amount' => 30,
                'start' => '-1 month',
                'end' => '+1 month'
            ],
            [
                'name' => 'Livraison gratuite',
                'code' => 'FREESHIP',
                'type' => Promotion::TYPE_FIXED_AMOUNT,
                'value' => 5.90,
                'minimum_amount' => 25,
                'start' => '-15 days',
                'end' => '+15 days'
            ],
            [
                'name' => 'Black Friday 25%',
                'code' => 'BLACK25',
                'type' => Promotion::TYPE_PERCENTAGE,
                'value' => 25,
                'minimum_amount' => 50,
                'start' => '+1 month',
                'end' => '+2 months'
            ]
        ];

        foreach ($promotions as $promoData) {
            $promotion = new Promotion();
            $promotion->setName($promoData['name'])
                      ->setCode($promoData['code'])
                      ->setType($promoData['type'])
                      ->setValue((string) $promoData['value'])
                      ->setMinimumAmount($promoData['minimum_amount'] ? (string) $promoData['minimum_amount'] : null)
                      ->setStartDate(new \DateTime($promoData['start']))
                      ->setEndDate(new \DateTime($promoData['end']))
                      ->setIsActive(true)
                      ->setUsageLimit($this->faker->optional(0.5)->numberBetween(10, 100))
                      ->setUsageCount($this->faker->numberBetween(0, 5));
            
            $manager->persist($promotion);
        }
    }

    private function createNotifications(ObjectManager $manager): void
    {
        $allUsers = $this->users;
        
        for ($i = 0; $i < 30; $i++) {
            $notification = new Notification();
            $user = $this->faker->randomElement($allUsers);
            
            $types = [
                Notification::TYPE_APPOINTMENT => [
                    'title' => 'Rappel rendez-vous',
                    'message' => 'Votre rendez-vous est prévu demain à {time}. Merci de confirmer votre présence.'
                ],
                Notification::TYPE_ORDER => [
                    'title' => 'Commande expédiée',
                    'message' => 'Votre commande #{orderNumber} a été expédiée et arrivera dans 2-3 jours.'
                ],
                Notification::TYPE_PROMOTION => [
                    'title' => 'Offre spéciale',
                    'message' => 'Profitez de -20% sur tous nos soins jusqu\'à la fin du mois !'
                ],
                Notification::TYPE_SYSTEM => [
                    'title' => 'Maintenance programmée',
                    'message' => 'Une maintenance aura lieu dimanche de 2h à 4h du matin.'
                ]
            ];
            
            $type = $this->faker->randomKey($types);
            $typeData = $types[$type];
            
            $notification->setUser($user)
                         ->setTitle($typeData['title'])
                         ->setMessage($typeData['message'])
                         ->setType($type)
                         ->setIsRead($this->faker->boolean(70)); // 70% des notifications sont lues
            
            $manager->persist($notification);
        }
    }

    private function createUserSessions(ObjectManager $manager): void
    {
        $activeUsers = array_filter($this->users, fn($user) => $user->isActive());
        
        // Quelques utilisateurs actifs ont des sessions
        $usersWithSessions = $this->faker->randomElements($activeUsers, 8);
        
        foreach ($usersWithSessions as $user) {
            $session = new UserSession();
            $session->setUser($user);
            
            $manager->persist($session);
        }
    }

    private function createSettings(ObjectManager $manager): void
    {
        $settings = [
            ['salon_name', 'Beauty Salon Deluxe', 'Nom du salon'],
            ['salon_phone', '01 23 45 67 89', 'Téléphone du salon'],
            ['salon_email', 'contact@salon.fr', 'Email de contact'],
            ['salon_address', '123 Rue de la Beauté, 75001 Paris', 'Adresse du salon'],
            ['opening_hours', json_encode([
                'monday' => '9:00-18:00',
                'tuesday' => '9:00-18:00',
                'wednesday' => '9:00-18:00',
                'thursday' => '9:00-18:00',
                'friday' => '9:00-19:00',
                'saturday' => '10:00-17:00',
                'sunday' => 'Fermé'
            ]), 'Horaires d\'ouverture'],
            ['appointment_reminder_hours', '24', 'Heures avant RDV pour envoyer un rappel'],
            ['free_shipping_threshold', '50', 'Montant minimum pour livraison gratuite'],
            ['tax_rate', '0.20', 'Taux de TVA'],
            ['appointment_duration_buffer', '15', 'Temps de battement entre RDV (minutes)'],
            ['maintenance_mode', '0', 'Mode maintenance activé'],
        ];

        foreach ($settings as $settingData) {
            $setting = new Setting();
            $setting->setKey($settingData[0])
                    ->setValue($settingData[1])
                    ->setDescription($settingData[2]);
            
            $manager->persist($setting);
        }
    }
}
