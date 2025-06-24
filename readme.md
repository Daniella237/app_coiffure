# 🏗️ Entités et Fixtures - Salon de Beauté

## 📋 Résumé des entités créées

Toutes les entités Symfony ont été créées selon le schéma de base de données fourni :

### 👥 **Entités Utilisateurs**
- **User** - Utilisateurs du système (clients, employés, admins)
- **Employee** - Profils employés avec spécialités et horaires
- **UserSession** - Sessions utilisateur pour la gestion des connexions

### 🎯 **Entités Services**
- **ServiceCategory** - Catégories de services (Coiffure, Onglerie, etc.)
- **Service** - Services proposés avec prix et durée
- **EmployeeService** - Association Many-to-Many entre employés et services

### 📅 **Entités Rendez-vous**
- **Appointment** - Rendez-vous avec statuts et rappels

### 🛍️ **Entités Produits & Commandes**
- **ProductCategory** - Catégories de produits
- **Product** - Produits avec stock et images
- **Order** - Commandes avec adresses et statuts
- **OrderItem** - Items de commande
- **CartItem** - Articles dans le panier

### 🎁 **Entités Marketing**
- **Promotion** - Codes promo avec types et limitations
- **Notification** - Notifications utilisateur

### ⚙️ **Entités Configuration**
- **Setting** - Paramètres système

## 📊 Fixtures générées

Les fixtures créent automatiquement :

### 🔑 **Comptes par défaut**
- **Admin** : `admin@salon.fr` / `admin123`
- **5 Employés** : `email généré` / `password123`
- **30 Clients** : `email généré` / `password123`

### 📈 **Données de test**
- 5 catégories de services (Coiffure, Onglerie, Extensions cils, Maquillage, Soins)
- 17 services réalistes avec prix français
- 4 catégories de produits
- 13 produits avec stocks
- 50 rendez-vous avec différents statuts
- 25 commandes complètes
- Paniers actifs pour 8 clients
- 3 promotions (WELCOME10, FREESHIP, BLACK25)
- 30 notifications diverses
- Sessions actives
- Paramètres du salon configurés

## 🎨 **Données réalistes**

Les fixtures utilisent **Faker français** pour générer :
- Noms et prénoms français
- Adresses françaises réalistes
- Numéros de téléphone au format français
- Horaires de salon réalistes
- Prix en euros
- Descriptions de services professionnelles

## 🚀 **Utilisation**

### Migration de la base de données :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Chargement des fixtures :
```bash
php bin/console doctrine:fixtures:load
```

## 📁 **Structure créée**

```
src/
├── Entity/
│   ├── User.php
│   ├── Employee.php
│   ├── ServiceCategory.php
│   ├── Service.php
│   ├── EmployeeService.php
│   ├── Appointment.php
│   ├── ProductCategory.php
│   ├── Product.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── CartItem.php
│   ├── Promotion.php
│   ├── Notification.php
│   ├── UserSession.php
│   └── Setting.php
├── Repository/
│   ├── UserRepository.php
│   ├── EmployeeRepository.php
│   ├── ServiceCategoryRepository.php
│   ├── ServiceRepository.php
│   ├── EmployeeServiceRepository.php
│   ├── AppointmentRepository.php
│   ├── ProductCategoryRepository.php
│   ├── ProductRepository.php
│   ├── OrderRepository.php
│   ├── OrderItemRepository.php
│   ├── CartItemRepository.php
│   ├── PromotionRepository.php
│   ├── NotificationRepository.php
│   ├── UserSessionRepository.php
│   └── SettingRepository.php
└── DataFixtures/
    └── AppFixtures.php
```

## ✅ **Fonctionnalités incluses**

### Entités User
- ✅ Authentification Symfony Security
- ✅ Rôles (ROLE_USER, ROLE_EMPLOYEE, ROLE_ADMIN)
- ✅ Gestion des mots de passe hashés
- ✅ Statut actif/inactif

### Entités Employee
- ✅ Bio et spécialités
- ✅ Horaires de travail (JSON)
- ✅ Statut disponible/indisponible
- ✅ Relations avec services

### Entités Service
- ✅ Catégorisation
- ✅ Prix et durée
- ✅ Statut actif/inactif
- ✅ Tri par ordre

### Entités Appointment
- ✅ Statuts multiples (pending, confirmed, in_progress, completed, cancelled)
- ✅ Système de rappels
- ✅ Notes personnalisées

### Entités Product
- ✅ SKU unique
- ✅ Gestion stock
- ✅ Images (JSON)
- ✅ Catégorisation

### Entités Order
- ✅ Numéro de commande unique
- ✅ Adresses de livraison/facturation (JSON)
- ✅ Calcul automatique des totaux
- ✅ Statuts de commande

### Entités Promotion
- ✅ Types pourcentage/montant fixe
- ✅ Dates de validité
- ✅ Montant minimum
- ✅ Limite d'utilisation

### Repositories avancés
- ✅ Méthodes de recherche optimisées
- ✅ Filtres par statut, dates, utilisateur
- ✅ Statistiques et rapports
- ✅ Nettoyage automatique (sessions expirées)

## 🔧 **Prêt pour le développement**

Le système est maintenant prêt pour :
- Développement des contrôleurs
- Création des formulaires
- Mise en place de l'interface admin
- API REST
- Interface client
- Gestion des réservations
- Boutique e-commerce

Toutes les entités respectent les bonnes pratiques Symfony et incluent les relations, validations et méthodes utilitaires nécessaires. 