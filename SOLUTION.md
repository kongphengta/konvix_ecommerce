# Solution aux Problèmes VPS - Konvix E-commerce

## Résumé des Problèmes

Vous rencontrez deux problèmes principaux sur votre VPS :

1. **Erreur de cache** : "Unable to write in the '/var/www/konvix.fr/var/cache/prod/' directory"
2. **Internal Server Error** lors de l'accès à https://konvix.fr

## Solutions Immédiates

### Option 1 : Utiliser le script automatique (RECOMMANDÉ) ✅

Connectez-vous à votre VPS via SSH et exécutez :

```bash
# Se déplacer dans le répertoire du projet
cd /var/www/konvix.fr

# Mettre à jour le code depuis GitHub
sudo -u www-data git pull origin main

# Exécuter le script de déploiement automatique
sudo bash deploy.sh --full
```

Ce script va :
- ✓ Créer tous les répertoires nécessaires
- ✓ Corriger automatiquement toutes les permissions
- ✓ Installer les dépendances (Composer + NPM)
- ✓ Compiler les assets
- ✓ Exécuter les migrations de base de données
- ✓ Nettoyer et régénérer le cache
- ✓ Vérifier la configuration

### Option 2 : Correction rapide des permissions uniquement

Si vous voulez juste corriger le problème de cache immédiatement :

```bash
cd /var/www/konvix.fr
sudo -u www-data git pull origin main
sudo bash deploy.sh --fix-permissions
```

### Option 3 : Correction manuelle (si les scripts ne fonctionnent pas)

```bash
# 1. Créer les répertoires nécessaires
sudo -u www-data mkdir -p /var/www/konvix.fr/var/cache/prod
sudo -u www-data mkdir -p /var/www/konvix.fr/var/log

# 2. Corriger le propriétaire
sudo chown -R www-data:www-data /var/www/konvix.fr/var
sudo chown -R www-data:www-data /var/www/konvix.fr/public/uploads

# 3. Corriger les permissions
sudo chmod -R 775 /var/www/konvix.fr/var
sudo chmod -R 775 /var/www/konvix.fr/public/uploads

# 4. Nettoyer le cache
sudo rm -rf /var/www/konvix.fr/var/cache/prod/*
sudo -u www-data php bin/console cache:clear --env=prod
sudo -u www-data php bin/console cache:warmup --env=prod

# 5. Redémarrer Apache
sudo systemctl restart apache2
```

## Vérifications Après Correction

### 1. Vérifier les logs Apache

```bash
sudo tail -f /var/log/apache2/konvix-error.log
```

Ouvrez https://konvix.fr dans votre navigateur, puis vérifiez s'il y a de nouvelles erreurs dans les logs.

### 2. Vérifier les logs Symfony

```bash
sudo tail -f /var/www/konvix.fr/var/log/prod.log
```

### 3. Vérifier que mod_rewrite est activé

```bash
# Vérifier si le module est actif
apache2ctl -M | grep rewrite

# Si absent, l'activer
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 4. Vérifier la configuration Apache

```bash
# Tester la configuration
sudo apache2ctl configtest

# Devrait afficher "Syntax OK"
```

## Configuration Apache pour konvix.fr

Si vous n'avez pas encore configuré Apache correctement, voici les étapes :

### 1. Copier la configuration de production

```bash
sudo cp /var/www/konvix.fr/apache-konvix-prod.conf /etc/apache2/sites-available/konvix.fr.conf
```

### 2. Éditer si nécessaire

Ouvrez le fichier et vérifiez que les chemins sont corrects :

```bash
sudo nano /etc/apache2/sites-available/konvix.fr.conf
```

Assurez-vous que :
- `DocumentRoot` pointe vers `/var/www/konvix.fr/public`
- `ServerName` est `konvix.fr`
- `ServerAlias` est `www.konvix.fr`

### 3. Activer le site et les modules nécessaires

```bash
# Activer les modules
sudo a2enmod rewrite headers ssl

# Activer le site
sudo a2ensite konvix.fr.conf

# Désactiver le site par défaut (si nécessaire)
sudo a2dissite 000-default.conf

# Redémarrer Apache
sudo systemctl restart apache2
```

### 4. Configurer SSL avec Let's Encrypt

```bash
# Installer Certbot si pas déjà fait
sudo apt-get install certbot python3-certbot-apache

# Obtenir le certificat SSL
sudo certbot --apache -d konvix.fr -d www.konvix.fr
```

## Configuration de l'environnement (.env.local)

Si vous n'avez pas encore créé le fichier `.env.local` pour la production :

```bash
# Copier le template
sudo cp /var/www/konvix.fr/.env.prod.example /var/www/konvix.fr/.env.local

# Éditer avec les vraies valeurs
sudo nano /var/www/konvix.fr/.env.local
```

**Valeurs importantes à configurer :**

```env
APP_ENV=prod
APP_SECRET=GENERER_UNE_CLE_SECRETE_ALEATOIRE_DE_32_CARACTERES
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/konvix_db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
STRIPE_SECRET_KEY=sk_live_VOTRE_CLE_STRIPE_LIVE
```

**Pour générer APP_SECRET :**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Après avoir modifié `.env.local`, toujours nettoyer le cache :

```bash
sudo -u www-data php bin/console cache:clear --env=prod
```

## Checklist Complète de Déploiement

- [ ] 1. Cloner ou mettre à jour le code depuis GitHub
- [ ] 2. Créer/vérifier le fichier `.env.local` avec les bonnes valeurs
- [ ] 3. Exécuter `sudo bash deploy.sh --full` ou corriger les permissions manuellement
- [ ] 4. Configurer Apache avec le fichier `apache-konvix-prod.conf`
- [ ] 5. Activer les modules Apache (rewrite, headers, ssl)
- [ ] 6. Activer le site `konvix.fr.conf`
- [ ] 7. Installer le certificat SSL avec Certbot
- [ ] 8. Vérifier les logs Apache et Symfony
- [ ] 9. Tester le site sur https://konvix.fr
- [ ] 10. Vérifier toutes les fonctionnalités (produits, panier, paiement, etc.)

## Ressources de Documentation

Maintenant que ces fichiers sont dans votre repository, vous avez accès à :

1. **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guide complet de déploiement
2. **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Guide de dépannage détaillé
3. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Référence rapide des commandes
4. **[.env.prod.example](.env.prod.example)** - Template de configuration
5. **deploy.sh** - Script de déploiement automatique
6. **verify-server.sh** - Script de vérification du serveur

## Commandes de Diagnostic Utiles

```bash
# Vérifier le serveur
bash /var/www/konvix.fr/verify-server.sh

# Vérifier les permissions
ls -la /var/www/konvix.fr/var/
ls -la /var/www/konvix.fr/public/uploads/

# Vérifier Apache
sudo apache2ctl configtest
sudo systemctl status apache2

# Vérifier MySQL
sudo systemctl status mysql
mysql -u root -p  # tester la connexion

# Voir les processus Apache (utilisateur)
ps aux | grep apache2 | head -3
```

## Support Supplémentaire

Si après avoir suivi toutes ces étapes, vous rencontrez toujours des problèmes :

1. Consultez le fichier **TROUBLESHOOTING.md** pour des solutions détaillées
2. Vérifiez les logs complets avec les commandes ci-dessus
3. Assurez-vous que tous les prérequis sont installés avec `verify-server.sh`

## Note Importante

⚠️ **Sécurité** : Ne commitez JAMAIS le fichier `.env.local` dans Git. Il contient des informations sensibles (mots de passe, clés API, etc.).

✅ **Bonnes pratiques** :
- Toujours faire une sauvegarde de la base de données avant une mise à jour
- Tester en local avant de déployer en production
- Surveiller les logs régulièrement
- Maintenir les dépendances à jour

---

**Bonne chance avec votre déploiement !** 🚀
