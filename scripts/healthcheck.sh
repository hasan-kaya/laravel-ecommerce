#!/bin/bash

###############################################################################
# HEALTH CHECK SCRIPT - E-Commerce Application
# Monitors system health and alerts on failures
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
HEALTHCHECK_TIMEOUT=5
CRITICAL_FAILURES=0
WARNING_COUNT=0

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

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_fail() {
    echo -e "${RED}[✗]${NC} $1"
}

# Check Docker Compose
check_docker() {
    if ! command -v docker-compose &> /dev/null; then
        log_fail "Docker Compose not installed"
        ((CRITICAL_FAILURES++))
        return 1
    fi
    log_success "Docker Compose available"
}

# Check containers are running
check_containers() {
    log_info "Checking containers..."

    local services=("web" "app" "db" "redis" "elasticsearch" "queue-worker")
    local failed=0

    for service in "${services[@]}"; do
        local running=$(docker-compose ps ${service} 2>/dev/null | grep -c "Up" || echo "0")

        if [ "${running}" -gt 0 ]; then
            log_success "${service}: Running (${running} instances)"
        else
            log_fail "${service}: Not running"
            ((failed++))
        fi
    done

    if [ ${failed} -gt 0 ]; then
        ((CRITICAL_FAILURES++))
        return 1
    fi

    return 0
}

# Check PostgreSQL
check_database() {
    log_info "Checking PostgreSQL..."

    if docker-compose exec -T db pg_isready -U laravel &>/dev/null; then
        # Check if database exists and is accessible
        if docker-compose exec -T db psql -U laravel -d laravel -c "SELECT 1" &>/dev/null; then
            # Check database size
            local db_size=$(docker-compose exec -T db psql -U laravel -d laravel -t -c "SELECT pg_size_pretty(pg_database_size('laravel'));" | xargs)

            # Check active connections
            local connections=$(docker-compose exec -T db psql -U laravel -d laravel -t -c "SELECT count(*) FROM pg_stat_activity WHERE datname='laravel';" | xargs)

            log_success "Database: Healthy (Size: ${db_size}, Connections: ${connections})"
            return 0
        else
            log_fail "Database: Cannot access database"
            ((CRITICAL_FAILURES++))
            return 1
        fi
    else
        log_fail "Database: Not ready"
        ((CRITICAL_FAILURES++))
        return 1
    fi
}

# Check Redis
check_redis() {
    log_info "Checking Redis..."

    if docker-compose exec -T redis redis-cli ping 2>/dev/null | grep -q "PONG"; then
        # Check memory usage
        local memory=$(docker-compose exec -T redis redis-cli INFO memory 2>/dev/null | grep "used_memory_human:" | cut -d: -f2 | tr -d '\r')

        # Check connected clients
        local clients=$(docker-compose exec -T redis redis-cli INFO clients 2>/dev/null | grep "connected_clients:" | cut -d: -f2 | tr -d '\r')

        log_success "Redis: Healthy (Memory: ${memory}, Clients: ${clients})"
        return 0
    else
        log_fail "Redis: Not responding"
        ((CRITICAL_FAILURES++))
        return 1
    fi
}

# Check Elasticsearch
check_elasticsearch() {
    log_info "Checking Elasticsearch..."

    local health=$(docker-compose exec -T elasticsearch curl -sf http://localhost:9200/_cluster/health 2>/dev/null)

    if [ -n "${health}" ]; then
        local status=$(echo ${health} | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
        local nodes=$(echo ${health} | grep -o '"number_of_nodes":[0-9]*' | cut -d: -f2)

        if [ "${status}" == "green" ] || [ "${status}" == "yellow" ]; then
            # Check index status
            local index_count=$(docker-compose exec -T elasticsearch curl -sf http://localhost:9200/_cat/indices?format=json 2>/dev/null | grep -o "products" | wc -l)

            log_success "Elasticsearch: Healthy (Status: ${status}, Nodes: ${nodes}, Indices: ${index_count})"

            if [ "${status}" == "yellow" ]; then
                log_warn "Elasticsearch status is yellow (acceptable for single node)"
                ((WARNING_COUNT++))
            fi
            return 0
        else
            log_fail "Elasticsearch: Unhealthy (Status: ${status})"
            ((CRITICAL_FAILURES++))
            return 1
        fi
    else
        log_fail "Elasticsearch: Not responding"
        ((CRITICAL_FAILURES++))
        return 1
    fi
}

# Check Nginx
check_nginx() {
    log_info "Checking Nginx..."

    if docker-compose exec -T web nginx -t &>/dev/null; then
        # Check if port is accessible (any HTTP response is valid)
        local port=${DOCKER_APP_PORT:-8080}
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:${port} 2>/dev/null || echo "000")

        if [ "${http_code}" != "000" ] && [ "${http_code}" != "" ]; then
            log_success "Nginx: Healthy (Port: ${port}, HTTP: ${http_code})"
            return 0
        else
            log_fail "Nginx: Port ${port} not responding"
            ((CRITICAL_FAILURES++))
            return 1
        fi
    else
        log_fail "Nginx: Configuration invalid"
        ((CRITICAL_FAILURES++))
        return 1
    fi
}

# Check Application
check_application() {
    log_info "Checking Laravel Application..."

    # Check if artisan is accessible
    if docker-compose exec -T app php artisan --version &>/dev/null; then
        # Check database connectivity from app
        if docker-compose exec -T app php artisan tinker --execute='DB::connection()->getPdo(); echo "OK";' 2>/dev/null | grep -q "OK"; then
            log_success "Application: Healthy"
            return 0
        else
            log_fail "Application: Cannot connect to database"
            ((CRITICAL_FAILURES++))
            return 1
        fi
    else
        log_fail "Application: Laravel not accessible"
        ((CRITICAL_FAILURES++))
        return 1
    fi
}

# Check Queue Workers
check_queue_workers() {
    log_info "Checking Queue Workers..."

    local worker_count=$(docker-compose ps queue-worker 2>/dev/null | grep -c "Up" || echo "0")

    if [ "${worker_count}" -gt 0 ]; then
        log_success "Queue Workers: Running (${worker_count} workers)"

        # Check if workers are processing jobs
        local queue_size=$(docker-compose exec -T redis redis-cli LLEN queues:payments 2>/dev/null || echo "0")
        log_info "Queue size: ${queue_size} jobs pending"

        return 0
    else
        log_fail "Queue Workers: No workers running"
        ((WARNING_COUNT++))
        return 1
    fi
}

# Check Disk Space
check_disk_space() {
    log_info "Checking Disk Space..."

    # Check volumes
    local volumes=("db_data" "redis_data" "elasticsearch_data")

    for volume in "${volumes[@]}"; do
        local size=$(docker volume inspect ecommerce_${volume} --format '{{ .Mountpoint }}' 2>/dev/null | xargs du -sh 2>/dev/null | cut -f1 || echo "N/A")
        log_info "${volume}: ${size}"
    done

    # Check host disk space
    local disk_usage=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')

    if [ "${disk_usage}" -lt 80 ]; then
        log_success "Disk Space: ${disk_usage}% used"
    elif [ "${disk_usage}" -lt 90 ]; then
        log_warn "Disk Space: ${disk_usage}% used (Warning)"
        ((WARNING_COUNT++))
    else
        log_fail "Disk Space: ${disk_usage}% used (Critical)"
        ((CRITICAL_FAILURES++))
    fi
}

# Check Resource Usage
check_resources() {
    log_info "Checking Resource Usage..."

    # CPU and Memory usage
    docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null | grep -E "(laravel|ecommerce)" || log_warn "Resource stats not available"
}

# Check Logs for Errors
check_logs() {
    log_info "Checking Recent Logs for Errors..."

    local error_count=$(docker-compose logs --tail=100 app 2>/dev/null | grep -ci "error" || echo "0")

    if [ "${error_count}" -gt 0 ]; then
        log_warn "Found ${error_count} errors in recent logs"
        ((WARNING_COUNT++))

        # Show last 5 errors
        echo ""
        log_info "Last 5 errors:"
        docker-compose logs --tail=100 app 2>/dev/null | grep -i "error" | tail -5
    else
        log_success "No errors in recent logs"
    fi
}

# Generate Health Report
generate_report() {
    echo ""
    echo "=========================================="
    echo "         HEALTH CHECK REPORT"
    echo "=========================================="
    echo "Timestamp: $(date)"
    echo ""
    echo "Status Summary:"
    echo "  Critical Failures: ${CRITICAL_FAILURES}"
    echo "  Warnings: ${WARNING_COUNT}"
    echo ""

    if [ ${CRITICAL_FAILURES} -eq 0 ] && [ ${WARNING_COUNT} -eq 0 ]; then
        echo -e "${GREEN} System Status: HEALTHY${NC}"
        echo ""
        return 0
    elif [ ${CRITICAL_FAILURES} -eq 0 ]; then
        echo -e "${YELLOW} System Status: DEGRADED (${WARNING_COUNT} warnings)${NC}"
        echo ""
        return 0
    else
        echo -e "${RED} System Status: UNHEALTHY (${CRITICAL_FAILURES} critical failures)${NC}"
        echo ""
        echo "Recommended Actions:"
        echo "  1. Review logs: docker-compose logs"
        echo "  2. Check container status: docker-compose ps"
        echo "  3. Consider rollback: ./scripts/rollback.sh"
        echo ""
        return 1
    fi
}

# Send Alert (placeholder for future integration)
send_alert() {
    local message="$1"

    # TODO: Integrate with monitoring systems (PagerDuty, Slack, etc.)
    # For now, just log to a file
    echo "[$(date)] ${message}" >> ./storage/logs/healthcheck-alerts.log
}

# Continuous monitoring mode
monitor_mode() {
    local interval=${1:-60}

    log_info "Starting continuous monitoring (interval: ${interval}s)"
    log_info "Press Ctrl+C to stop"
    echo ""

    while true; do
        clear
        main_checks

        if [ ${CRITICAL_FAILURES} -gt 0 ]; then
            send_alert "CRITICAL: ${CRITICAL_FAILURES} failures detected"
        fi

        sleep ${interval}
    done
}

# Main health checks
main_checks() {
    check_docker
    check_containers
    check_database
    check_redis
    check_elasticsearch
    check_nginx
    check_application
    check_queue_workers
    check_disk_space
    check_resources
    check_logs
    generate_report
}

# Main execution
main() {
    echo "E-Commerce Health Check"
    echo "======================="
    echo ""

    if [ "$1" == "monitor" ]; then
        monitor_mode ${2:-60}
    else
        main_checks
        exit $?
    fi
}

# Run main function
main "$@"
