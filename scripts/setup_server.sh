#!/bin/bash

# 서버 설정 스크립트
# 이 스크립트는 프로덕션 또는 스테이징 서버를 설정하는 데 사용됩니다.

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
}

# 사용법 표시
usage() {
    echo "사용법: $0 [옵션]"
    echo "옵션:"
    echo "  -e, --environment   환경 (production 또는 staging)"
    echo "  -h, --help          도움말 표시"
    exit 1
}

# 인수 파싱
ENVIRONMENT="production"

while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
        -e|--environment)
            ENVIRONMENT="$2"
            shift
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            error "알 수 없는 옵션: $1"
            usage
            ;;
    esac
done

# 환경 검증
if [[ "$ENVIRONMENT" != "production" && "$ENVIRONMENT" != "staging" ]]; then
    error "유효하지 않은 환경: $ENVIRONMENT. 'production' 또는 'staging'이어야 합니다."
    exit 1
fi

# 루트 권한 확인
if [[ $EUID -ne 0 ]]; then
    error "이 스크립트는 루트 권한으로 실행해야 합니다."
    exit 1
fi

# 시스템 업데이트
setup_system() {
    log "시스템 업데이트 중..."
    apt-get update
    apt-get upgrade -y
    
    log "기본 패키지 설치 중..."
    apt-get install -y curl wget git unzip zip htop vim software-properties-common gnupg lsb-release apt-transport-https ca-certificates
    
    success "시스템 업데이트 완료"
}

# 방화벽 설정
setup_firewall() {
    log "방화벽 설정 중..."
    apt-get install -y ufw
    
    # 기본 정책 설정
    ufw default deny incoming
    ufw default allow outgoing
    
    # SSH 허용
    ufw allow ssh
    
    # HTTP/HTTPS 허용
    ufw allow 80/tcp
    ufw allow 443/tcp
    
    # 방화벽 활성화
    echo "y" | ufw enable
    
    success "방화벽 설정 완료"
}

# Nginx 설치 및 설정
setup_nginx() {
    log "Nginx 설치 중..."
    apt-get install -y nginx
    
    log "Nginx 설정 파일 복사 중..."
    cp /var/www/rocketsourcer/current/config/server/nginx.conf /etc/nginx/nginx.conf
    
    # 가상 호스트 디렉토리 생성
    mkdir -p /etc/nginx/conf.d
    
    # SSL 디렉토리 생성
    mkdir -p /etc/nginx/ssl
    
    # Nginx 재시작
    systemctl restart nginx
    systemctl enable nginx
    
    success "Nginx 설정 완료"
}

# PHP 설치 및 설정
setup_php() {
    log "PHP 설치 중..."
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    
    apt-get install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-intl php8.1-redis php8.1-opcache
    
    log "PHP 설정 파일 복사 중..."
    cp /var/www/rocketsourcer/current/config/server/php-fpm.conf /etc/php/8.1/fpm/php-fpm.conf
    
    # PHP-FPM 재시작
    systemctl restart php8.1-fpm
    systemctl enable php8.1-fpm
    
    success "PHP 설정 완료"
}

# MySQL 설치 및 설정
setup_mysql() {
    log "MySQL 설치 중..."
    apt-get install -y mysql-server
    
    log "MySQL 설정 파일 복사 중..."
    cp /var/www/rocketsourcer/current/config/server/mysql.cnf /etc/mysql/conf.d/rocketsourcer.cnf
    
    # MySQL 재시작
    systemctl restart mysql
    systemctl enable mysql
    
    # MySQL 보안 설정
    log "MySQL 보안 설정 중..."
    mysql_secure_installation
    
    success "MySQL 설정 완료"
}

# Redis 설치 및 설정
setup_redis() {
    log "Redis 설치 중..."
    apt-get install -y redis-server
    
    log "Redis 설정 파일 복사 중..."
    cp /var/www/rocketsourcer/current/config/server/redis.conf /etc/redis/redis.conf
    
    # Redis 재시작
    systemctl restart redis-server
    systemctl enable redis-server
    
    success "Redis 설정 완료"
}

# Let's Encrypt 설치 및 SSL 인증서 발급
setup_ssl() {
    log "Certbot 설치 중..."
    apt-get install -y certbot python3-certbot-nginx
    
    # 도메인 설정
    if [[ "$ENVIRONMENT" == "production" ]]; then
        DOMAIN="rocketsourcer.com"
        EXTRA_DOMAIN="www.rocketsourcer.com"
    else
        DOMAIN="staging.rocketsourcer.com"
        EXTRA_DOMAIN=""
    fi
    
    log "SSL 인증서 발급 중 ($DOMAIN)..."
    if [[ -n "$EXTRA_DOMAIN" ]]; then
        certbot --nginx -d $DOMAIN -d $EXTRA_DOMAIN --non-interactive --agree-tos --email admin@rocketsourcer.com
    else
        certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@rocketsourcer.com
    fi
    
    # 인증서 자동 갱신 설정
    log "인증서 자동 갱신 설정 중..."
    echo "0 0,12 * * * root python -c 'import random; import time; time.sleep(random.random() * 3600)' && certbot renew -q" | tee -a /etc/crontab > /dev/null
    
    success "SSL 설정 완료"
}

# 배포 디렉토리 설정
setup_deploy_dirs() {
    log "배포 디렉토리 설정 중..."
    
    if [[ "$ENVIRONMENT" == "production" ]]; then
        APP_DIR="/var/www/rocketsourcer"
    else
        APP_DIR="/var/www/rocketsourcer-staging"
    fi
    
    # 디렉토리 생성
    mkdir -p $APP_DIR/releases
    mkdir -p $APP_DIR/shared/storage/app/public
    mkdir -p $APP_DIR/shared/storage/framework/cache
    mkdir -p $APP_DIR/shared/storage/framework/sessions
    mkdir -p $APP_DIR/shared/storage/framework/views
    mkdir -p $APP_DIR/shared/storage/logs
    
    # 권한 설정
    chown -R www-data:www-data $APP_DIR
    chmod -R 775 $APP_DIR/shared/storage
    
    # 블루-그린 배포를 위한 디렉토리 설정
    if [[ "$ENVIRONMENT" == "production" ]]; then
        mkdir -p $APP_DIR/blue
        mkdir -p $APP_DIR/green
        mkdir -p $APP_DIR/canary
        
        chown -R www-data:www-data $APP_DIR/blue
        chown -R www-data:www-data $APP_DIR/green
        chown -R www-data:www-data $APP_DIR/canary
    fi
    
    success "배포 디렉토리 설정 완료"
}

# 모니터링 도구 설치
setup_monitoring() {
    log "모니터링 도구 설치 중..."
    
    # Prometheus Node Exporter 설치
    log "Prometheus Node Exporter 설치 중..."
    wget https://github.com/prometheus/node_exporter/releases/download/v1.3.1/node_exporter-1.3.1.linux-amd64.tar.gz
    tar xvfz node_exporter-1.3.1.linux-amd64.tar.gz
    mv node_exporter-1.3.1.linux-amd64/node_exporter /usr/local/bin/
    rm -rf node_exporter-1.3.1.linux-amd64*
    
    # 서비스 파일 생성
    cat > /etc/systemd/system/node_exporter.service << EOF
[Unit]
Description=Prometheus Node Exporter
After=network.target

[Service]
Type=simple
User=node_exporter
Group=node_exporter
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF
    
    # 사용자 생성
    useradd -rs /bin/false node_exporter
    
    # 서비스 시작
    systemctl daemon-reload
    systemctl start node_exporter
    systemctl enable node_exporter
    
    success "모니터링 도구 설치 완료"
}

# 백업 설정
setup_backups() {
    log "백업 설정 중..."
    
    # 백업 디렉토리 생성
    if [[ "$ENVIRONMENT" == "production" ]]; then
        BACKUP_DIR="/var/backups/rocketsourcer"
    else
        BACKUP_DIR="/var/backups/rocketsourcer-staging"
    fi
    
    mkdir -p $BACKUP_DIR/database
    mkdir -p $BACKUP_DIR/files
    
    # 권한 설정
    chown -R www-data:www-data $BACKUP_DIR
    
    # 백업 스크립트 설치
    cp /var/www/rocketsourcer/current/scripts/backup.sh /usr/local/bin/rocketsourcer-backup
    chmod +x /usr/local/bin/rocketsourcer-backup
    
    # 크론 작업 설정
    if [[ "$ENVIRONMENT" == "production" ]]; then
        # 프로덕션: 매일 자정에 백업
        echo "0 0 * * * root /usr/local/bin/rocketsourcer-backup -t daily > /dev/null 2>&1" | tee -a /etc/crontab > /dev/null
        # 프로덕션: 매주 일요일 새벽 1시에 주간 백업
        echo "0 1 * * 0 root /usr/local/bin/rocketsourcer-backup -t weekly > /dev/null 2>&1" | tee -a /etc/crontab > /dev/null
        # 프로덕션: 매월 1일 새벽 2시에 월간 백업
        echo "0 2 1 * * root /usr/local/bin/rocketsourcer-backup -t monthly > /dev/null 2>&1" | tee -a /etc/crontab > /dev/null
    else
        # 스테이징: 매일 새벽 3시에 백업
        echo "0 3 * * * root /usr/local/bin/rocketsourcer-backup -t daily > /dev/null 2>&1" | tee -a /etc/crontab > /dev/null
    fi
    
    success "백업 설정 완료"
}

# 로그 로테이션 설정
setup_logrotate() {
    log "로그 로테이션 설정 중..."
    
    if [[ "$ENVIRONMENT" == "production" ]]; then
        APP_DIR="/var/www/rocketsourcer"
    else
        APP_DIR="/var/www/rocketsourcer-staging"
    fi
    
    # 로그 로테이션 설정 파일 생성
    cat > /etc/logrotate.d/rocketsourcer << EOF
$APP_DIR/shared/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 \`cat /var/run/nginx.pid\`
    endscript
}
EOF
    
    success "로그 로테이션 설정 완료"
}

# 스왑 설정
setup_swap() {
    log "스왑 설정 중..."
    
    # 현재 메모리 확인
    MEM_TOTAL=$(free -m | grep Mem | awk '{print $2}')
    
    # 메모리 크기에 따라 스왑 크기 결정
    if [ $MEM_TOTAL -le 2048 ]; then
        SWAP_SIZE=2G
    elif [ $MEM_TOTAL -le 4096 ]; then
        SWAP_SIZE=4G
    elif [ $MEM_TOTAL -le 8192 ]; then
        SWAP_SIZE=8G
    else
        SWAP_SIZE=16G
    fi
    
    # 스왑 파일 생성
    log "스왑 파일 생성 중 ($SWAP_SIZE)..."
    fallocate -l $SWAP_SIZE /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    
    # /etc/fstab에 추가
    echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab
    
    # 스왑 설정 최적화
    echo 'vm.swappiness=10' | tee -a /etc/sysctl.conf
    echo 'vm.vfs_cache_pressure=50' | tee -a /etc/sysctl.conf
    sysctl -p
    
    success "스왑 설정 완료"
}

# 메인 함수
main() {
    log "서버 설정 시작 ($ENVIRONMENT 환경)..."
    
    setup_system
    setup_swap
    setup_firewall
    setup_nginx
    setup_php
    setup_mysql
    setup_redis
    setup_ssl
    setup_deploy_dirs
    setup_monitoring
    setup_backups
    setup_logrotate
    
    success "서버 설정 완료! ($ENVIRONMENT 환경)"
}

# 스크립트 실행
main 