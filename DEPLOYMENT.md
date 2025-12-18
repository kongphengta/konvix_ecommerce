# Guide de Déploiement Production - Konvix E-commerce

## Prérequis VPS

### Système requis
- Ubuntu 20.04+ ou Debian 11+
- Apache 2.4+ avec mod_rewrite activé
- PHP 8.2+ avec extensions requises
- MySQL/MariaDB 10.3+
- Certbot pour SSL/HTTPS
- Git

### Extensions PHP requises
```bash
sudo apt-get update
sudo apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
  php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl \
  php8.2-bcmath php8.2-opcache
```

### Installation de Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Installation de Node.js et npm
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## Installation sur VPS

### 1. Cloner le projet
```bash
cd /var/www
sudo git clone https://github.com/kongphengta/konvix_ecommerce.git konvix.fr
cd konvix.fr
```

### 2. Configurer les permissions
```bash
# Définir le propriétaire correct (www-data pour Apache)
sudo chown -R www-data:www-data /var/www/konvix.fr

# Permissions pour les répertoires
sudo find /var/www/konvix.fr -type d -exec chmod 755 {} \;

# Permissions pour les fichiers
sudo find /var/www/konvix.fr -type f -exec chmod 644 {} \;

# Permissions spéciales pour var/ et public/uploads/
sudo chmod -R 775 /var/www/konvix.fr/var
sudo chmod -R 775 /var/www/konvix.fr/public/uploads

# S'assurer que www-data possède var/ et uploads/
sudo chown -R www-data:www-data /var/www/konvix.fr/var
sudo chown -R www-data:www-data /var/www/konvix.fr/public/uploads
```

### 3. Créer les répertoires nécessaires
```bash
# Créer les répertoires s'ils n'existent pas
sudo -u www-data mkdir -p /var/www/konvix.fr/var/cache/prod
sudo -u www-data mkdir -p /var/www/konvix.fr/var/log
sudo -u www-data mkdir -p /var/www/konvix.fr/public/uploads
sudo -u www-data mkdir -p /var/www/konvix.fr/public/uploads/products
```

### 4. Installer les dépendances
```bash
# Installer les dépendances PHP (en tant que www-data)
sudo -u www-data composer install --no-dev --optimize-autoloader

# Installer les dépendances Node.js
sudo -u www-data npm ci --production

# Compiler les assets pour production
sudo -u www-data npm run build
```

### 5. Configuration de l'environnement
```bash
# Copier le fichier d'environnement
sudo cp .env .env.local

# Éditer le fichier .env.local avec les vraies valeurs
sudo nano .env.local
```

Configuration .env.local pour production :
```env
APP_ENV=prod
APP_SECRET=VOTRE_SECRET_GENERE_ICI
DEFAULT_URI=https://konvix.fr

# Database
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/konvix_db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

# Mailer
MAILER_DSN=smtp://user:pass@smtp.example.com:465

# Stripe
STRIPE_SECRET_KEY=sk_live_VOTRE_CLE_STRIPE
```

### 6. Configurer la base de données
```bash
# Créer la base de données
sudo -u www-data php bin/console doctrine:database:create --env=prod

# Exécuter les migrations
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

### 7. Nettoyer et chauffer le cache
```bash
# Nettoyer le cache
sudo -u www-data php bin/console cache:clear --env=prod

# Préchauffer le cache
sudo -u www-data php bin/console cache:warmup --env=prod
```

## Configuration Apache

### 1. Créer le Virtual Host
Créer le fichier `/etc/apache2/sites-available/konvix.fr.conf` :

```apache
<VirtualHost *:80>
    ServerName konvix.fr
    ServerAlias www.konvix.fr
    
    DocumentRoot /var/www/konvix.fr/public
    
    <Directory /var/www/konvix.fr/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
        
        # Enable rewrite engine
        RewriteEngine On
        
        # Handle Authorization Header
        RewriteCond %{HTTP:Authorization} ^(.*)
        RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
        
        # Redirect to front controller
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    <Directory /var/www/konvix.fr/public/uploads>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/konvix-error.log
    CustomLog ${APACHE_LOG_DIR}/konvix-access.log combined
</VirtualHost>
```

### 2. Activer le site et les modules
```bash
# Activer les modules nécessaires
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl

# Activer le site
sudo a2ensite konvix.fr.conf

# Désactiver le site par défaut si nécessaire
sudo a2dissite 000-default.conf

# Tester la configuration
sudo apache2ctl configtest

# Redémarrer Apache
sudo systemctl restart apache2
```

### 3. Configurer SSL avec Let's Encrypt
```bash
# Installer Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtenir et installer le certificat SSL
sudo certbot --apache -d konvix.fr -d www.konvix.fr

# Le certificat sera automatiquement renouvelé
# Vérifier le renouvellement automatique :
sudo certbot renew --dry-run
```

Après l'installation SSL, le fichier de configuration sera mis à jour automatiquement.

## Dépannage

### Erreur : "Unable to write in the '/var/www/konvix.fr/var/cache/prod/' directory"

**Cause** : Permissions insuffisantes sur le répertoire var/

**Solution** :
```bash
# Vérifier les permissions actuelles
ls -la /var/www/konvix.fr/var/

# Corriger les permissions
sudo chown -R www-data:www-data /var/www/konvix.fr/var
sudo chmod -R 775 /var/www/konvix.fr/var

# Nettoyer et recréer le cache
sudo rm -rf /var/www/konvix.fr/var/cache/prod/*
sudo -u www-data php bin/console cache:clear --env=prod
sudo -u www-data php bin/console cache:warmup --env=prod
```

### Erreur : "Internal Server Error"

**Causes possibles** :
1. Fichier .htaccess mal configuré
2. mod_rewrite non activé
3. Erreurs dans les logs Apache
4. Cache non généré correctement

**Diagnostic** :
```bash
# Vérifier les logs d'erreur Apache
sudo tail -f /var/log/apache2/konvix-error.log

# Vérifier les logs Symfony
sudo tail -f /var/www/konvix.fr/var/log/prod.log

# Vérifier que mod_rewrite est activé
apache2ctl -M | grep rewrite

# Tester la configuration Apache
sudo apache2ctl configtest
```

**Solutions** :
```bash
# Activer mod_rewrite si nécessaire
sudo a2enmod rewrite
sudo systemctl restart apache2

# Vérifier le fichier .htaccess dans public/
cd /var/www/konvix.fr/public
cat .htaccess

# Régénérer le cache
sudo -u www-data php bin/console cache:clear --env=prod --no-warmup
sudo -u www-data php bin/console cache:warmup --env=prod
```

### Erreur : "An exception occurred in driver: SQLSTATE[HY000] [2002] Connection refused"

**Cause** : Problème de connexion à la base de données

**Solution** :
```bash
# Vérifier que MySQL/MariaDB fonctionne
sudo systemctl status mysql

# Tester la connexion
mysql -u db_user -p

# Vérifier les credentials dans .env.local
cat /var/www/konvix.fr/.env.local | grep DATABASE_URL
```

### Problèmes d'upload de fichiers

**Solution** :
```bash
# Vérifier les permissions du dossier uploads
ls -la /var/www/konvix.fr/public/uploads/

# Corriger si nécessaire
sudo chown -R www-data:www-data /var/www/konvix.fr/public/uploads
sudo chmod -R 775 /var/www/konvix.fr/public/uploads

# Vérifier les limites PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

## Maintenance

### Mise à jour du code
```bash
cd /var/www/konvix.fr

# Sauvegarder la base de données
sudo mysqldump -u db_user -p konvix_db > backup_$(date +%Y%m%d).sql

# Mettre à jour le code
sudo -u www-data git pull origin main

# Installer les dépendances
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci --production
sudo -u www-data npm run build

# Exécuter les migrations
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Nettoyer le cache
sudo -u www-data php bin/console cache:clear --env=prod
sudo -u www-data php bin/console cache:warmup --env=prod
```

### Surveillance des logs
```bash
# Logs Apache
sudo tail -f /var/log/apache2/konvix-error.log

# Logs Symfony
sudo tail -f /var/www/konvix.fr/var/log/prod.log
```

### Optimisation des performances
```bash
# Activer OPcache (éditer /etc/php/8.2/apache2/php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000

# Redémarrer Apache après modification
sudo systemctl restart apache2
```

## Sécurité

### Fichiers sensibles
```bash
# S'assurer que .env.local n'est pas accessible
# Vérifier que public/ est bien le DocumentRoot
# Ne jamais commiter .env.local dans git
```

### Permissions recommandées
```
Répertoires : 755 (rwxr-xr-x)
Fichiers : 644 (rw-r--r--)
var/ : 775 (rwxrwxr-x)
public/uploads/ : 775 (rwxrwxr-x)
Propriétaire : www-data:www-data
```

## Checklist de déploiement

- [ ] VPS configuré avec tous les prérequis
- [ ] Code cloné dans /var/www/konvix.fr
- [ ] Permissions correctement configurées
- [ ] Dépendances installées (Composer + NPM)
- [ ] .env.local configuré avec les bonnes valeurs
- [ ] Base de données créée et migrations exécutées
- [ ] Cache généré et warmed up
- [ ] Apache configuré avec le Virtual Host
- [ ] SSL configuré avec Let's Encrypt
- [ ] Site accessible via HTTPS
- [ ] Logs vérifiés sans erreurs
- [ ] Tests de fonctionnalités critiques effectués
- [ ] Sauvegardes configurées

## Support

Pour toute question ou problème, consultez :
- Logs Apache : `/var/log/apache2/konvix-error.log`
- Logs Symfony : `/var/www/konvix.fr/var/log/prod.log`
- Documentation Symfony : https://symfony.com/doc/current/deployment.html
