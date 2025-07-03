<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\User;
use App\Entity\Employee;
use App\Entity\Appointment;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\CartItem;
use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des catégories de services (grandes familles)
        $serviceCategories = [
            'Coiffure' => [
                'Entretien du cheveu naturel' => [
                    'description' => 'Soins et entretien pour cheveux naturels',
                    'price' => 25.00,
                    'duration' => 60
                ],
                'Tresses sans rajouts' => [
                    'description' => 'Tresses traditionnelles sans extensions',
                    'price' => 35.00,
                    'duration' => 90
                ],
                'Tresses avec rajouts' => [
                    'description' => 'Tresses avec extensions naturelles',
                    'price' => 45.00,
                    'duration' => 120
                ],
                'Braids / twist' => [
                    'description' => 'Tresses africaines et twists',
                    'price' => 40.00,
                    'duration' => 100
                ],
                'Pose perruque (closure, frontal, tissage)' => [
                    'description' => 'Pose de perruques lace frontal et closure',
                    'price' => 80.00,
                    'duration' => 150
                ],
                'Chignon & événements' => [
                    'description' => 'Coiffures spéciales pour événements',
                    'price' => 60.00,
                    'duration' => 90
                ],
                'Coiffure homme' => [
                    'description' => 'Coupes et coiffures pour hommes',
                    'price' => 20.00,
                    'duration' => 45
                ],
                'Coiffure enfant' => [
                    'description' => 'Coiffures pour enfants',
                    'price' => 15.00,
                    'duration' => 30
                ]
            ],
            'Onglerie' => [
                'Manucure simple' => [
                    'description' => 'Manucure classique avec vernis',
                    'price' => 20.00,
                    'duration' => 45
                ],
                'Pose de gel' => [
                    'description' => 'Pose de gel coloré ou transparent',
                    'price' => 35.00,
                    'duration' => 75
                ],
                'Pose d\'acrylique' => [
                    'description' => 'Pose d\'ongles acryliques',
                    'price' => 45.00,
                    'duration' => 90
                ],
                'Pose capsule / chablon' => [
                    'description' => 'Pose avec capsules ou chablons',
                    'price' => 40.00,
                    'duration' => 80
                ],
                'Nail art & déco' => [
                    'description' => 'Décoration et nail art',
                    'price' => 15.00,
                    'duration' => 30
                ],
                'Remplissage / dépose' => [
                    'description' => 'Remplissage ou dépose complète',
                    'price' => 25.00,
                    'duration' => 60
                ]
            ],
            'Extension de cils' => [
                'Pose cil à cil' => [
                    'description' => 'Extension cils individuels',
                    'price' => 60.00,
                    'duration' => 120
                ],
                'Pose volume russe' => [
                    'description' => 'Extensions volume russe',
                    'price' => 80.00,
                    'duration' => 150
                ],
                'Pose mixte' => [
                    'description' => 'Mélange cils individuels et volume',
                    'price' => 70.00,
                    'duration' => 135
                ],
                'Retouche / dépose' => [
                    'description' => 'Retouche ou dépose complète',
                    'price' => 30.00,
                    'duration' => 60
                ]
            ],
            'Maquillage' => [
                'Makeup jour / soirée' => [
                    'description' => 'Maquillage pour journée ou soirée',
                    'price' => 45.00,
                    'duration' => 60
                ],
                'Makeup mariage' => [
                    'description' => 'Maquillage spécial mariage',
                    'price' => 80.00,
                    'duration' => 90
                ],
                'Makeup shooting' => [
                    'description' => 'Maquillage professionnel pour shooting',
                    'price' => 60.00,
                    'duration' => 75
                ]
            ],
            'Massage & Head Spa' => [
                'Massage relaxant' => [
                    'description' => 'Massage relaxant du cuir chevelu',
                    'price' => 35.00,
                    'duration' => 45
                ],
                'Massage tonifiant' => [
                    'description' => 'Massage tonifiant et stimulant',
                    'price' => 40.00,
                    'duration' => 50
                ],
                'Head Spa' => [
                    'description' => 'Soin complet tête et cuir chevelu',
                    'price' => 50.00,
                    'duration' => 75
                ]
            ],
            'Pose de perruques' => [
                'Pose lace frontal, closure, tissage' => [
                    'description' => 'Pose professionnelle de perruques',
                    'price' => 100.00,
                    'duration' => 180
                ],
                'Customisation' => [
                    'description' => 'Personnalisation de perruques',
                    'price' => 30.00,
                    'duration' => 60
                ],
                'Dépose & nettoyage' => [
                    'description' => 'Dépose et nettoyage de perruques',
                    'price' => 25.00,
                    'duration' => 45
                ],
                'Retouche / recoiffage' => [
                    'description' => 'Retouche et recoiffage de perruques',
                    'price' => 20.00,
                    'duration' => 30
                ]
            ]
        ];

        $serviceImages = [
            'Coiffure' => [
                'https://images.pexels.com/photos/32807304/pexels-photo-32807304.jpeg', 'https://images.pexels.com/photos/32722100/pexels-photo-32722100.jpeg', 'https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg', 'https://images.pexels.com/photos/973403/pexels-photo-973403.jpeg',
                'https://images.pexels.com/photos/31818415/pexels-photo-31818415.jpeg', 'https://images.pexels.com/photos/7577050/pexels-photo-7577050.jpeg', 'https://images.pexels.com/photos/6606914/pexels-photo-6606914.jpeg', 'https://images.pexels.com/photos/23350857/pexels-photo-23350857.jpeg'
            ],
            'Onglerie' => [
                'https://images.pexels.com/photos/887352/pexels-photo-887352.jpeg', 'https://images.pexels.com/photos/851219/pexels-photo-851219.jpeg', 'https://images.pexels.com/photos/994173/pexels-photo-994173.jpeg', 'https://images.pexels.com/photos/332046/pexels-photo-332046.jpeg',
                'https://images.pexels.com/photos/332046/pexels-photo-332046.jpeg', 'https://images.pexels.com/photos/4210657/pexels-photo-4210657.jpeg', 'https://images.pexels.com/photos/2701767/pexels-photo-2701767.jpeg'
            ],
            'Extension de cils' => [
                'cils-1.jpg', 'cils-2.jpg', 'volume-russe-1.jpg', 'mixte-1.jpg',
                'retouche-cils-1.jpg'
            ],
            'Maquillage' => [
                'https://images.pexels.com/photos/5128235/pexels-photo-5128235.jpeg', 'https://images.pexels.com/photos/20765765/pexels-photo-20765765.jpeg', 'https://images.pexels.com/photos/247287/pexels-photo-247287.jpeg', 'https://images.pexels.com/photos/1523528/pexels-photo-1523528.jpeg',
                'https://images.pexels.com/photos/1910051/pexels-photo-1910051.jpeg'
            ],
            'Massage & Head Spa' => [
                'massage-1.jpg', 'massage-2.jpg', 'head-spa-1.jpg', 'relaxant-1.jpg'
            ],
            'Pose de perruques' => [
                'https://images.pexels.com/photos/2703039/pexels-photo-2703039.jpeg', 'https://images.pexels.com/photos/19190390/pexels-photo-19190390.jpeg', 'https://images.pexels.com/photos/2085528/pexels-photo-2085528.jpeg',
                'https://images.pexels.com/photos/4724468/pexels-photo-4724468.jpeg', 'https://images.pexels.com/photos/13221801/pexels-photo-13221801.jpeg'
            ]
        ];

        $serviceCategoryEntities = [];
        foreach ($serviceCategories as $categoryName => $services) {
            $category = new ServiceCategory();
            $category->setName($categoryName);
            $category->setDescription('Catégorie ' . $categoryName);
            $category->setIsActive(true);
            $manager->persist($category);
            $serviceCategoryEntities[$categoryName] = $category;

            foreach ($services as $serviceName => $serviceData) {
                $service = new Service();
                $service->setName($serviceName);
                $service->setDescription($serviceData['description']);
                $service->setPrice((string) $serviceData['price']);
                $service->setDurationMinutes($serviceData['duration']);
                $service->setCategory($category);
                $service->setIsActive(true);
                
                if (isset($serviceImages[$categoryName])) {
                    $randomImage = $serviceImages[$categoryName][array_rand($serviceImages[$categoryName])];
                    $service->setImage($randomImage);
                }
                
                $manager->persist($service);
            }
        }

        $productCategories = [
            'Perruques' => [
                'Lace frontal' => [
                    'description' => 'Perruques lace frontal de qualité',
                    'price' => 150.00,
                    'stock' => 20
                ],
                'Closure' => [
                    'description' => 'Perruques closure naturelles',
                    'price' => 120.00,
                    'stock' => 15
                ],
                'Perruques customisées' => [
                    'description' => 'Perruques personnalisées sur mesure',
                    'price' => 200.00,
                    'stock' => 10
                ],
                'Perruques glueless' => [
                    'description' => 'Perruques sans colle, clips intégrés',
                    'price' => 180.00,
                    'stock' => 12
                ],
                'Perruques synthétiques' => [
                    'description' => 'Perruques synthétiques abordables',
                    'price' => 80.00,
                    'stock' => 25
                ]
            ],
            'Produits capillaires' => [
                'Huiles et sérums' => [
                    'description' => 'Huiles et sérums pour soins capillaires',
                    'price' => 25.00,
                    'stock' => 50
                ],
                'Shampooings & après-shampooings' => [
                    'description' => 'Produits de lavage et soins',
                    'price' => 18.00,
                    'stock' => 40
                ],
                'Masques capillaires' => [
                    'description' => 'Masques nourrissants et réparateurs',
                    'price' => 22.00,
                    'stock' => 35
                ],
                'Gels & mousses coiffantes' => [
                    'description' => 'Produits de coiffage et fixation',
                    'price' => 15.00,
                    'stock' => 45
                ],
                'Produits pour cheveux naturels / crépus / bouclés' => [
                    'description' => 'Spécialement formulés pour cheveux naturels',
                    'price' => 28.00,
                    'stock' => 30
                ]
            ]
        ];

        $productCategoryEntities = [];
        foreach ($productCategories as $categoryName => $products) {
            $category = new ProductCategory();
            $category->setName($categoryName);
            $category->setDescription('Catégorie ' . $categoryName);
            $category->setIsActive(true);
            $manager->persist($category);
            $productCategoryEntities[$categoryName] = $category;

            foreach ($products as $productName => $productData) {
                $product = new Product();
                $product->setName($productName);
                $product->setDescription($productData['description']);
                $product->setPrice((string) $productData['price']);
                $product->setStockQuantity($productData['stock']);
                $product->setSku('SKU-' . strtoupper(str_replace(' ', '-', $productName)));
                $product->setCategory($category);
                $product->setIsActive(true);
                $manager->persist($product);
            }
        }

        $admin = new User();
        $admin->setFirstName('Admin');
        $admin->setLastName('Glowly');
        $admin->setEmail('admin@salon.fr');
        $admin->setPhone('+33 1 23 45 67 89');
        $admin->setIsActive(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $employeesData = [
            ['Marie', 'Dubois', 'marie@glowly.fr', '+33 1 23 45 67 90', 'Coiffure, Tresses, Maquillage'],
            ['Sophie', 'Martin', 'sophie@glowly.fr', '+33 1 23 45 67 91', 'Onglerie, Extension de cils'],
            ['Emma', 'Bernard', 'emma@glowly.fr', '+33 1 23 45 67 92', 'Coiffure, Pose de perruques'],
            ['Léa', 'Petit', 'lea@glowly.fr', '+33 1 23 45 67 93', 'Maquillage, Massage & Head Spa'],
            ['Chloé', 'Robert', 'chloe@glowly.fr', '+33 1 23 45 67 94', 'Coiffure, Onglerie'],
            ['Jade', 'Richard', 'jade@glowly.fr', '+33 1 23 45 67 95', 'Extension de cils, Maquillage']
        ];

        $employeeEntities = [];
        foreach ($employeesData as $employeeData) {
            $employeeUser = new User();
            $employeeUser->setFirstName($employeeData[0]);
            $employeeUser->setLastName($employeeData[1]);
            $employeeUser->setEmail($employeeData[2]);
            $employeeUser->setPhone($employeeData[3]);
            $employeeUser->setIsActive(true);
            $employeeUser->setRoles(['ROLE_EMPLOYEE']);
            $employeeUser->setPassword($this->passwordHasher->hashPassword($employeeUser, 'employee123'));
            $manager->persist($employeeUser);

            $employee = new Employee();
            $employee->setUser($employeeUser);
            $employee->setSpecialties($employeeData[4]);
            $employee->setIsAvailable(true);
            $manager->persist($employee);
            $employeeEntities[] = $employee;
        }

        $clients = [
            ['Sophie', 'Martin', 'sophie.martin@email.com', '+33 6 12 34 56 78'],
            ['Emma', 'Bernard', 'emma.bernard@email.com', '+33 6 23 45 67 89'],
            ['Léa', 'Petit', 'lea.petit@email.com', '+33 6 34 56 78 90'],
            ['Chloé', 'Robert', 'chloe.robert@email.com', '+33 6 45 67 89 01'],
            ['Jade', 'Richard', 'jade.richard@email.com', '+33 6 56 78 90 12'],
            ['Alice', 'Durand', 'alice.durand@email.com', '+33 6 67 89 01 23'],
            ['Camille', 'Moreau', 'camille.moreau@email.com', '+33 6 78 90 12 34'],
            ['Sarah', 'Simon', 'sarah.simon@email.com', '+33 6 89 01 23 45'],
            ['Julie', 'Michel', 'julie.michel@email.com', '+33 6 90 12 34 56'],
            ['Manon', 'Leroy', 'manon.leroy@email.com', '+33 6 01 23 45 67'],
            ['Clara', 'Roux', 'clara.roux@email.com', '+33 6 12 34 56 78'],
            ['Inès', 'David', 'ines.david@email.com', '+33 6 23 45 67 89'],
            ['Louise', 'Bertrand', 'louise.bertrand@email.com', '+33 6 34 56 78 90'],
            ['Eva', 'Rivière', 'eva.riviere@email.com', '+33 6 45 67 89 01'],
            ['Zoé', 'Blanc', 'zoe.blanc@email.com', '+33 6 56 78 90 12'],
            ['Nina', 'Guerin', 'nina.guerin@email.com', '+33 6 67 89 01 23'],
            ['Lola', 'Boyer', 'lola.boyer@email.com', '+33 6 78 90 12 34'],
            ['Mia', 'Garnier', 'mia.garnier@email.com', '+33 6 89 01 23 45'],
            ['Emy', 'Chevalier', 'emy.chevalier@email.com', '+33 6 90 12 34 56'],
            ['Anaïs', 'Denis', 'anais.denis@email.com', '+33 6 01 23 45 67']
        ];

        $clientEntities = [];
        foreach ($clients as $clientData) {
            $client = new User();
            $client->setFirstName($clientData[0]);
            $client->setLastName($clientData[1]);
            $client->setEmail($clientData[2]);
            $client->setPhone($clientData[3]);
            $client->setIsActive(true);
            $client->setRoles(['ROLE_USER']);
            $client->setPassword($this->passwordHasher->hashPassword($client, 'client123'));
            $manager->persist($client);
            $clientEntities[] = $client;
        }

        $services = $manager->getRepository(Service::class)->findAll();
        if (!empty($services)) {
            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $notes = [
                'Rendez-vous régulier',
                'Première visite',
                'Retouche',
                'Soin spécial',
                'Événement important',
                'Consultation',
                'Soin de maintenance',
                'Rendez-vous d\'urgence',
                'Soin complet',
                'Retouche rapide'
            ];

            for ($i = 0; $i < 50; $i++) {
                $appointment = new Appointment();
                $appointment->setClient($clientEntities[array_rand($clientEntities)]);
                $appointment->setEmployee($employeeEntities[array_rand($employeeEntities)]);
                $appointment->setService($services[array_rand($services)]);
                
                $daysOffset = rand(-30, 60);
                $appointment->setAppointmentDate(new \DateTime('+' . $daysOffset . ' days'));
                
                $appointment->setStatus($statuses[array_rand($statuses)]);
                $appointment->setNotes($notes[array_rand($notes)] . ' #' . ($i + 1));
                $manager->persist($appointment);
            }
        }

        $products = $manager->getRepository(Product::class)->findAll();
        if (!empty($products)) {
            $orderStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            $addresses = [
                [
                    'name' => 'Sophie Martin',
                    'address' => '123 Rue de la Beauté',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'France'
                ],
                [
                    'name' => 'Emma Bernard',
                    'address' => '456 Avenue des Champs',
                    'city' => 'Lyon',
                    'postal_code' => '69001',
                    'country' => 'France'
                ],
                [
                    'name' => 'Léa Petit',
                    'address' => '789 Boulevard Central',
                    'city' => 'Marseille',
                    'postal_code' => '13001',
                    'country' => 'France'
                ],
                [
                    'name' => 'Chloé Robert',
                    'address' => '321 Rue du Commerce',
                    'city' => 'Toulouse',
                    'postal_code' => '31000',
                    'country' => 'France'
                ],
                [
                    'name' => 'Jade Richard',
                    'address' => '654 Place de la République',
                    'city' => 'Nantes',
                    'postal_code' => '44000',
                    'country' => 'France'
                ]
            ];

            for ($i = 0; $i < 30; $i++) {
                $order = new Order();
                $order->setUser($clientEntities[array_rand($clientEntities)]);
                $order->setOrderNumber('CMD' . str_pad($i + 1, 4, '0', STR_PAD_LEFT));
                $order->setStatus($orderStatuses[array_rand($orderStatuses)]);
                $order->setTotalAmount('0');
                $order->setSubtotal('0');
                $order->setTaxAmount('0');
                $order->setShippingCost('0');
                $order->setShippingAddress($addresses[array_rand($addresses)]);
                $order->setCreatedAt(new \DateTime('-' . rand(1, 90) . ' days'));
                $manager->persist($order);

                $orderItems = rand(1, 4);
                $totalAmount = 0;
                for ($j = 0; $j < $orderItems; $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 3);
                    $price = (float) $product->getPrice();

                    $orderItem = new OrderItem();
                    $orderItem->setOrder($order);
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($quantity);
                    $orderItem->setUnitPrice((string) $price);
                    $manager->persist($orderItem);

                    $totalAmount += $price * $quantity;
                }
                $order->setTotalAmount((string) $totalAmount);
                $order->setSubtotal((string) $totalAmount);
            }
        }

        $settings = [
            'salon_name' => 'Salon de Beauté Glowly',
            'salon_address' => '123 Rue de la Beauté, 75001 Paris',
            'salon_phone' => '+33 1 23 45 67 89',
            'salon_email' => 'contact@glowly.fr',
            'opening_hours_monday' => '09:00-18:00',
            'opening_hours_tuesday' => '09:00-18:00',
            'opening_hours_wednesday' => '09:00-18:00',
            'opening_hours_thursday' => '09:00-18:00',
            'opening_hours_friday' => '09:00-18:00',
            'opening_hours_saturday' => '09:00-17:00',
            'opening_hours_sunday' => 'Fermé',
            'promo_mode_enabled' => '0',
            'promo_discount_percentage' => '10',
            'promo_message' => 'Promotion en cours !',
            'email_confirmation_enabled' => '1',
            'email_reminder_enabled' => '1',
            'email_reminder_hours' => '24',
            'email_newsletter_enabled' => '1'
        ];

        foreach ($settings as $key => $value) {
            $setting = new Setting();
            $setting->setKey($key);
            $setting->setValue($value);
            $manager->persist($setting);
        }

        $manager->flush();
    }
}
