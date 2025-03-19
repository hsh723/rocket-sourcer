# 로켓소서 실행 가이드

로켓소서 애플리케이션을 실행하는 방법을 설명합니다.

## 개발 환경에서 실행하기

### 요구 사항

- PHP 8.0 이상
- MySQL 5.7 이상
- Composer
- 필요한 PHP 확장: PDO, JSON, mbstring

### 실행 단계

1. 저장소 복제:
```bash
git clone https://github.com/yourusername/rocket-sourcer.git
cd rocket-sourcer
```

2. 의존성 설치:
```bash
composer install
```

3. 환경 설정:
```bash
cp .env.example .env
```
`.env` 파일을 편집하여 데이터베이스 연결 정보 및 API 키를 설정하세요.

4. 데이터베이스 준비:
```bash
mysql -u root -p -e "CREATE DATABASE rocket_sourcer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

5. 시작 스크립트 실행:
```bash
chmod +x scripts/start.sh
scripts/start.sh
```

6. 브라우저에서 확인:
```
http://localhost:8000
```

## 배포하기

### 요구 사항

- PHP 8.0 이상이 설치된 웹 서버
- MySQL 5.7 이상
- 웹 서버 (Apache 또는 Nginx)

### 배포 단계

1. 소스 코드 복사:
```bash
git clone https://github.com/yourusername/rocket-sourcer.git /var/www/rocketsourcer
cd /var/www/rocketsourcer
```

2. 의존성 설치 (프로덕션 모드):
```bash
composer install --no-dev --optimize-autoloader
```

3. 환경 설정:
```bash
cp .env.example .env
```

4. 웹 서버 설정:
Apache나 Nginx의 설정 파일을 각각의 디렉토리에 추가하세요.
설정 예시는 `config/server/` 디렉토리에 있습니다.

5. 배포 스크립트 실행:
```bash
chmod +x scripts/deploy.sh
scripts/deploy.sh production
```

6. 웹 서버 재시작:
```bash
# Apache의 경우
sudo systemctl restart apache2

# Nginx의 경우
sudo systemctl restart nginx
```
