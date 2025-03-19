#!/bin/bash

# 헬스 체크 스크립트
# 애플리케이션 및 서버 상태를 확인하는 스크립트

set -e

# 설정 변수
APP_NAME="rocketsourcer"
APP_URL=${1:-"https://${APP_NAME}.com"}
ENDPOINTS=(
    "/api/health"
    "/api/status"
)
TIMEOUT=10
SLACK_WEBHOOK_URL=${SLACK_WEBHOOK_URL:-""}
NOTIFY_SLACK=${NOTIFY_SLACK:-"true"}
CHECK_DISK=${CHECK_DISK:-"true"}
CHECK_MEMORY=${CHECK_MEMORY:-"true"}
CHECK_LOAD=${CHECK_LOAD:-"true"}
CHECK_SERVICES=${CHECK_SERVICES:-"true"}
DISK_THRESHOLD=90
MEMORY_THRESHOLD=90
LOAD_THRESHOLD=5
SERVICES=(
    "nginx"
    "php-fpm"
    "mysql"
    "redis"
    "supervisor"
)

# 로그 함수
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Slack 알림 함수
notify_slack() {
    local status=$1
    local message=$2
    local color=$3
    
    if [ "${NOTIFY_SLACK}" != "true" ] || [ -z "${SLACK_WEBHOOK_URL}" ]; then
        return 0
    fi
    
    local payload="{
        \"attachments\": [
            {
                \"fallback\": \"${APP_NAME} 헬스 체크: ${status}\",
                \"color\": \"${color}\",
                \"title\": \"${APP_NAME} 헬스 체크: ${status}\",
                \"text\": \"${message}\",
                \"fields\": [
                    {
                        \"title\": \"서버\",
                        \"value\": \"$(hostname)\",
                        \"short\": true
                    },
                    {
                        \"title\": \"시간\",
                        \"value\": \"$(date '+%Y-%m-%d %H:%M:%S')\",
                        \"short\": true
                    }
                ],
                \"footer\": \"RocketSourcer 헬스 체크\"
            }
        ]
    }"
    
    curl -s -X POST -H 'Content-type: application/json' --data "${payload}" ${SLACK_WEBHOOK_URL} > /dev/null
}

# API 엔드포인트 체크
check_endpoints() {
    local all_passed=true
    local failed_endpoints=""
    
    for endpoint in "${ENDPOINTS[@]}"; do
        log "엔드포인트 체크 중: ${APP_URL}${endpoint}"
        
        local response=$(curl -s -o /dev/null -w "%{http_code}" -m ${TIMEOUT} "${APP_URL}${endpoint}")
        
        if [ "${response}" = "200" ]; then
            log "✅ 엔드포인트 정상: ${endpoint} (${response})"
        else
            log "❌ 엔드포인트 오류: ${endpoint} (${response})"
            all_passed=false
            failed_endpoints="${failed_endpoints}\n- ${endpoint} (${response})"
        fi
    done
    
    if [ "${all_passed}" = "true" ]; then
        return 0
    else
        echo -e "다음 엔드포인트에서 오류가 발생했습니다:${failed_endpoints}"
        return 1
    fi
}

# 디스크 사용량 체크
check_disk_usage() {
    if [ "${CHECK_DISK}" != "true" ]; then
        return 0
    fi
    
    log "디스크 사용량 체크 중..."
    
    local disk_usage=$(df -h / | grep -v Filesystem | awk '{print $5}' | sed 's/%//')
    
    if [ "${disk_usage}" -lt "${DISK_THRESHOLD}" ]; then
        log "✅ 디스크 사용량 정상: ${disk_usage}%"
        return 0
    else
        log "❌ 디스크 사용량 경고: ${disk_usage}% (임계값: ${DISK_THRESHOLD}%)"
        echo "디스크 사용량이 임계값을 초과했습니다: ${disk_usage}% (임계값: ${DISK_THRESHOLD}%)"
        return 1
    fi
}

# 메모리 사용량 체크
check_memory_usage() {
    if [ "${CHECK_MEMORY}" != "true" ]; then
        return 0
    fi
    
    log "메모리 사용량 체크 중..."
    
    local memory_usage=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
    
    if [ "${memory_usage}" -lt "${MEMORY_THRESHOLD}" ]; then
        log "✅ 메모리 사용량 정상: ${memory_usage}%"
        return 0
    else
        log "❌ 메모리 사용량 경고: ${memory_usage}% (임계값: ${MEMORY_THRESHOLD}%)"
        echo "메모리 사용량이 임계값을 초과했습니다: ${memory_usage}% (임계값: ${MEMORY_THRESHOLD}%)"
        return 1
    fi
}

# 시스템 로드 체크
check_system_load() {
    if [ "${CHECK_LOAD}" != "true" ]; then
        return 0
    fi
    
    log "시스템 로드 체크 중..."
    
    local load=$(uptime | awk -F'load average:' '{print $2}' | awk -F',' '{print $1}' | tr -d ' ')
    
    if (( $(echo "${load} < ${LOAD_THRESHOLD}" | bc -l) )); then
        log "✅ 시스템 로드 정상: ${load}"
        return 0
    else
        log "❌ 시스템 로드 경고: ${load} (임계값: ${LOAD_THRESHOLD})"
        echo "시스템 로드가 임계값을 초과했습니다: ${load} (임계값: ${LOAD_THRESHOLD})"
        return 1
    fi
}

# 서비스 상태 체크
check_services_status() {
    if [ "${CHECK_SERVICES}" != "true" ]; then
        return 0
    fi
    
    log "서비스 상태 체크 중..."
    
    local all_passed=true
    local failed_services=""
    
    for service in "${SERVICES[@]}"; do
        log "서비스 체크 중: ${service}"
        
        if systemctl is-active --quiet ${service}; then
            log "✅ 서비스 정상: ${service}"
        else
            log "❌ 서비스 오류: ${service}"
            all_passed=false
            failed_services="${failed_services}\n- ${service}"
        fi
    done
    
    if [ "${all_passed}" = "true" ]; then
        return 0
    else
        echo -e "다음 서비스에서 오류가 발생했습니다:${failed_services}"
        return 1
    fi
}

# 메인 함수
main() {
    log "헬스 체크 시작: ${APP_NAME}"
    
    local status="성공"
    local message="모든 시스템이 정상 작동 중입니다."
    local color="good"
    local has_error=false
    local error_messages=""
    
    # API 엔드포인트 체크
    if ! endpoint_result=$(check_endpoints); then
        has_error=true
        error_messages="${error_messages}\n${endpoint_result}"
    fi
    
    # 디스크 사용량 체크
    if ! disk_result=$(check_disk_usage); then
        has_error=true
        error_messages="${error_messages}\n${disk_result}"
    fi
    
    # 메모리 사용량 체크
    if ! memory_result=$(check_memory_usage); then
        has_error=true
        error_messages="${error_messages}\n${memory_result}"
    fi
    
    # 시스템 로드 체크
    if ! load_result=$(check_system_load); then
        has_error=true
        error_messages="${error_messages}\n${load_result}"
    fi
    
    # 서비스 상태 체크
    if ! services_result=$(check_services_status); then
        has_error=true
        error_messages="${error_messages}\n${services_result}"
    fi
    
    # 결과 처리
    if [ "${has_error}" = "true" ]; then
        status="실패"
        message="다음 문제가 발생했습니다:${error_messages}"
        color="danger"
        
        # Slack 알림
        notify_slack "${status}" "${message}" "${color}"
        
        log "❌ 헬스 체크 실패"
        echo -e "${message}"
        exit 1
    else
        # Slack 알림
        notify_slack "${status}" "${message}" "${color}"
        
        log "✅ 헬스 체크 성공"
        echo "${message}"
        exit 0
    fi
}

# 스크립트 실행
main 