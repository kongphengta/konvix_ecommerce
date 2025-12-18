#!/usr/bin/env bash

###############################################################################
# Script de déploiement et correction des permissions - Konvix E-commerce
# Usage: sudo bash deploy.sh [--fix-permissions|--clear-cache|--full]
###############################################################################

set -e  # Arrêt en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/konvix.fr"
WEB_USER="www-data"
WEB_GROUP="www-data"
APP_ENV="prod"

# Fonction pour afficher les messages
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier si le script est exécuté en tant que root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        error "Ce script doit être exécuté en tant que root (utilisez sudo)"
        exit 1
    fi
}

# Vérifier si le répertoire du projet existe
check_project_dir() {
    if [ ! -d "$PROJECT_DIR" ]; then
        error "Le répertoire du projet n'existe pas: $PROJECT_DIR"
        exit 1
    fi
    info "Répertoire du projet trouvé: $PROJECT_DIR"
}

# Créer les répertoires nécessaires
create_directories() {
    info "Création des répertoires nécessaires..."
    
    # Liste des répertoires à créer
    local dirs=(
        "$PROJECT_DIR/var/cache/$APP_ENV"
        "$PROJECT_DIR/var/log"
        "$PROJECT_DIR/var/sessions"
        "$PROJECT_DIR/public/uploads"
        "$PROJECT_DIR/public/uploads/products"
    )
    
    # Créer chaque répertoire
    for dir in "${dirs[@]}"; do
        sudo -u $WEB_USER mkdir -p "$dir"
    done
    
    info "Répertoires créés avec succès"
}

# Corriger les permissions
fix_permissions() {
    info "Correction des permissions..."
    
    # Propriétaire général
    chown -R $WEB_USER:$WEB_GROUP "$PROJECT_DIR"
    info "Propriétaire défini: $WEB_USER:$WEB_GROUP"
    
    # Permissions des répertoires
    find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
    info "Permissions des répertoires: 755"
    
    # Permissions des fichiers
    find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
    info "Permissions des fichiers: 644"
    
    # Permissions spéciales pour var/
    chmod -R 775 "$PROJECT_DIR/var"
    chown -R $WEB_USER:$WEB_GROUP "$PROJECT_DIR/var"
    info "Permissions var/: 775"
    
    # Permissions spéciales pour public/uploads/
    chmod -R 775 "$PROJECT_DIR/public/uploads"
    chown -R $WEB_USER:$WEB_GROUP "$PROJECT_DIR/public/uploads"
    info "Permissions public/uploads/: 775"
    
    # Rendre bin/console exécutable
    if [ -f "$PROJECT_DIR/bin/console" ]; then
        chmod +x "$PROJECT_DIR/bin/console"
        info "bin/console rendu exécutable"
    fi
    
    info "Permissions corrigées avec succès"
}

# Nettoyer et régénérer le cache
clear_cache() {
    info "Nettoyage du cache..."
    
    cd "$PROJECT_DIR"
    
    # Créer le répertoire cache s'il n'existe pas
    if [ ! -d "$PROJECT_DIR/var/cache/$APP_ENV" ]; then
        sudo -u $WEB_USER mkdir -p "$PROJECT_DIR/var/cache/$APP_ENV"
        info "Répertoire cache créé"
    fi
    
    # Supprimer le cache existant
    if [ -d "$PROJECT_DIR/var/cache/$APP_ENV" ]; then
        rm -rf "$PROJECT_DIR/var/cache/$APP_ENV"/*
        info "Cache existant supprimé"
    fi
    
    # Nettoyer le cache avec Symfony
    sudo -u $WEB_USER php bin/console cache:clear --env=$APP_ENV --no-warmup
    info "Cache nettoyé"
    
    # Préchauffer le cache
    sudo -u $WEB_USER php bin/console cache:warmup --env=$APP_ENV
    info "Cache préchauffé"
    
    info "Cache régénéré avec succès"
}

# Installer/Mettre à jour les dépendances
install_dependencies() {
    info "Installation des dépendances..."
    
    cd "$PROJECT_DIR"
    
    # Composer
    if [ -f "composer.json" ]; then
        info "Installation des dépendances Composer..."
        sudo -u $WEB_USER composer install --no-dev --optimize-autoloader --no-interaction
        info "Dépendances Composer installées"
    fi
    
    # NPM
    if [ -f "package.json" ]; then
        info "Installation des dépendances NPM..."
        sudo -u $WEB_USER npm ci --production
        info "Dépendances NPM installées"
        
        info "Compilation des assets..."
        sudo -u $WEB_USER npm run build
        info "Assets compilés"
    fi
}

# Exécuter les migrations
run_migrations() {
    info "Exécution des migrations de base de données..."
    
    cd "$PROJECT_DIR"
    sudo -u $WEB_USER php bin/console doctrine:migrations:migrate --no-interaction --env=$APP_ENV
    
    info "Migrations exécutées avec succès"
}

# Vérifier la configuration
verify_config() {
    info "Vérification de la configuration..."
    
    cd "$PROJECT_DIR"
    
    # Vérifier que .env.local existe
    if [ ! -f ".env.local" ]; then
        warning ".env.local n'existe pas. Assurez-vous de le créer avec les bonnes valeurs."
    else
        info ".env.local trouvé"
    fi
    
    # Vérifier les permissions
    info "Vérification des permissions..."
    ls -la "$PROJECT_DIR/var/" | head -5
    ls -la "$PROJECT_DIR/public/uploads/" | head -5
    
    info "Vérification terminée"
}

# Afficher l'aide
show_help() {
    cat << EOF
Usage: sudo bash deploy.sh [OPTIONS]

Options:
  --fix-permissions    Corriger uniquement les permissions
  --clear-cache        Nettoyer et régénérer le cache uniquement
  --install-deps       Installer les dépendances uniquement
  --migrations         Exécuter les migrations uniquement
  --full               Déploiement complet (par défaut)
  --help               Afficher cette aide

Exemples:
  sudo bash deploy.sh --fix-permissions
  sudo bash deploy.sh --clear-cache
  sudo bash deploy.sh --full
  sudo bash deploy.sh

Description:
  Ce script automatise le déploiement et la maintenance de l'application Konvix E-commerce.
  Il corrige les permissions, nettoie le cache, et effectue les opérations de maintenance nécessaires.

EOF
}

# Fonction principale
main() {
    check_root
    check_project_dir
    
    case "${1:-}" in
        --fix-permissions)
            info "=== Correction des permissions uniquement ==="
            create_directories
            fix_permissions
            verify_config
            ;;
        --clear-cache)
            info "=== Nettoyage du cache uniquement ==="
            clear_cache
            ;;
        --install-deps)
            info "=== Installation des dépendances uniquement ==="
            install_dependencies
            ;;
        --migrations)
            info "=== Exécution des migrations uniquement ==="
            run_migrations
            ;;
        --full|"")
            info "=== Déploiement complet ==="
            create_directories
            fix_permissions
            install_dependencies
            run_migrations
            clear_cache
            verify_config
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
    
    info ""
    info "=== Opération terminée avec succès ==="
    info ""
    info "Prochaines étapes:"
    info "1. Vérifier les logs Apache: sudo tail -f /var/log/apache2/konvix-error.log"
    info "2. Vérifier les logs Symfony: sudo tail -f $PROJECT_DIR/var/log/prod.log"
    info "3. Tester le site: https://konvix.fr"
}

# Exécuter le script
main "$@"
