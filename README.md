# 로켓소서 (RocketSourcer)

쿠팡 로켓그로스 셀러들을 위한 데이터 기반 소싱 자동화 시스템입니다.

## 소개

로켓소서는 쿠팡 로켓그로스 셀러들이 데이터 기반으로 높은 성공 확률의 제품을 소싱할 수 있도록 지원하는 시스템입니다. 키워드 분석, 제품 발굴, 수익성 분석, 크로스 카테고리 최적화 등 다양한 기능을 제공합니다.

## 주요 기능

- **키워드 분석 및 트렌드 모니터링**: 검색량, 경쟁 강도, 시즌성을 고려한 키워드 분석
- **제품 발굴 및 검증**: 쿠팡 내 인기 제품 분석 및 경쟁 제품 심층 분석
- **수익성 분석 및 예측**: 마진 계산, 판매량 예측, ROI 시뮬레이션
- **크로스 카테고리 최적화**: 다양한 카테고리별 최적화 전략 제안
- **차별화 전략 제안**: 소구점 분석, 리뷰 기반 개선점 도출
- **제품 생애주기 관리**: 리뷰 모니터링, 제품 업그레이드 알림

## 요구사항

- PHP 8.0 이상
- MySQL 5.7 이상
- Composer
- Node.js 16.x 이상
- npm 8.x 이상

## 설치 방법

1. 저장소 클론:
```bash
git clone https://github.com/rocket-sourcer/rocket-sourcer.git
cd rocket-sourcer
```

2. 의존성 설치:
```bash
composer install
npm install
```

3. 환경 설정:
```bash
cp .env.example .env
php artisan key:generate
```

4. 데이터베이스 설정:
- .env 파일에서 데이터베이스 정보 설정
- 마이그레이션 실행:
```bash
php artisan migrate
php artisan db:seed
```

5. 개발 서버 실행:
```bash
php artisan serve
npm run dev
```

## 사용 방법

1. 웹 브라우저에서 http://localhost:8000 접속
2. 관리자 계정으로 로그인:
   - 이메일: admin@rocketsourcer.com
   - 비밀번호: 초기 비밀번호는 이메일로 전송됨

## 기여 방법

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 라이선스

MIT License

## 문의사항

기술 지원 및 문의사항은 Issues 탭을 이용해 주세요.