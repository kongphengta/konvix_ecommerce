# Konvix E-commerce — Rapport de Soutenance

---

## Page de garde

**Projet :** Konvix E-commerce  
**Auteur :** Kongphengta  
**Date :** Décembre 2025  
**École :** [Ecole en ligne Studi]

---

## Sommaire

1. Introduction
2. Présentation du projet
3. Architecture technique
4. Choix des technologies
5. Installation et configuration
6. Fonctionnalités détaillées
7. Sécurité
8. UX/UI et responsive design
9. Paiement sécurisé
10. Tests et validation
11. Captures d’écran
12. Conclusion
13. Annexes

---

## 1. Introduction

Ce rapport présente le projet Konvix E-commerce, développé dans le cadre de la formation développeur Web & Mobile full stack. L’objectif est de concevoir une boutique en ligne moderne, sécurisée et professionnelle, intégrant un tunnel de commande complet, la gestion des rôles, et une expérience utilisateur optimale.

---

## 2. Présentation du projet

- Description générale du site
- Objectifs principaux
- Parcours utilisateur (client, vendeur, admin)

---

## 3. Architecture technique

- Symfony 7.3 (framework PHP moderne)
- Doctrine ORM (gestion des entités et de la base de données)
- Twig (moteur de templates)
- Bootstrap 5 (UI responsive)
- Webpack Encore (gestion des assets JS/CSS)
- Stripe/PayPal (paiement sécurisé)

Schéma d’architecture :
![Schéma architecture](captures/architecture.png)

---

## 4. Choix des technologies

- **Symfony** : robustesse, sécurité, communauté
- **Doctrine** : mapping objet-relationnel, migrations
- **Twig** : séparation logique/présentation
- **Bootstrap** : rapidité de prototypage, responsive
- **Webpack Encore** : assets modernes, optimisation
- **Stripe/PayPal** : fiabilité, sécurité des paiements

Explication et justification de chaque choix.

---

## 5. Installation et configuration

- Prérequis (PHP, Composer, Node.js, MySQL)
- Étapes d’installation (voir README)
- Configuration de la base, des rôles, des paiements

---

## 6. Fonctionnalités détaillées

### Catalogue produits

- Affichage, recherche, gestion du stock
- Images principales et secondaires
- Avis clients

### Tunnel de commande

- Panier, adresse, livraison, paiement
- Validation et confirmation

### Espace client

- Profil, commandes, avis

### Espace vendeur/admin

- Gestion des produits, utilisateurs

---

## 7. Sécurité

- Gestion des rôles (client, vendeur, admin)
- Hashage des mots de passe
- Protection CSRF
- Validation des données

---

## 8. UX/UI et responsive design

- Utilisation de Bootstrap 5
- Grille produit, header séparé, trois colonnes
- Feedback utilisateur (flashs, confirmations)
- Accessibilité et ergonomie

Captures d’écran sur mobile/tablette :
![Responsive](captures/responsive.png)

---

## 9. Paiement sécurisé

- Intégration Stripe et PayPal
- Sécurité des transactions
- Gestion des erreurs et retours utilisateur

---

## 10. Tests et validation

- Méthodologie de test (fonctionnel, UX, sécurité)
- Bugs rencontrés et corrections
- Validation finale du tunnel de commande

---

## 11. Captures d’écran

- Accueil
- Page produit
- Panier
- Tunnel de commande
- Espace client
- Espace admin
- Paiement

*Insérer les images dans le dossier `captures/` et les référencer ici :*
![Accueil](captures/accueil.png)
![Produit](captures/produit.png)
![Panier](captures/panier.png)
![Tunnel](captures/tunnel.png)
![Client](captures/client.png)
![Admin](captures/admin.png)
![Paiement](captures/paiement.png)

---

## 12. Conclusion

- Bilan du projet
- Points forts et axes d’amélioration
- Perspectives (fonctionnalités futures, déploiement)

---

## 13. Annexes

- Extraits de code clé
- Migrations Doctrine
- Schémas supplémentaires
- Documentation technique

---

*Ce rapport est à compléter avec des explications détaillées, des schémas, des extraits de code et des captures d’écran pour atteindre 35 pages.*
