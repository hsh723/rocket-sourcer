# Rocket Sourcer 호환성 체크리스트

## 시스템 요구사항 체크

### PHP 호환성
- [ ] PHP 버전 7.4 이상
- [ ] PHP 메모리 제한 128MB 이상
- [ ] PHP 실행 시간 제한 30초 이상
- [ ] PHP cURL 확장 설치
- [ ] PHP JSON 확장 설치
- [ ] PHP MySQL 확장 설치
- [ ] PHP ZIP 확장 설치

### MySQL/MariaDB 호환성
- [ ] MySQL 5.6 이상 또는 MariaDB 10.1 이상
- [ ] InnoDB 스토리지 엔진 지원
- [ ] UTF8MB4 문자셋 지원

### WordPress 호환성
- [ ] WordPress 버전 5.8 이상
- [ ] REST API 활성화
- [ ] 퍼머링크 설정 완료
- [ ] WP_MEMORY_LIMIT 128MB 이상

## 플러그인 호환성

### 필수 플러그인
- [ ] WooCommerce (선택적)
- [ ] Advanced Custom Fields (선택적)

### 알려진 호환 플러그인
- [ ] Yoast SEO
- [ ] WP Super Cache
- [ ] W3 Total Cache
- [ ] WP Rocket
- [ ] Wordfence Security

### 알려진 비호환 플러그인
- [ ] 특정 캐시 플러그인과 충돌 여부 확인
- [ ] 특정 보안 플러그인과 충돌 여부 확인

## 테마 호환성

### 테마 요구사항
- [ ] WordPress 기본 테마 훅 지원
- [ ] 반응형 디자인 지원
- [ ] JavaScript 이벤트 충돌 없음
- [ ] CSS 충돌 없음

### 테스트된 테마
- [ ] Twenty Twenty-Four
- [ ] Twenty Twenty-Three
- [ ] Astra
- [ ] OceanWP
- [ ] GeneratePress

## 브라우저 호환성

### 데스크톱 브라우저
- [ ] Chrome (최신 3개 버전)
- [ ] Firefox (최신 3개 버전)
- [ ] Safari (최신 2개 버전)
- [ ] Edge (최신 3개 버전)

### 모바일 브라우저
- [ ] Chrome for Android
- [ ] Safari for iOS
- [ ] Samsung Internet
- [ ] Opera Mobile

## API 호환성

### 외부 API
- [ ] 1688.com API
- [ ] AliExpress API
- [ ] 배송사 API
- [ ] 결제 게이트웨이 API

### 내부 API
- [ ] WordPress REST API
- [ ] WooCommerce REST API (해당되는 경우)

## 성능 체크

### 로딩 시간
- [ ] 관리자 페이지 로딩 < 2초
- [ ] 프론트엔드 페이지 로딩 < 1초
- [ ] API 응답 시간 < 1초

### 리소스 사용
- [ ] PHP 메모리 사용 < 64MB
- [ ] 데이터베이스 쿼리 < 50/페이지
- [ ] 캐시 적용 확인

## 보안 체크

### 데이터 보안
- [ ] 사용자 데이터 암호화
- [ ] API 키 보안 저장
- [ ] XSS 방지
- [ ] CSRF 방지
- [ ] SQL 인젝션 방지

### 파일 보안
- [ ] 파일 업로드 제한
- [ ] 파일 타입 검증
- [ ] 디렉토리 접근 제한

## 다국어 지원

### 번역 준비
- [ ] 한국어 번역 완료
- [ ] 영어 번역 완료
- [ ] 번역 파일 검증
- [ ] RTL 언어 지원 (선택적)

## 접근성

### WCAG 2.1 준수
- [ ] 키보드 접근성
- [ ] 스크린 리더 호환성
- [ ] 색상 대비
- [ ] 오류 메시지 명확성

## 백업 호환성

### 데이터 마이그레이션
- [ ] 업그레이드 경로 테스트
- [ ] 다운그레이드 경로 테스트
- [ ] 데이터 백업 기능

### 설정 보존
- [ ] 플러그인 설정 보존
- [ ] 사용자 데이터 보존
- [ ] 캐시 데이터 처리

## 문서화

### 기술 문서
- [ ] API 문서
- [ ] 개발자 가이드
- [ ] 배포 가이드

### 사용자 문서
- [ ] 설치 가이드
- [ ] 사용자 매뉴얼
- [ ] FAQ
- [ ] 문제 해결 가이드 