#!/bin/bash

# 롤백 스크립트
# 이전 릴리스로 롤백하는 스크립트

set -e

# 설정 변수
APP_NAME="rocketsourcer"
DEPLOY_USER=$(whoami)
DEPLOY_PATH="/var/www/${APP_NAME}"
RELEASES_PATH="${DEPLOY_PATH}/releases"
CURRENT_LINK="${DEPLOY_PATH}/current"
HEALTH_CHECK_URL="https://${APP_NAME}.com/api/health"
HEALTH_CHECK_TIMEOUT=300
HEALTH_CHECK_INTERVAL=5
ROLLBACK_TO=${1:-"previous"} # 롤백할 릴리스 (기본값: 이전 릴리스)

# 로그 함수
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# 헬스 체크 함수
check_health() {
    log "헬스 체크 시작: ${HEALTH_CHECK_URL}"
    
    local timeout_counter=0
    local health_status=0
    
    while [ ${timeout_counter} -lt ${HEALTH_CHECK_TIMEOUT} ]; do
        if curl -s -o /dev/null -w "%{http_code}" ${HEALTH_CHECK_URL} | grep -q "200"; then
            log "헬스 체크 성공!"
            health_status=1
            break
        else
            log "헬스 체크 실패. ${HEALTH_CHECK_INTERVAL}초 후 재시도..."
            sleep ${HEALTH_CHECK_INTERVAL}
            timeout_counter=$((timeout_counter + HEALTH_CHECK_INTERVAL))
        fi
    done
    
    if [ ${health_status} -eq 0 ]; then
        log "헬스 체크 타임아웃. 롤백 실패."
        return 1
    fi
    
    return 0
}

# 롤백 시작
log "롤백 시작: ${APP_NAME}"

# 현재 릴리스 확인
if [ ! -L "${CURRENT_LINK}" ]; then
    log "현재 릴리스 링크가 없습니다. 롤백 실패."
    exit 1
fi

CURRENT_RELEASE=$(readlink ${CURRENT_LINK})
log "현재 릴리스: ${CURRENT_RELEASE}"

# 롤백할 릴리스 결정
if [ "${ROLLBACK_TO}" = "previous" ]; then
    # 이전 릴리스 찾기
    TARGET_RELEASE=$(find ${RELEASES_PATH} -maxdepth 1 -type d | sort -r | sed -n 2p)
    
    if [ -z "${TARGET_RELEASE}" ]; then
        log "이전 릴리스를 찾을 수 없습니다. 롤백 실패."
        exit 1
    fi
else
    # 특정 릴리스로 롤백
    TARGET_RELEASE="${RELEASES_PATH}/${ROLLBACK_TO}"
    
    if [ ! -d "${TARGET_RELEASE}" ]; then
        log "지정한 릴리스를 찾을 수 없습니다: ${ROLLBACK_TO}. 롤백 실패."
        exit 1
    fi
fi

log "롤백할 릴리스: ${TARGET_RELEASE}"

# 롤백 확인
read -p "정말로 롤백하시겠습니까? (y/n): " confirm
if [ "${confirm}" != "y" ]; then
    log "롤백이 취소되었습니다."
    exit 0
fi

# 데이터베이스 백업
log "데이터베이스 백업 중..."
TIMESTAMP=$(date +%Y%m%d%H%M%S)
BACKUP_DIR="${DEPLOY_PATH}/backups"
mkdir -p ${BACKUP_DIR}
cd ${CURRENT_LINK}
php artisan db:backup --filename="${BACKUP_DIR}/rollback_${TIMESTAMP}.sql"

# 롤백 실행
log "롤백 실행 중..."
ln -sfn ${TARGET_RELEASE} ${CURRENT_LINK}

# PHP-FPM 재시작
log "PHP-FPM 재시작"
sudo systemctl reload php-fpm

# 헬스 체크
if ! check_health; then
    log "롤백 후 헬스 체크 실패. 원래 릴리스로 복원합니다."
    ln -sfn ${CURRENT_RELEASE} ${CURRENT_LINK}
    sudo systemctl reload php-fpm
    exit 1
fi

# 롤백 완료
log "롤백 완료: ${APP_NAME}"
log "현재 릴리스: ${TARGET_RELEASE}"

exit 0 