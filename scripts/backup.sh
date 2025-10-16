#!/bin/bash

###############################################################################
# BACKUP SCRIPT - E-Commerce Application
# Creates full backup of database, redis, elasticsearch and application files
###############################################################################

set -e

# Configuration
BACKUP_DIR="./backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_PATH="${BACKUP_DIR}/${TIMESTAMP}"
RETENTION_DAYS=7

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Create backup directory
create_backup_dir() {
    log_info "Creating backup directory: ${BACKUP_PATH}"
    mkdir -p "${BACKUP_PATH}"
}

# Backup PostgreSQL Database
backup_database() {
    log_info "Backing up PostgreSQL database..."
    
    docker-compose exec -T db pg_dump -U laravel laravel \
        --format=custom \
        --compress=9 \
        --no-owner \
        --no-acl \
        > "${BACKUP_PATH}/database.dump"
    
    # Also create SQL format for easy inspection
    docker-compose exec -T db pg_dump -U laravel laravel \
        --format=plain \
        --no-owner \
        --no-acl \
        > "${BACKUP_PATH}/database.sql"
    
    log_info "Database backup completed: $(du -h ${BACKUP_PATH}/database.dump | cut -f1)"
}

# Backup Redis Data
backup_redis() {
    log_info "Backing up Redis data..."
    
    # Force Redis to save current state
    docker-compose exec -T redis redis-cli BGSAVE
    
    # Wait for background save to complete
    sleep 2
    
    # Copy the dump file
    docker cp laravel_redis:/data/dump.rdb "${BACKUP_PATH}/redis.rdb" 2>/dev/null || {
        log_warn "Redis dump file not found, skipping..."
        return 0
    }
    
    log_info "Redis backup completed: $(du -h ${BACKUP_PATH}/redis.rdb | cut -f1 2>/dev/null || echo 'N/A')"
}

# Backup Elasticsearch Indices
backup_elasticsearch() {
    log_info "Backing up Elasticsearch indices..."
    
    # Export products index
    docker-compose exec -T elasticsearch curl -s -X GET "localhost:9200/products/_search?size=10000" \
        > "${BACKUP_PATH}/elasticsearch_products.json"
    
    # Export index mapping
    docker-compose exec -T elasticsearch curl -s -X GET "localhost:9200/products/_mapping" \
        > "${BACKUP_PATH}/elasticsearch_mapping.json"
    
    log_info "Elasticsearch backup completed: $(du -h ${BACKUP_PATH}/elasticsearch_products.json | cut -f1)"
}

# Backup Application Files (excluding vendor and node_modules)
backup_application() {
    log_info "Backing up application files..."
    
    tar -czf "${BACKUP_PATH}/application.tar.gz" \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='backups' \
        --exclude='.git' \
        -C . \
        app config routes database resources public storage .env 2>/dev/null || {
            log_warn "Some files could not be backed up, continuing..."
        }
    
    log_info "Application backup completed: $(du -h ${BACKUP_PATH}/application.tar.gz | cut -f1)"
}

# Backup Storage Files (user uploads)
backup_storage() {
    log_info "Backing up storage files..."
    
    if [ -d "storage/app/public" ] && [ "$(ls -A storage/app/public 2>/dev/null)" ]; then
        tar -czf "${BACKUP_PATH}/storage.tar.gz" -C storage/app/public . 2>/dev/null
        log_info "Storage backup completed: $(du -h ${BACKUP_PATH}/storage.tar.gz | cut -f1)"
    else
        log_warn "No storage files found, skipping..."
    fi
}

# Create backup metadata
create_metadata() {
    log_info "Creating backup metadata..."
    
    cat > "${BACKUP_PATH}/metadata.json" <<EOF
{
    "timestamp": "${TIMESTAMP}",
    "date": "$(date -Iseconds)",
    "hostname": "$(hostname)",
    "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo 'N/A')",
    "git_branch": "$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'N/A')",
    "laravel_version": "$(docker-compose exec -T app php artisan --version | cut -d ' ' -f 3 || echo 'N/A')",
    "services": {
        "web": "$(docker-compose ps web 2>/dev/null | grep Up | wc -l)",
        "app": "$(docker-compose ps app 2>/dev/null | grep Up | wc -l)",
        "db": "$(docker-compose ps db 2>/dev/null | grep Up | wc -l)",
        "redis": "$(docker-compose ps redis 2>/dev/null | grep Up | wc -l)",
        "elasticsearch": "$(docker-compose ps elasticsearch 2>/dev/null | grep Up | wc -l)"
    }
}
EOF
    
    log_info "Metadata created"
}

# Cleanup old backups
cleanup_old_backups() {
    log_info "Cleaning up backups older than ${RETENTION_DAYS} days..."
    
    find "${BACKUP_DIR}" -type d -mtime +${RETENTION_DAYS} -exec rm -rf {} + 2>/dev/null || true
    
    log_info "Cleanup completed"
}

# Verify backup integrity
verify_backup() {
    log_info "Verifying backup integrity..."
    
    local errors=0
    
    # Check if files exist and are not empty
    for file in database.dump database.sql; do
        if [ ! -s "${BACKUP_PATH}/${file}" ]; then
            log_error "Backup file ${file} is missing or empty"
            ((errors++))
        fi
    done
    
    if [ $errors -eq 0 ]; then
        log_info "Backup verification passed ✓"
        return 0
    else
        log_error "Backup verification failed with ${errors} errors"
        return 1
    fi
}

# Create backup summary
create_summary() {
    log_info "Creating backup summary..."
    
    cat > "${BACKUP_PATH}/summary.txt" <<EOF
E-Commerce Backup Summary
========================

Timestamp: ${TIMESTAMP}
Date: $(date)
Backup Path: ${BACKUP_PATH}

Files Included:
---------------
EOF
    
    ls -lh "${BACKUP_PATH}" >> "${BACKUP_PATH}/summary.txt"
    
    echo "" >> "${BACKUP_PATH}/summary.txt"
    echo "Total Backup Size: $(du -sh ${BACKUP_PATH} | cut -f1)" >> "${BACKUP_PATH}/summary.txt"
    
    cat "${BACKUP_PATH}/summary.txt"
}

# Main execution
main() {
    log_info "Starting backup process..."
    log_info "Timestamp: ${TIMESTAMP}"
    
    # Check if docker-compose is available
    if ! command -v docker-compose &> /dev/null; then
        log_error "docker-compose not found. Please install docker-compose."
        exit 1
    fi
    
    # Check if containers are running
    if [ $(docker-compose ps -q | wc -l) -eq 0 ]; then
        log_error "No containers are running. Please start the application first."
        exit 1
    fi
    
    create_backup_dir
    
    # Perform backups
    backup_database
    backup_redis
    backup_elasticsearch
    backup_application
    backup_storage
    create_metadata
    
    # Verify and finalize
    if verify_backup; then
        create_summary
        cleanup_old_backups
        
        log_info "Backup completed successfully!"
        log_info "Backup location: ${BACKUP_PATH}"
        log_info "Total size: $(du -sh ${BACKUP_PATH} | cut -f1)"
    else
        log_error "Backup completed with errors. Please check the logs."
        exit 1
    fi
}

# Run main function
main "$@"
