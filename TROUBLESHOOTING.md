# Guide de Dépannage - Konvix E-commerce

Ce document répertorie les problèmes courants rencontrés lors du déploiement et de l'utilisation de Konvix E-commerce en production, ainsi que leurs solutions.

## Table des matières

1. [Erreurs de cache](#erreurs-de-cache)
2. [Erreurs serveur (500)](#erreurs-serveur-500)
3. [Problèmes de base de données](#problèmes-de-base-de-données)
4. [Problèmes d'upload de fichiers](#problèmes-dupload-de-fichiers)
5. [Problèmes SSL/HTTPS](#problèmes-sslhttps)
6. [Problèmes de permissions](#problèmes-de-permissions)
7. [Problèmes de configuration Apache](#problèmes-de-configuration-apache)
8. [Diagnostic général](#diagnostic-général)

---

## Erreurs de cache

### Erreur: "Unable to write in the '/var/www/konvix.fr/var/cache/prod/' directory"

**Symptômes:**
- Impossible de nettoyer le cache avec `php bin/console cache:clear --env=prod`
- Message d'erreur mentionnant des permissions insuffisantes
- Le site affiche une erreur 500

**Causes possibles:**
1. Le répertoire `var/cache/prod/` n'existe pas
2. Permissions insuffisantes sur le répertoire
3. Le répertoire appartient au mauvais utilisateur

**Diagnostic:**
```bash
# Vérifier si le répertoire existe
ls -la /var/www/konvix.fr/var/

# Vérifier les permissions
ls -la /var/www/konvix.fr/var/cache/

# Vérifier le propriétaire
stat /var/www/konvix.fr/var/cache/
```

**Solutions:**

**Solution 1: Utiliser le script de déploiement (RECOMMANDÉ)**
```bash
cd /var/www/konvix.fr
sudo bash deploy.sh --fix-permissions
```

**Solution 2: Correction manuelle**
```bash
# Créer les répertoires nécessaires
sudo -u www-data mkdir -p /var/www/konvix.fr/var/cache/prod
sudo -u www-data mkdir -p /var/www/konvix.fr/var/log

# Corriger le propriétaire
sudo chown -R www-data:www-data /var/www/konvix.fr/var

# Corriger les permissions
sudo chmod -R 775 /var/www/konvix.fr/var

# Nettoyer le cache
sudo -u www-data php bin/console cache:clear --env=prod
```

**Solution 3: Si le cache est corrompu**
```bash
# Supprimer complètement le cache
sudo rm -rf /var/www/konvix.fr/var/cache/prod/*

# Régénérer le cache
sudo -u www-data php bin/console cache:clear --env=prod --no-warmup
sudo -u www-data php bin/console cache:warmup --env=prod
```

---

## Erreurs serveur (500)

### Erreur: "Internal Server Error" lors de l'accès au site

**Symptômes:**
- Le navigateur affiche "Internal Server Error"
- Page blanche ou erreur 500
- Le site ne charge pas

**Diagnostic:**

**Étape 1: Vérifier les logs**
```bash
# Logs Apache (priorité haute)
sudo tail -f /var/log/apache2/konvix-error.log

# Logs Symfony
sudo tail -f /var/www/konvix.fr/var/log/prod.log

# Logs système
sudo tail -f /var/log/syslog
```

**Étape 2: Vérifier la configuration Apache**
```bash
# Tester la configuration
sudo apache2ctl configtest

# Si erreur, la corriger avant de continuer
```

**Étape 3: Vérifier le fichier .htaccess**
```bash
cat /var/www/konvix.fr/public/.htaccess
```

**Solutions selon les logs:**

**Si "mod_rewrite" n'est pas activé:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Si "AllowOverride None" dans la config Apache:**
```bash
# Éditer /etc/apache2/sites-available/konvix.fr.conf
# Changer AllowOverride None en AllowOverride All
sudo nano /etc/apache2/sites-available/konvix.fr.conf

# Redémarrer Apache
sudo systemctl restart apache2
```

**Si erreur de cache:**
```bash
# Régénérer le cache
sudo -u www-data php bin/console cache:clear --env=prod
sudo -u www-data php bin/console cache:warmup --env=prod
```

**Si erreur de variable d'environnement (APP_SECRET, etc.):**
```bash
# Vérifier que .env.local existe et est correctement configuré
cat /var/www/konvix.fr/.env.local

# Si manquant, créer depuis l'exemple
sudo cp /var/www/konvix.fr/.env.prod.example /var/www/konvix.fr/.env.local
sudo nano /var/www/konvix.fr/.env.local
# Éditer les valeurs
```

**Si erreur PHP (parse error, class not found):**
```bash
# Vérifier la version PHP
php -v

# Réinstaller les dépendances
cd /var/www/konvix.fr
sudo -u www-data composer install --no-dev --optimize-autoloader
```

---

## Problèmes de base de données

### Erreur: "SQLSTATE[HY000] [2002] Connection refused"

**Symptômes:**
- Impossible de se connecter à la base de données
- Erreur mentionnant "Connection refused" ou "Access denied"

**Diagnostic:**
```bash
# Vérifier que MySQL/MariaDB fonctionne
sudo systemctl status mysql

# Vérifier la connexion
mysql -u root -p
```

**Solutions:**

**Si MySQL n'est pas démarré:**
```bash
sudo systemctl start mysql
sudo systemctl enable mysql  # Pour démarrage automatique
```

**Si les credentials sont incorrects:**
```bash
# Vérifier .env.local
cat /var/www/konvix.fr/.env.local | grep DATABASE_URL

# Tester la connexion manuellement
mysql -u utilisateur -p nom_base
# Entrer le mot de passe quand demandé
```

**Si la base de données n'existe pas:**
```bash
cd /var/www/konvix.fr
sudo -u www-data php bin/console doctrine:database:create --env=prod
sudo -u www-data php bin/console doctrine:migrations:migrate --env=prod
```

### Erreur: "The table 'xxx' doesn't exist"

**Solution:**
```bash
cd /var/www/konvix.fr

# Exécuter les migrations
sudo -u www-data php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

---

## Problèmes d'upload de fichiers

### Erreur: Impossible d'uploader des images produits

**Symptômes:**
- Les images ne s'uploadent pas
- Erreur lors de la soumission du formulaire
- Images non sauvegardées

**Diagnostic:**
```bash
# Vérifier les permissions du dossier uploads
ls -la /var/www/konvix.fr/public/uploads/

# Vérifier les limites PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

**Solutions:**

**Problème de permissions:**
```bash
# Corriger les permissions
sudo chown -R www-data:www-data /var/www/konvix.fr/public/uploads
sudo chmod -R 775 /var/www/konvix.fr/public/uploads

# Créer les sous-dossiers si nécessaire
sudo -u www-data mkdir -p /var/www/konvix.fr/public/uploads/products
```

**Problème de limite de taille:**
```bash
# Éditer la configuration PHP
sudo nano /etc/php/8.2/apache2/php.ini

# Modifier les valeurs:
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M

# Redémarrer Apache
sudo systemctl restart apache2
```

---

## Problèmes SSL/HTTPS

### Erreur: "Your connection is not private" (ERR_CERT_AUTHORITY_INVALID)

**Symptômes:**
- Avertissement de sécurité dans le navigateur
- Certificat SSL invalide ou expiré

**Solutions:**

**Installer/Renouveler le certificat SSL:**
```bash
# Installer Certbot si pas déjà fait
sudo apt-get install certbot python3-certbot-apache

# Obtenir un certificat
sudo certbot --apache -d konvix.fr -d www.konvix.fr

# Renouveler un certificat expiré
sudo certbot renew

# Tester le renouvellement automatique
sudo certbot renew --dry-run
```

### Site ne redirige pas vers HTTPS

**Solution:**
```bash
# Vérifier la configuration Apache
cat /etc/apache2/sites-available/konvix.fr.conf

# S'assurer que la redirection HTTP -> HTTPS est présente
# (voir apache-konvix-prod.conf pour un exemple)

# Redémarrer Apache
sudo systemctl restart apache2
```

---

## Problèmes de permissions

### Diagnostic complet des permissions

**Script de diagnostic:**
```bash
#!/bin/bash

echo "=== Diagnostic des permissions ==="
echo ""

echo "Propriétaire du projet:"
ls -ld /var/www/konvix.fr/

echo ""
echo "Répertoire var/:"
ls -la /var/www/konvix.fr/var/

echo ""
echo "Répertoire cache:"
ls -la /var/www/konvix.fr/var/cache/ 2>/dev/null || echo "Répertoire cache n'existe pas"

echo ""
echo "Répertoire uploads:"
ls -la /var/www/konvix.fr/public/uploads/

echo ""
echo "Processus Apache (utilisateur):"
ps aux | grep apache2 | head -3
```

**Permissions recommandées:**
```
/var/www/konvix.fr/              -> www-data:www-data 755
/var/www/konvix.fr/var/          -> www-data:www-data 775
/var/www/konvix.fr/var/cache/    -> www-data:www-data 775
/var/www/konvix.fr/var/log/      -> www-data:www-data 775
/var/www/konvix.fr/public/uploads/ -> www-data:www-data 775
```

**Réinitialisation complète des permissions:**
```bash
cd /var/www/konvix.fr
sudo bash deploy.sh --fix-permissions
```

---

## Problèmes de configuration Apache

### Apache ne démarre pas

**Diagnostic:**
```bash
# Vérifier le statut
sudo systemctl status apache2

# Tester la configuration
sudo apache2ctl configtest

# Voir les logs
sudo journalctl -xeu apache2
```

**Solutions courantes:**

**Erreur de syntaxe dans la config:**
```bash
# Identifier le fichier problématique
sudo apache2ctl configtest

# Éditer et corriger
sudo nano /etc/apache2/sites-available/konvix.fr.conf

# Retester
sudo apache2ctl configtest
```

**Port 80/443 déjà utilisé:**
```bash
# Identifier le processus
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443

# Arrêter le processus conflictuel ou changer le port
```

### Site par défaut s'affiche au lieu de Konvix

**Solution:**
```bash
# Désactiver le site par défaut
sudo a2dissite 000-default.conf

# S'assurer que konvix.fr est activé
sudo a2ensite konvix.fr.conf

# Redémarrer Apache
sudo systemctl restart apache2
```

---

## Diagnostic général

### Script de diagnostic complet

Créer un fichier `diagnostic.sh`:

```bash
#!/bin/bash

echo "======================================"
echo "Diagnostic Konvix E-commerce"
echo "======================================"
echo ""

echo "=== 1. Informations système ==="
uname -a
php -v | head -1
apache2 -v | head -1
mysql --version
echo ""

echo "=== 2. État des services ==="
systemctl status apache2 --no-pager | grep Active
systemctl status mysql --no-pager | grep Active
echo ""

echo "=== 3. Configuration Apache ==="
apache2ctl -t
echo ""

echo "=== 4. Modules Apache activés ==="
apache2ctl -M | grep -E "rewrite|ssl|headers"
echo ""

echo "=== 5. Sites Apache activés ==="
ls -la /etc/apache2/sites-enabled/
echo ""

echo "=== 6. Permissions projet ==="
ls -ld /var/www/konvix.fr/
ls -ld /var/www/konvix.fr/var/
ls -ld /var/www/konvix.fr/public/uploads/
echo ""

echo "=== 7. Configuration Symfony ==="
if [ -f /var/www/konvix.fr/.env.local ]; then
    echo ".env.local existe: OUI"
    cat /var/www/konvix.fr/.env.local | grep -E "APP_ENV|APP_SECRET|DATABASE_URL" | sed 's/=.*/=***/'
else
    echo ".env.local existe: NON"
fi
echo ""

echo "=== 8. Dernières erreurs Apache ==="
tail -20 /var/log/apache2/konvix-error.log 2>/dev/null || echo "Pas de logs disponibles"
echo ""

echo "=== 9. Dernières erreurs Symfony ==="
tail -20 /var/www/konvix.fr/var/log/prod.log 2>/dev/null || echo "Pas de logs disponibles"
echo ""

echo "======================================"
echo "Diagnostic terminé"
echo "======================================"
```

Utilisation:
```bash
sudo bash diagnostic.sh > diagnostic-output.txt
cat diagnostic-output.txt
```

---

## Checklist de résolution de problèmes

Lorsqu'un problème survient, suivre cette checklist dans l'ordre:

- [ ] 1. Consulter les logs Apache: `sudo tail -f /var/log/apache2/konvix-error.log`
- [ ] 2. Consulter les logs Symfony: `sudo tail -f /var/www/konvix.fr/var/log/prod.log`
- [ ] 3. Vérifier que les services fonctionnent: `systemctl status apache2 mysql`
- [ ] 4. Tester la config Apache: `sudo apache2ctl configtest`
- [ ] 5. Vérifier les permissions: `ls -la /var/www/konvix.fr/var/`
- [ ] 6. Vérifier .env.local existe et est correct
- [ ] 7. Vérifier la connexion à la base de données
- [ ] 8. Nettoyer le cache: `sudo -u www-data php bin/console cache:clear --env=prod`
- [ ] 9. Vérifier mod_rewrite est activé: `apache2ctl -M | grep rewrite`
- [ ] 10. Redémarrer Apache: `sudo systemctl restart apache2`

---

## Contacts et ressources

- Documentation Symfony: https://symfony.com/doc/current/setup.html
- Documentation Doctrine: https://www.doctrine-project.org/
- Forum Symfony: https://symfony.com/support
- Stack Overflow: https://stackoverflow.com/questions/tagged/symfony

---

## Notes importantes

1. **Toujours sauvegarder** avant de faire des modifications importantes
2. **Tester en dev** avant de déployer en production
3. **Surveiller les logs** régulièrement
4. **Documenter** les problèmes rencontrés et leurs solutions
5. **Automatiser** avec le script deploy.sh pour éviter les erreurs manuelles
