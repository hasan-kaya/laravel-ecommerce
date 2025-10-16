#!/bin/bash

###############################################################################
# ROLLBACK SCRIPT - E-Commerce Application
# Rolls back migrations, deployments, or specific components
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_prompt() {
    echo -e "${BLUE}[PROMPT]${NC} $1"
}

# Show usage
usage() {
    cat <<EOF
Usage: $0 [OPTION]

Rollback options:
  migration [steps]   Rollback database migrations (default: 1 step)
  deployment         Rollback to previous backup
  cache             Rollback and clear all caches
  elasticsearch     Rollback Elasticsearch index
  all               Full system rollback to last backup

Examples:
  $0 migration          # Rollback last migration
  $0 migration 3        # Rollback last 3 migrations
  $0 deployment         # Full deployment rollback
  $0 cache              # Clear all caches
  $0 elasticsearch      # Reindex Elasticsearch
  $0 all                # Full system rollback

EOF
    exit 1
}

# Check prerequisites
check_prerequisites() {
    if ! command -v docker-compose &> /dev/null; then
        log_error "docker-compose not found. Please install docker-compose."
        exit 1
    fi

    if [ $(docker-compose ps -q | wc -l) -eq 0 ]; then
        log_error "No containers are running. Please start the application first."
        exit 1
    fi
}

# Rollback migrations
rollback_migration() {
    local steps=${1:-1}

    log_warn "⚠️  This will rollback ${steps} migration(s)"
    read -p "Continue? (yes/no): " confirm

    if [ "${confirm}" != "yes" ]; then
        log_info "Rollback cancelled"
        exit 0
    fi

    log_info "Rolling back ${steps} migration(s)..."

    # Show what will be rolled back
    log_info "Migrations to rollback:"
    docker-compose exec -T app php artisan migrate:status | tail -n +3 | grep -E "^.*Ran.*$" | tail -n ${steps}

    echo ""
    read -p "Confirm rollback? (yes/no): " final_confirm

    if [ "${final_confirm}" != "yes" ]; then
        log_info "Rollback cancelled"
        exit 0
    fi

    # Perform rollback
    docker-compose exec -T app php artisan migrate:rollback --step=${steps}

    log_info "Migration rollback completed"

    # Show current status
    echo ""
    log_info "Current migration status:"
    docker-compose exec -T app php artisan migrate:status
}

# Rollback deployment (restore from latest backup)
rollback_deployment() {
    log_warn "⚠️  This will restore from the latest backup"
    log_warn "⚠️  Current data will be backed up before restore"

    read -p "Continue? (yes/no): " confirm

    if [ "${confirm}" != "yes" ]; then
        log_info "Rollback cancelled"
        exit 0
    fi

    log_info "Rolling back deployment..."

    # Check if restore script exists
    if [ ! -f "./scripts/restore.sh" ]; then
        log_error "Restore script not found: ./scripts/restore.sh"
        exit 1
    fi

    # Run restore with latest backup
    ./scripts/restore.sh latest

    log_info "Deployment rollback completed"
}

# Rollback cache
rollback_cache() {
    log_info "Clearing all caches..."

    # Clear Laravel cache
    docker-compose exec -T app php artisan optimize:clear

    # Clear Redis cache
    docker-compose exec -T redis redis-cli FLUSHDB

    # Clear Elasticsearch cache
    docker-compose exec -T elasticsearch curl -s -X POST "localhost:9200/_cache/clear"

    log_info "Cache rollback completed"

    # Regenerate necessary caches
    log_info "Regenerating config cache..."
    docker-compose exec -T app php artisan config:cache

    log_info "Cache regenerated"
}

# Rollback Elasticsearch
rollback_elasticsearch() {
    log_info "Rolling back Elasticsearch index..."

    log_warn "⚠️  This will delete and recreate the Elasticsearch index"
    read -p "Continue? (yes/no): " confirm

    if [ "${confirm}" != "yes" ]; then
        log_info "Rollback cancelled"
        exit 0
    fi

    # Delete and recreate index
    docker-compose exec -T app php artisan elasticsearch:index --recreate

    log_info "Elasticsearch rollback completed"
}

# Full system rollback
rollback_all() {
    log_warn "⚠️  FULL SYSTEM ROLLBACK"
    log_warn "⚠️  This will restore from the latest backup"
    log_warn "⚠️  All current data will be replaced"

    echo ""
    read -p "Are you ABSOLUTELY sure? Type 'ROLLBACK' to confirm: " confirm

    if [ "${confirm}" != "ROLLBACK" ]; then
        log_info "Rollback cancelled"
        exit 0
    fi

    log_info "Performing full system rollback..."

    # Rollback deployment (includes database, redis, files)
    rollback_deployment

    # Rollback cache
    rollback_cache

    # Rollback Elasticsearch
    rollback_elasticsearch

    log_info "Full system rollback completed"
}

# Emergency rollback (no confirmation)
emergency_rollback() {
    log_error "⚠️  EMERGENCY ROLLBACK ACTIVATED"

    log_info "Stopping application..."
    docker-compose stop app web queue-worker 2>/dev/null || true

    log_info "Restoring from latest backup..."
    if [ -f "./scripts/restore.sh" ]; then
        # Find latest backup
        LATEST_BACKUP=$(ls -dt ./backups/*/ | head -1)
        if [ -z "${LATEST_BACKUP}" ]; then
            log_error "No backups found!"
            exit 1
        fi

        ./scripts/restore.sh "$(basename ${LATEST_BACKUP})"
    else
        log_error "Restore script not found!"
        exit 1
    fi

    log_info "Emergency rollback completed"
}

# Create rollback checkpoint
create_checkpoint() {
    log_info "Creating rollback checkpoint..."

    if [ -f "./scripts/backup.sh" ]; then
        ./scripts/backup.sh
        log_info "Checkpoint created"
    else
        log_warn "Backup script not found, skipping checkpoint"
    fi
}

# List rollback options
list_options() {
    log_info "Available rollback operations:"
    echo ""
    echo "  1. Migration Rollback       - Rollback database migrations"
    echo "  2. Deployment Rollback      - Restore from latest backup"
    echo "  3. Cache Rollback           - Clear all caches"
    echo "  4. Elasticsearch Rollback   - Recreate search index"
    echo "  5. Full System Rollback     - Complete system restore"
    echo "  6. Emergency Rollback       - Immediate restore (no confirm)"
    echo "  7. Create Checkpoint        - Create backup before changes"
    echo ""
}

# Interactive mode
interactive_mode() {
    list_options

    read -p "Select option (1-7): " option

    case $option in
        1)
            read -p "Number of migrations to rollback (default: 1): " steps
            rollback_migration ${steps:-1}
            ;;
        2)
            rollback_deployment
            ;;
        3)
            rollback_cache
            ;;
        4)
            rollback_elasticsearch
            ;;
        5)
            rollback_all
            ;;
        6)
            emergency_rollback
            ;;
        7)
            create_checkpoint
            ;;
        *)
            log_error "Invalid option"
            exit 1
            ;;
    esac
}

# Main execution
main() {
    log_info "E-Commerce Rollback Script"
    echo ""

    check_prerequisites

    if [ $# -eq 0 ]; then
        interactive_mode
        exit 0
    fi

    case "$1" in
        migration)
            rollback_migration ${2:-1}
            ;;
        deployment)
            rollback_deployment
            ;;
        cache)
            rollback_cache
            ;;
        elasticsearch)
            rollback_elasticsearch
            ;;
        all)
            rollback_all
            ;;
        emergency)
            emergency_rollback
            ;;
        checkpoint)
            create_checkpoint
            ;;
        help|--help|-h)
            usage
            ;;
        *)
            log_error "Unknown option: $1"
            usage
            ;;
    esac
}

# Run main function
main "$@"
