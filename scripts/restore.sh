#!/bin/bash

###############################################################################
# RESTORE SCRIPT - E-Commerce Application
# Restores database, redis, elasticsearch and application files from backup
###############################################################################

set -e

# Configuration
BACKUP_DIR="./backups"

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

# List available backups
list_backups() {
    log_info "Available backups:"
    echo ""

    if [ ! -d "${BACKUP_DIR}" ] || [ -z "$(ls -A ${BACKUP_DIR} 2>/dev/null)" ]; then
        log_warn "No backups found in ${BACKUP_DIR}"
        exit 1
    fi

    local index=1
    for dir in $(ls -dt ${BACKUP_DIR}/*/); do
        local timestamp=$(basename ${dir})
        local size=$(du -sh ${dir} | cut -f1)
        local date=""

        if [ -f "${dir}/metadata.json" ]; then
            date=$(grep '"date"' ${dir}/metadata.json | cut -d '"' -f 4)
        fi

        echo "  ${index}. ${timestamp} - ${size} - ${date}"
        ((index++))
    done

    echo ""
}

# Select backup
select_backup() {
    list_backups

    local backups=($(ls -dt ${BACKUP_DIR}/*/))
    local count=${#backups[@]}

    read -p "Select backup number (1-${count}) or 'latest' for most recent: " selection

    if [ "${selection}" == "latest" ]; then
        RESTORE_PATH="${backups[0]}"
    elif [[ "${selection}" =~ ^[0-9]+$ ]] && [ "${selection}" -ge 1 ] && [ "${selection}" -le "${count}" ]; then
        RESTORE_PATH="${backups[$((selection-1))]}"
    else
        log_error "Invalid selection"
        exit 1
    fi

    RESTORE_PATH="${RESTORE_PATH%/}"  # Remove trailing slash
    log_info "Selected backup: ${RESTORE_PATH}"
}

# Confirm restore
confirm_restore() {
    log_warn "⚠️  WARNING: This will OVERWRITE current data!"
    echo ""
    echo "Backup to restore: ${RESTORE_PATH}"

    if [ -f "${RESTORE_PATH}/metadata.json" ]; then
        echo ""
        echo "Backup Information:"
        cat "${RESTORE_PATH}/metadata.json" | grep -E '"(date|git_commit|git_branch)"' | sed 's/^/  /'
    fi

    echo ""
    read -p "Are you sure you want to continue? (yes/no): " confirm

    if [ "${confirm}" != "yes" ]; then
        log_info "Restore cancelled by user"
        exit 0
    fi
}

# Create backup before restore
backup_current_state() {
    log_info "Creating safety backup of current state..."

    if [ -f "./scripts/backup.sh" ]; then
        ./scripts/backup.sh
    else
        log_warn "Backup script not found, skipping safety backup"
    fi
}

# Stop application
stop_application() {
    log_info "Stopping application services..."
    docker-compose stop app web queue-worker 2>/dev/null || true
}

# Start application
start_application() {
    log_info "Starting application services..."
    docker-compose up -d

    # Wait for services to be healthy
    log_info "Waiting for services to be healthy..."
    sleep 5
}

# Restore PostgreSQL Database
restore_database() {
    log_info "Restoring PostgreSQL database..."

    if [ ! -f "${RESTORE_PATH}/database.dump" ]; then
        log_error "Database backup file not found: ${RESTORE_PATH}/database.dump"
        return 1
    fi

    # Drop and recreate database
    docker-compose exec -T db psql -U laravel -c "DROP DATABASE IF EXISTS laravel_restore;" 2>/dev/null || true
    docker-compose exec -T db psql -U laravel -c "CREATE DATABASE laravel_restore;" 2>/dev/null

    # Restore from custom format dump
    cat "${RESTORE_PATH}/database.dump" | docker-compose exec -T db pg_restore \
        -U laravel \
        -d laravel_restore \
        --no-owner \
        --no-acl \
        --verbose 2>&1 | grep -v "ERROR: role" || true

    # Swap databases
    docker-compose exec -T db psql -U laravel -c "
        SELECT pg_terminate_backend(pid)
        FROM pg_stat_activity
        WHERE datname = 'laravel' AND pid <> pg_backend_pid();
    " 2>/dev/null || true

    docker-compose exec -T db psql -U laravel -c "ALTER DATABASE laravel RENAME TO laravel_old;" 2>/dev/null || true
    docker-compose exec -T db psql -U laravel -c "ALTER DATABASE laravel_restore RENAME TO laravel;"
    docker-compose exec -T db psql -U laravel -c "DROP DATABASE IF EXISTS laravel_old;" 2>/dev/null || true

    log_info "Database restore completed ✓"
}

# Restore Redis Data
restore_redis() {
    log_info "Restoring Redis data..."

    if [ ! -f "${RESTORE_PATH}/redis.rdb" ]; then
        log_warn "Redis backup file not found, skipping..."
        return 0
    fi

    # Stop Redis
    docker-compose stop redis

    # Copy dump file
    docker cp "${RESTORE_PATH}/redis.rdb" laravel_redis:/data/dump.rdb

    # Start Redis
    docker-compose start redis

    # Wait for Redis to load data
    sleep 2

    log_info "Redis restore completed ✓"
}

# Restore Elasticsearch Indices
restore_elasticsearch() {
    log_info "Restoring Elasticsearch indices..."

    if [ ! -f "${RESTORE_PATH}/elasticsearch_products.json" ]; then
        log_warn "Elasticsearch backup file not found, skipping..."
        return 0
    fi

    # Delete existing index
    docker-compose exec -T elasticsearch curl -s -X DELETE "localhost:9200/products" 2>/dev/null || true

    # Recreate index with mapping
    if [ -f "${RESTORE_PATH}/elasticsearch_mapping.json" ]; then
        docker-compose exec -T elasticsearch curl -s -X PUT "localhost:9200/products" \
            -H 'Content-Type: application/json' \
            -d @"${RESTORE_PATH}/elasticsearch_mapping.json"
    fi

    # Reindex from database (safer than restoring JSON)
    docker-compose exec -T app php artisan elasticsearch:index --recreate

    log_info "Elasticsearch restore completed ✓"
}

# Restore Application Files
restore_application() {
    log_info "Restoring application files..."

    if [ ! -f "${RESTORE_PATH}/application.tar.gz" ]; then
        log_warn "Application backup file not found, skipping..."
        return 0
    fi

    # Extract files
    tar -xzf "${RESTORE_PATH}/application.tar.gz" -C .

    # Set permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    log_info "Application files restore completed ✓"
}

# Restore Storage Files
restore_storage() {
    log_info "Restoring storage files..."

    if [ ! -f "${RESTORE_PATH}/storage.tar.gz" ]; then
        log_warn "Storage backup file not found, skipping..."
        return 0
    fi

    # Create directory if not exists
    mkdir -p storage/app/public

    # Extract files
    tar -xzf "${RESTORE_PATH}/storage.tar.gz" -C storage/app/public

    log_info "Storage files restore completed ✓"
}

# Post-restore tasks
post_restore_tasks() {
    log_info "Running post-restore tasks..."

    # Clear cache
    docker-compose exec -T app php artisan optimize:clear

    # Regenerate cache
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache

    # Run migrations (if any new ones)
    docker-compose exec -T app php artisan migrate --force

    log_info "Post-restore tasks completed ✓"
}

# Verify restore
verify_restore() {
    log_info "Verifying restore..."

    # Check database connection
    if ! docker-compose exec -T db psql -U laravel -d laravel -c "SELECT 1" &>/dev/null; then
        log_error "Database connection failed"
        return 1
    fi

    # Check Redis connection
    if ! docker-compose exec -T redis redis-cli ping &>/dev/null; then
        log_error "Redis connection failed"
        return 1
    fi

    # Check Elasticsearch
    if ! docker-compose exec -T elasticsearch curl -sf http://localhost:9200/_cluster/health &>/dev/null; then
        log_error "Elasticsearch connection failed"
        return 1
    fi

    # Check application
    if ! docker-compose exec -T app php artisan tinker --execute='echo "OK";' | grep -q OK; then
        log_error "Application check failed"
        return 1
    fi

    log_info "Verification completed ✓"
    return 0
}

# Main execution
main() {
    log_info "Starting restore process..."

    # Check if docker-compose is available
    if ! command -v docker-compose &> /dev/null; then
        log_error "docker-compose not found. Please install docker-compose."
        exit 1
    fi

    # Select backup
    if [ -z "$1" ]; then
        select_backup
    else
        RESTORE_PATH="${BACKUP_DIR}/$1"
        if [ ! -d "${RESTORE_PATH}" ]; then
            log_error "Backup not found: ${RESTORE_PATH}"
            exit 1
        fi
    fi

    # Confirm restore
    confirm_restore

    # Create safety backup
    backup_current_state

    # Stop application
    stop_application

    # Perform restore
    restore_database
    restore_redis
    restore_application
    restore_storage

    # Start application
    start_application

    # Post-restore tasks
    restore_elasticsearch
    post_restore_tasks

    # Verify restore
    if verify_restore; then
        log_info "Restore completed successfully!"
        log_info "Restored from: ${RESTORE_PATH}"
    else
        log_error "Restore completed with errors. Please check the logs."
        exit 1
    fi
}

# Run main function
main "$@"
