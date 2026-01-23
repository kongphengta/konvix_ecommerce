# Konvix E-commerce

## Présentation
Konvix E-commerce est une application Symfony 7 moderne permettant la gestion complète d'une boutique en ligne : catalogue produits, tunnel de commande sécurisé, gestion des utilisateurs (clients, vendeurs, admin), avis clients, paiement Stripe/PayPal, et interface responsive.

## Fonctionnalités principales
- Catalogue produits avec images, stock, avis clients
- Tunnel de commande complet (panier, livraison, paiement sécurisé)
- Gestion des rôles (client, vendeur, admin)
- Espace client (profil, commandes, avis)
- Interface admin (gestion produits, utilisateurs)
- Paiement Stripe et PayPal
- Responsive design (Bootstrap 5)

## Installation
### Prérequis
- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL ou MariaDB

### Étapes
1. Cloner le projet :
   ```bash
   git clone <repo-url>
   cd konvix_ecommerce
   ```
2. Installer les dépendances PHP :
   ```bash
   composer install
   ```
3. Installer les dépendances JS/CSS :
   ```bash
   npm install
   npm run build
   ```
4. Configurer la base de données :
   - Copier `.env` en `.env.local` et adapter les accès DB
   - Créer la base puis lancer les migrations :
     ```bash
     php bin/console doctrine:database:create
     php bin/console doctrine:migrations:migrate
     ```
5. Lancer le serveur Symfony :
   ```bash
   symfony server:start
   ```

## Utilisation
- Accès front : http://localhost:8000
- Accès admin : /admin (compte admin requis)
- Comptes de test : voir fixtures ou demander à l’enseignant

## Structure du projet
- `src/Entity/` : entités Doctrine (User, Product, Review...)
- `src/Controller/` : contrôleurs (front, admin, seller)
- `src/Service/` : services métiers (cart, avis...)
- `templates/` : vues Twig (Bootstrap 5)
- `public/` : fichiers statiques, index.php
- `assets/` : JS/CSS source (Webpack Encore)
- `migrations/` : migrations Doctrine

## Points forts
- Sécurité (hashage, CSRF, rôles)
- UX moderne (Bootstrap, feedback utilisateur)
- Paiement sécurisé (Stripe/PayPal)
- Code commenté et structuré

## Captures d’écran
Ajoutez ici vos captures principales (accueil, produit, tunnel, espace client, admin...)

## Auteur
Kongphengta (2025)

---
Pour toute question : voir le code ou contacter l’auteur.