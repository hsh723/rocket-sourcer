# 배포 설정 가이드

이 문서는 Rocket Sourcer 애플리케이션의 배포 설정 및 프로세스에 대한 가이드입니다.

## 목차

1. [배포 아키텍처](#배포-아키텍처)
2. [배포 전략](#배포-전략)
3. [배포 스크립트](#배포-스크립트)
4. [서버 설정](#서버-설정)
5. [GitHub Actions 워크플로우](#github-actions-워크플로우)
6. [롤백 프로세스](#롤백-프로세스)
7. [모니터링 및 알림](#모니터링-및-알림)
8. [문제 해결](#문제-해결)

## 배포 아키텍처

Rocket Sourcer는 다음과 같은 배포 아키텍처를 사용합니다:

- **웹 서버**: Nginx
- **애플리케이션 서버**: PHP-FPM 8.1
- **데이터베이스**: MySQL 8.0
- **캐시**: Redis
- **배포 방식**: 제로 다운타임 배포 (Blue-Green, Canary)
- **환경**: 프로덕션, 스테이징

### 디렉토리 구조

```
/var/www/rocketsourcer/
├── current -> /var/www/rocketsourcer/releases/release_20230101120000
├── releases/
│   ├── release_20230101120000/
│   ├── release_20230102120000/
│   └── ...
├── shared/
│   ├── .env
│   └── storage/
├── blue/
├── green/
└── canary/
```

## 배포 전략

Rocket Sourcer는 다음과 같은 배포 전략을 지원합니다:

### 표준 배포

표준 배포는 가장 기본적인 배포 방식으로, 애플리케이션을 직접 업데이트합니다. 이 방식은 짧은 다운타임이 발생할 수 있습니다.

### 블루-그린 배포

블루-그린 배포는 제로 다운타임 배포를 위한 전략입니다. 두 개의 동일한 환경(블루와 그린)을 유지하고, 한 환경에서 다른 환경으로 트래픽을 전환하여 다운타임 없이 배포합니다.

1. 현재 활성 환경(예: 블루)이 트래픽을 처리하는 동안 비활성 환경(예: 그린)에 새 버전을 배포합니다.
2. 비활성 환경에서 테스트를 수행합니다.
3. 트래픽을 비활성 환경으로 전환합니다.
4. 문제가 발생하면 즉시 이전 환경으로 롤백할 수 있습니다.

### 카나리 배포

카나리 배포는 점진적으로 트래픽을 새 버전으로 전환하는 전략입니다. 이 방식은 새 버전의 문제를 조기에 발견하고 영향을 최소화할 수 있습니다.

1. 카나리 환경에 새 버전을 배포합니다.
2. 소량의 트래픽(예: 10%)을 카나리 환경으로 전환합니다.
3. 모니터링하고 문제가 없으면 점진적으로 트래픽을 증가시킵니다(예: 50%, 100%).
4. 문제가 발생하면 즉시 트래픽을 이전 버전으로 되돌립니다.

## 배포 스크립트

### 배포 스크립트 (`scripts/deploy.sh`)

배포 스크립트는 애플리케이션을 배포하는 데 사용됩니다. 다음과 같은 기능을 제공합니다:

- 코드 체크아웃
- 의존성 설치
- 공유 디렉토리 설정
- 애플리케이션 설정
- 데이터베이스 마이그레이션
- 심볼릭 링크 업데이트
- 웹 서버 재시작
- 헬스 체크
- 롤백 처리

#### 사용법

```bash
./scripts/deploy.sh -e <environment> -t <type> -b <branch> -m <mode>
```

옵션:
- `-e, --environment`: 배포 환경 (production 또는 staging, 기본값: production)
- `-t, --type`: 배포 유형 (full 또는 code-only, 기본값: full)
- `-b, --branch`: 배포할 Git 브랜치 (기본값: main)
- `-m, --mode`: 배포 모드 (standard, blue-green, canary, 기본값: standard)

예시:
```bash
./scripts/deploy.sh -e production -t full -b main -m blue-green
```

### 롤백 스크립트 (`scripts/rollback.sh`)

롤백 스크립트는 이전 버전으로 롤백하는 데 사용됩니다.

#### 사용법

```bash
./scripts/rollback.sh [release]
```

옵션:
- `[release]`: 롤백할 릴리스 이름 (지정하지 않으면 이전 릴리스로 롤백)

예시:
```bash
./scripts/rollback.sh release_20230101120000
```

### 트래픽 전환 스크립트 (`scripts/shift-traffic.sh`)

트래픽 전환 스크립트는 블루-그린 또는 카나리 배포에서 트래픽을 전환하는 데 사용됩니다.

#### 사용법

```bash
./scripts/shift-traffic.sh <percentage> [mode]
```

옵션:
- `<percentage>`: 새 버전으로 전환할 트래픽 비율 (0-100)
- `[mode]`: 배포 모드 (blue-green 또는 canary, 기본값: canary)

예시:
```bash
./scripts/shift-traffic.sh 10 canary    # 10% 트래픽을 카나리 환경으로 전환
./scripts/shift-traffic.sh 100 blue-green # 100% 트래픽을 블루-그린 환경으로 전환
```

### 헬스 체크 스크립트 (`scripts/health_check.sh`)

헬스 체크 스크립트는 애플리케이션과 서버의 상태를 모니터링하는 데 사용됩니다.

#### 사용법

```bash
./scripts/health_check.sh
```

## 서버 설정

### Nginx 설정

Nginx 설정 파일은 `config/server/nginx.conf`에 있습니다. 이 파일은 다음과 같은 설정을 포함합니다:

- HTTP/HTTPS 설정
- SSL 설정
- 가상 호스트 설정
- PHP-FPM 연결 설정
- 정적 파일 캐싱
- 보안 헤더
- 블루-그린 및 카나리 배포를 위한 설정

### PHP-FPM 설정

PHP-FPM 설정 파일은 `config/server/php-fpm.conf`에 있습니다. 이 파일은 다음과 같은 설정을 포함합니다:

- 프로세스 관리 설정
- 성능 최적화 설정
- 메모리 제한 설정
- 세션 관리 설정
- 오류 로깅 설정
- 환경별 풀 설정 (프로덕션, 스테이징)

### MySQL 설정

MySQL 설정 파일은 `config/server/mysql.cnf`에 있습니다. 이 파일은 다음과 같은 설정을 포함합니다:

- 성능 최적화 설정
- 캐시 설정
- 복제 설정
- 로깅 설정
- 보안 설정
- InnoDB 설정

### Redis 설정

Redis 설정 파일은 `config/server/redis.conf`에 있습니다. 이 파일은 다음과 같은 설정을 포함합니다:

- 메모리 관리 설정
- 지속성 설정
- 복제 설정
- 보안 설정
- 성능 최적화 설정

## GitHub Actions 워크플로우

GitHub Actions 워크플로우는 `.github/workflows/deploy.yml`에 정의되어 있습니다. 이 워크플로우는 다음과 같은 작업을 수행합니다:

1. **테스트**: 코드 체크아웃, PHP 설정, 의존성 설치, 환경 설정, 데이터베이스 마이그레이션, PHPUnit 테스트, 코드 스타일 검사
2. **빌드**: 코드 체크아웃, Node.js 설정, 의존성 설치, 프론트엔드 빌드, 빌드 아티팩트 업로드
3. **배포**: 코드 체크아웃, 빌드 아티팩트 다운로드, SSH 키 설정, 배포 환경 결정, 배포 준비, 배포 스크립트 전송, 배포 실행, 헬스 체크, Slack 알림
4. **카나리**: 코드 체크아웃, SSH 키 설정, 카나리 배포 실행, 트래픽 점진적 전환, 헬스 체크, Slack 알림

### 워크플로우 트리거

워크플로우는 다음과 같은 이벤트에 의해 트리거됩니다:

- `main` 브랜치로의 푸시: 프로덕션 환경에 배포
- `staging` 브랜치로의 푸시: 스테이징 환경에 배포
- 수동 트리거: 사용자가 지정한 환경 및 배포 유형으로 배포

## 롤백 프로세스

롤백은 다음과 같은 상황에서 수행될 수 있습니다:

1. **자동 롤백**: 배포 후 헬스 체크가 실패하면 자동으로 이전 버전으로 롤백됩니다.
2. **수동 롤백**: `scripts/rollback.sh` 스크립트를 사용하여 수동으로 롤백할 수 있습니다.

### 롤백 단계

1. 이전 릴리스로 심볼릭 링크를 업데이트합니다.
2. 웹 서버를 재시작합니다.
3. 헬스 체크를 수행합니다.
4. 롤백 결과를 알립니다.

## 모니터링 및 알림

### 모니터링

- **애플리케이션 모니터링**: 헬스 체크 엔드포인트 (`/api/health`)를 통해 애플리케이션 상태를 모니터링합니다.
- **서버 모니터링**: Prometheus Node Exporter를 사용하여 서버 리소스를 모니터링합니다.
- **로그 모니터링**: 애플리케이션 로그, 웹 서버 로그, PHP-FPM 로그, MySQL 로그를 모니터링합니다.

### 알림

- **Slack 알림**: 배포 성공/실패, 롤백, 서버 이슈 등에 대한 알림을 Slack으로 전송합니다.
- **이메일 알림**: 중요한 이슈에 대한 알림을 이메일로 전송합니다.

## 문제 해결

### 일반적인 문제

1. **배포 실패**
   - 로그 확인: `/var/log/deploy.log`
   - 롤백 수행: `./scripts/rollback.sh`

2. **헬스 체크 실패**
   - 애플리케이션 로그 확인: `/var/www/rocketsourcer/shared/storage/logs/laravel.log`
   - 웹 서버 로그 확인: `/var/log/nginx/error.log`
   - PHP-FPM 로그 확인: `/var/log/php8.1-fpm.log`

3. **데이터베이스 이슈**
   - MySQL 로그 확인: `/var/log/mysql/error.log`
   - 데이터베이스 연결 확인: `php artisan db:monitor`

4. **캐시 이슈**
   - Redis 로그 확인: `/var/log/redis/redis-server.log`
   - 캐시 지우기: `php artisan cache:clear`

### 지원 및 문의

문제가 발생하면 다음 연락처로 문의하세요:

- **DevOps 팀**: devops@rocketsourcer.com
- **긴급 연락처**: emergency@rocketsourcer.com
- **Slack 채널**: #devops-alerts 