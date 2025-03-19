#!/bin/bash

# 로켓소서 배포 스크립트
echo "로켓소서 배포를 시작합니다..."

# 배포 환경 설정
DEPLOY_ENV=${1:-production}
DEPLOY_DIR=$(pwd)
TIMESTAMP=$(date +%Y%m%d%H%M%S)
BACKUP_DIR="${DEPLOY_DIR}/backups/${TIMESTAMP}"

echo "배포 환경: ${DEPLOY_ENV}"
echo "배포 디렉토리: ${DEPLOY_DIR}"

# 백업 디렉토리 생성
mkdir -p "${BACKUP_DIR}"
echo "백업 디렉토리 생성: ${BACKUP_DIR}"

# 환경 설정 파일 백업
if [ -f "${DEPLOY_DIR}/.env" ]; then
    cp "${DEPLOY_DIR}/.env" "${BACKUP_DIR}/.env"
    echo ".env 파일 백업 완료"
fi

# Composer 의존성 설치/업데이트
echo "Composer 의존성 설치 중..."
composer install --no-dev --optimize-autoloader

# 환경 설정 파일 확인
if [ ! -f "${DEPLOY_DIR}/.env" ]; then
    echo ".env 파일이 없습니다. .env.example에서 복사합니다."
    cp "${DEPLOY_DIR}/.env.example" "${DEPLOY_DIR}/.env"
    echo ".env 생성 완료. 필요한 설정을 업데이트하세요."
fi

# 필요한 디렉토리 권한 설정
chmod -R 755 "${DEPLOY_DIR}/public"
chmod -R 755 "${DEPLOY_DIR}/logs"
echo "디렉토리 권한 설정 완료"

# 캐시 지우기
if [ -d "${DEPLOY_DIR}/cache" ]; then
    rm -rf "${DEPLOY_DIR}/cache/*"
    echo "캐시 정리 완료"
fi

echo "배포가 완료되었습니다."