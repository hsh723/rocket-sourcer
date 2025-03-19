#!/bin/bash

# 트래픽 전환 스크립트
# 이 스크립트는 블루-그린 또는 카나리 배포에서 트래픽을 전환하는 데 사용됩니다.

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 로깅 함수
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
    exit 1
}

# 사용법 표시
usage() {
    echo "사용법: $0 <percentage> [mode]"
    echo "  <percentage>: 새 버전으로 전환할 트래픽 비율 (0-100)"
    echo "  [mode]: 배포 모드 (blue-green 또는 canary, 기본값: canary)"
    echo "예시:"
    echo "  $0 10 canary    # 10% 트래픽을 카나리 환경으로 전환"
    echo "  $0 100 blue-green # 100% 트래픽을 블루-그린 환경으로 전환"
    exit 1
}

# 인수 확인
if [ $# -lt 1 ]; then
    usage
fi

PERCENTAGE=$1
MODE=${2:-canary}

# 비율 검증
if ! [[ "$PERCENTAGE" =~ ^[0-9]+$ ]] || [ "$PERCENTAGE" -lt 0 ] || [ "$PERCENTAGE" -gt 100 ]; then
    error "유효하지 않은 비율: $PERCENTAGE. 0에서 100 사이의 정수여야 합니다."
fi

# 모드 검증
if [[ "$MODE" != "blue-green" && "$MODE" != "canary" ]]; then
    error "유효하지 않은 모드: $MODE. 'blue-green' 또는 'canary'여야 합니다."
fi

# Nginx 설정 파일 경로
NGINX_CONF="/etc/nginx/conf.d/rocketsourcer.conf"
NGINX_UPSTREAM_CONF="/etc/nginx/conf.d/upstream.conf"

# 블루-그린 배포 트래픽 전환
shift_blue_green() {
    local percentage=$1
    log "블루-그린 배포: $percentage% 트래픽을 새 환경으로 전환 중..."
    
    # 현재 활성 환경 확인
    local current_target=$(readlink /var/www/rocketsourcer/current)
    local blue_dir="/var/www/rocketsourcer/blue"
    local green_dir="/var/www/rocketsourcer/green"
    
    # 현재 비활성 환경 결정
    local inactive_env
    if [[ "$current_target" == *"blue"* ]]; then
        inactive_env="green"
        log "현재 활성 환경: 블루, 전환 대상: 그린"
    else
        inactive_env="blue"
        log "현재 활성 환경: 그린, 전환 대상: 블루"
    fi
    
    local target_dir="/var/www/rocketsourcer/${inactive_env}"
    
    if [ "$percentage" -eq 0 ]; then
        # 롤백: 이전 환경으로 100% 트래픽 전환
        log "롤백: 이전 환경으로 100% 트래픽 전환 중..."
        ln -sfn "$current_target" /var/www/rocketsourcer/current
    elif [ "$percentage" -eq 100 ]; then
        # 완료: 새 환경으로 100% 트래픽 전환
        log "완료: 새 환경으로 100% 트래픽 전환 중..."
        ln -sfn "$target_dir" /var/www/rocketsourcer/current
    else
        # 부분 전환: 가중치 기반 로드 밸런싱 설정
        log "부분 전환: $percentage% 트래픽을 새 환경으로 전환 중..."
        
        # 업스트림 설정 업데이트
        cat > "$NGINX_UPSTREAM_CONF" << EOF
upstream backend {
    server localhost:8080 weight=$((100 - percentage));
    server localhost:8081 weight=$percentage;
}
EOF
    fi
    
    # Nginx 설정 테스트
    nginx -t
    
    if [ $? -ne 0 ]; then
        error "Nginx 설정 테스트 실패"
    fi
    
    # Nginx 재시작
    systemctl reload nginx
    
    success "블루-그린 배포: $percentage% 트래픽 전환 완료"
}

# 카나리 배포 트래픽 전환
shift_canary() {
    local percentage=$1
    log "카나리 배포: $percentage% 트래픽을 카나리 환경으로 전환 중..."
    
    if [ "$percentage" -eq 0 ]; then
        # 롤백: 카나리 배포 중단
        log "롤백: 카나리 배포 중단 중..."
        
        # 업스트림 설정 업데이트
        cat > "$NGINX_UPSTREAM_CONF" << EOF
upstream backend {
    server localhost:8080 weight=100;
    server localhost:8082 weight=0;
}
EOF
    elif [ "$percentage" -eq 100 ]; then
        # 완료: 카나리 환경으로 100% 트래픽 전환
        log "완료: 카나리 환경으로 100% 트래픽 전환 중..."
        
        # 카나리 환경을 현재 환경으로 설정
        ln -sfn /var/www/rocketsourcer/canary /var/www/rocketsourcer/current
        
        # 업스트림 설정 업데이트
        cat > "$NGINX_UPSTREAM_CONF" << EOF
upstream backend {
    server localhost:8080 weight=0;
    server localhost:8082 weight=100;
}
EOF
    else
        # 부분 전환: 가중치 기반 로드 밸런싱 설정
        log "부분 전환: $percentage% 트래픽을 카나리 환경으로 전환 중..."
        
        # 업스트림 설정 업데이트
        cat > "$NGINX_UPSTREAM_CONF" << EOF
upstream backend {
    server localhost:8080 weight=$((100 - percentage));
    server localhost:8082 weight=$percentage;
}
EOF
    fi
    
    # Nginx 설정 테스트
    nginx -t
    
    if [ $? -ne 0 ]; then
        error "Nginx 설정 테스트 실패"
    fi
    
    # Nginx 재시작
    systemctl reload nginx
    
    success "카나리 배포: $percentage% 트래픽 전환 완료"
}

# 메인 함수
main() {
    log "트래픽 전환 시작 (모드: $MODE, 비율: $PERCENTAGE%)"
    
    # 루트 권한 확인
    if [[ $EUID -ne 0 ]]; then
        error "이 스크립트는 루트 권한으로 실행해야 합니다."
    fi
    
    # 모드에 따라 다른 전환 함수 호출
    case "$MODE" in
        "blue-green")
            shift_blue_green "$PERCENTAGE"
            ;;
        "canary")
            shift_canary "$PERCENTAGE"
            ;;
        *)
            error "지원되지 않는 모드: $MODE"
            ;;
    esac
    
    success "트래픽 전환 완료 (모드: $MODE, 비율: $PERCENTAGE%)"
}

# 스크립트 실행
main 