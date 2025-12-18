#!/bin/bash

###############################################################################
# Script de vérification des prérequis serveur - Konvix E-commerce
# Usage: bash verify-server.sh
###############################################################################

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compteurs
PASSED=0
FAILED=0
WARNINGS=0

# Fonction pour afficher les résultats
check_pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

header() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Vérifications
header "1. VÉRIFICATION DU SYSTÈME"

# Système d'exploitation
if [ -f /etc/os-release ]; then
    . /etc/os-release
    check_pass "Système: $NAME $VERSION"
else
    check_warn "Impossible de déterminer le système d'exploitation"
fi

header "2. VÉRIFICATION DE PHP"

# PHP installé
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    check_pass "PHP installé: version $PHP_VERSION"
    
    # Version PHP >= 8.2
    if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
        check_pass "Version PHP >= 8.2"
    else
        check_fail "Version PHP < 8.2 (requis: >= 8.2)"
    fi
    
    # Extensions PHP requises
    echo ""
    info "Vérification des extensions PHP:"
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "xml" "curl" "zip" "gd" "intl" "json" "opcache")
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            check_pass "Extension $ext"
        else
            check_fail "Extension $ext manquante"
        fi
    done
    
    # Configuration PHP
    echo ""
    info "Configuration PHP (php.ini):"
    
    UPLOAD_MAX=$(php -r "echo ini_get('upload_max_filesize');")
    POST_MAX=$(php -r "echo ini_get('post_max_size');")
    MEMORY=$(php -r "echo ini_get('memory_limit');")
    
    info "  upload_max_filesize: $UPLOAD_MAX"
    info "  post_max_size: $POST_MAX"
    info "  memory_limit: $MEMORY"
    
    # Vérifier si OPcache est activé
    if php -m | grep -qi "opcache"; then
        OPCACHE_ENABLED=$(php -r "echo ini_get('opcache.enable');")
        if [ "$OPCACHE_ENABLED" = "1" ]; then
            check_pass "OPcache activé"
        else
            check_warn "OPcache installé mais non activé (recommandé pour production)"
        fi
    fi
    
else
    check_fail "PHP n'est pas installé"
fi

header "3. VÉRIFICATION DE COMPOSER"

if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version --no-ansi 2>&1 | grep -oP 'version \K[^ ]+' | head -1)
    check_pass "Composer installé: version $COMPOSER_VERSION"
else
    check_fail "Composer n'est pas installé"
fi

header "4. VÉRIFICATION DE NODE.JS ET NPM"

if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    check_pass "Node.js installé: $NODE_VERSION"
else
    check_fail "Node.js n'est pas installé"
fi

if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    check_pass "npm installé: version $NPM_VERSION"
else
    check_fail "npm n'est pas installé"
fi

header "5. VÉRIFICATION D'APACHE"

if command -v apache2 &> /dev/null; then
    APACHE_VERSION=$(apache2 -v 2>&1 | head -1)
    check_pass "Apache installé: $APACHE_VERSION"
    
    # Vérifier si Apache est en cours d'exécution
    if systemctl is-active --quiet apache2; then
        check_pass "Apache est en cours d'exécution"
    else
        check_warn "Apache est installé mais n'est pas en cours d'exécution"
    fi
    
    # Modules Apache requis
    echo ""
    info "Vérification des modules Apache:"
    REQUIRED_MODULES=("rewrite" "headers" "ssl")
    
    for mod in "${REQUIRED_MODULES[@]}"; do
        if apache2ctl -M 2>/dev/null | grep -q "${mod}_module"; then
            check_pass "Module $mod activé"
        else
            check_fail "Module $mod non activé"
        fi
    done
    
    # Configuration Apache
    echo ""
    info "Test de configuration Apache:"
    if apache2ctl configtest &> /dev/null; then
        check_pass "Configuration Apache valide"
    else
        check_fail "Configuration Apache invalide"
        info "Exécutez 'sudo apache2ctl configtest' pour plus de détails"
    fi
    
else
    check_fail "Apache n'est pas installé"
fi

header "6. VÉRIFICATION DE MYSQL/MARIADB"

if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version)
    check_pass "MySQL/MariaDB installé: $MYSQL_VERSION"
    
    # Vérifier si MySQL est en cours d'exécution
    if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
        check_pass "MySQL/MariaDB est en cours d'exécution"
    else
        check_warn "MySQL/MariaDB est installé mais n'est pas en cours d'exécution"
    fi
else
    check_fail "MySQL/MariaDB n'est pas installé"
fi

header "7. VÉRIFICATION DE GIT"

if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version)
    check_pass "Git installé: $GIT_VERSION"
else
    check_fail "Git n'est pas installé"
fi

header "8. VÉRIFICATION DE CERTBOT (SSL)"

if command -v certbot &> /dev/null; then
    CERTBOT_VERSION=$(certbot --version 2>&1)
    check_pass "Certbot installé: $CERTBOT_VERSION"
else
    check_warn "Certbot n'est pas installé (requis pour SSL/HTTPS)"
fi

header "9. VÉRIFICATION DES PORTS"

# Vérifier les ports 80 et 443
if command -v netstat &> /dev/null || command -v ss &> /dev/null; then
    info "Vérification des ports:"
    
    if netstat -tlnp 2>/dev/null | grep -q ":80 " || ss -tlnp 2>/dev/null | grep -q ":80 "; then
        check_pass "Port 80 (HTTP) en écoute"
    else
        check_warn "Port 80 (HTTP) n'est pas en écoute"
    fi
    
    if netstat -tlnp 2>/dev/null | grep -q ":443 " || ss -tlnp 2>/dev/null | grep -q ":443 "; then
        check_pass "Port 443 (HTTPS) en écoute"
    else
        check_warn "Port 443 (HTTPS) n'est pas en écoute"
    fi
else
    check_warn "netstat/ss non disponible, impossible de vérifier les ports"
fi

header "10. VÉRIFICATION DE L'ESPACE DISQUE"

DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
DISK_AVAIL=$(df -h / | awk 'NR==2 {print $4}')

info "Espace disque utilisé: ${DISK_USAGE}%"
info "Espace disponible: $DISK_AVAIL"

if [ "$DISK_USAGE" -lt 80 ]; then
    check_pass "Espace disque suffisant"
elif [ "$DISK_USAGE" -lt 90 ]; then
    check_warn "Espace disque faible (${DISK_USAGE}% utilisé)"
else
    check_fail "Espace disque critique (${DISK_USAGE}% utilisé)"
fi

header "11. VÉRIFICATION DES RÉPERTOIRES DU PROJET"

PROJECT_DIR="/var/www/konvix.fr"

if [ -d "$PROJECT_DIR" ]; then
    check_pass "Répertoire du projet existe: $PROJECT_DIR"
    
    # Vérifier le propriétaire
    OWNER=$(stat -c '%U:%G' "$PROJECT_DIR" 2>/dev/null)
    info "Propriétaire: $OWNER"
    
    # Vérifier les répertoires critiques
    info "Vérification des sous-répertoires:"
    
    CRITICAL_DIRS=("public" "src" "config" "var")
    for dir in "${CRITICAL_DIRS[@]}"; do
        if [ -d "$PROJECT_DIR/$dir" ]; then
            check_pass "Répertoire $dir existe"
        else
            check_warn "Répertoire $dir manquant"
        fi
    done
    
    # Vérifier var/cache et var/log
    if [ -d "$PROJECT_DIR/var/cache" ]; then
        CACHE_PERMS=$(stat -c '%a' "$PROJECT_DIR/var/cache" 2>/dev/null)
        if [ "$CACHE_PERMS" = "775" ] || [ "$CACHE_PERMS" = "777" ]; then
            check_pass "Permissions var/cache correctes ($CACHE_PERMS)"
        else
            check_warn "Permissions var/cache à vérifier ($CACHE_PERMS)"
        fi
    else
        check_warn "Répertoire var/cache n'existe pas"
    fi
    
    # Vérifier .env.local
    if [ -f "$PROJECT_DIR/.env.local" ]; then
        check_pass "Fichier .env.local existe"
    else
        check_warn "Fichier .env.local n'existe pas (à créer pour la production)"
    fi
    
else
    check_warn "Répertoire du projet n'existe pas encore: $PROJECT_DIR"
fi

# Résumé
header "RÉSUMÉ"

echo ""
echo -e "${GREEN}Réussis:${NC} $PASSED"
echo -e "${YELLOW}Avertissements:${NC} $WARNINGS"
echo -e "${RED}Échecs:${NC} $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}✓ Le serveur est prêt pour le déploiement de Konvix E-commerce!${NC}"
        exit 0
    else
        echo -e "${YELLOW}⚠ Le serveur est généralement prêt, mais certains avertissements nécessitent votre attention.${NC}"
        exit 0
    fi
else
    echo -e "${RED}✗ Des problèmes doivent être résolus avant le déploiement.${NC}"
    echo ""
    echo "Recommandations:"
    echo "1. Installer les composants manquants"
    echo "2. Activer les modules Apache requis: sudo a2enmod rewrite headers ssl"
    echo "3. Vérifier les extensions PHP: voir DEPLOYMENT.md"
    echo "4. Consulter DEPLOYMENT.md pour les instructions d'installation"
    exit 1
fi
