# Guide Rapide - Commandes de Déploiement

## Pour résoudre l'erreur "Unable to write in the cache directory"

```bash
# Solution 1 : Utiliser le script automatique (RECOMMANDÉ)
cd /var/www/konvix.fr
sudo bash deploy.sh --fix-permissions

# Solution 2 : Correction manuelle
sudo chown -R www-data:www-data /var/www/konvix.fr/var
sudo chmod -R 775 /var/www/konvix.fr/var
sudo -u www-data php bin/console cache:clear --env=prod
```

## Pour résoudre "Internal Server Error"

```bash
# 1. Vérifier les logs
sudo tail -f /var/log/apache2/konvix-error.log
sudo tail -f /var/www/konvix.fr/var/log/prod.log

# 2. Activer mod_rewrite si nécessaire
sudo a2enmod rewrite
sudo systemctl restart apache2

# 3. Nettoyer le cache
sudo -u www-data php bin/console cache:clear --env=prod

# 4. Vérifier la configuration Apache
sudo apache2ctl configtest
```

## Commandes de déploiement complètes

```bash
# Déploiement complet (première installation ou mise à jour majeure)
cd /var/www/konvix.fr
sudo bash deploy.sh --full

# Correction des permissions uniquement
sudo bash deploy.sh --fix-permissions

# Nettoyage du cache uniquement
sudo bash deploy.sh --clear-cache

# Installation des dépendances uniquement
sudo bash deploy.sh --install-deps

# Exécution des migrations uniquement
sudo bash deploy.sh --migrations
```

## Vérification du serveur

```bash
# Vérifier que le serveur répond aux prérequis
bash verify-server.sh
```

## Commandes de diagnostic

```bash
# Vérifier les permissions
ls -la /var/www/konvix.fr/var/
ls -la /var/www/konvix.fr/public/uploads/

# Vérifier Apache
sudo apache2ctl configtest
sudo systemctl status apache2

# Vérifier MySQL
sudo systemctl status mysql

# Vérifier les modules Apache
apache2ctl -M | grep rewrite
```

## Maintenance courante

```bash
# Nettoyer le cache
sudo -u www-data php bin/console cache:clear --env=prod

# Voir les logs en temps réel
sudo tail -f /var/log/apache2/konvix-error.log

# Redémarrer Apache
sudo systemctl restart apache2

# Sauvegarder la base de données
sudo mysqldump -u db_user -p konvix_db > backup_$(date +%Y%m%d).sql
```

## Mise à jour du code

```bash
cd /var/www/konvix.fr

# Sauvegarder la base de données avant toute mise à jour
sudo mysqldump -u db_user -p konvix_db > backup_$(date +%Y%m%d).sql

# Mettre à jour le code
sudo -u www-data git pull origin main

# Lancer le script de déploiement
sudo bash deploy.sh --full
```

## Configuration SSL

```bash
# Installer Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtenir un certificat SSL
sudo certbot --apache -d konvix.fr -d www.konvix.fr

# Renouveler manuellement
sudo certbot renew

# Tester le renouvellement automatique
sudo certbot renew --dry-run
```

## En cas d'urgence

Si le site est complètement cassé :

```bash
# 1. Sauvegarder la base de données
sudo mysqldump -u db_user -p konvix_db > emergency_backup.sql

# 2. Supprimer complètement le cache
sudo rm -rf /var/www/konvix.fr/var/cache/*

# 3. Réinitialiser les permissions
cd /var/www/konvix.fr
sudo bash deploy.sh --fix-permissions

# 4. Vérifier les logs
sudo tail -100 /var/log/apache2/konvix-error.log
sudo tail -100 /var/www/konvix.fr/var/log/prod.log

# 5. Redémarrer Apache
sudo systemctl restart apache2
```

## Ressources

- Guide complet : [DEPLOYMENT.md](DEPLOYMENT.md)
- Dépannage : [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- Configuration production : [.env.prod.example](.env.prod.example)
