# Rocket Sourcer 개발자 가이드

## 목차

1. [개발 환경 설정](#개발-환경-설정)
2. [아키텍처 개요](#아키텍처-개요)
3. [코드 구조](#코드-구조)
4. [API 문서](#api-문서)
5. [개발 가이드라인](#개발-가이드라인)
6. [테스트](#테스트)
7. [배포](#배포)

## 개발 환경 설정

### 요구사항
- PHP 7.4 이상
- MySQL 5.6 이상 또는 MariaDB 10.1 이상
- Node.js 16 이상
- Composer
- WordPress 5.8 이상

### 로컬 개발 환경 설정
```bash
# 저장소 클론
git clone https://github.com/your-username/rocket-sourcer.git
cd rocket-sourcer

# Composer 의존성 설치
composer install

# Node.js 의존성 설치
npm install

# 개발 모드로 실행
npm run dev
```

### 환경 변수 설정
`.env` 파일을 생성하고 다음 변수들을 설정하세요:
```
ROCKET_SOURCER_1688_API_KEY=your_1688_api_key
ROCKET_SOURCER_ALIEXPRESS_API_KEY=your_aliexpress_api_key
ROCKET_SOURCER_SHIPPING_API_KEY=your_shipping_api_key
```

## 아키텍처 개요

### 핵심 컴포넌트
1. **크롤러 (Crawler)**
   - 데이터 수집 및 분석
   - 캐싱 시스템
   - 에러 처리

2. **분석기 (Analyzer)**
   - 키워드 분석
   - 제품 분석
   - 마진 계산

3. **소싱 엔진 (Sourcing Engine)**
   - 해외 소싱 통합
   - 이미지 검색
   - 배송비 계산

4. **관리자 인터페이스**
   - AJAX 처리
   - 데이터 시각화
   - 설정 관리

### 데이터 흐름
```
사용자 요청 → AJAX 핸들러 → 컨트롤러 → 서비스 → 모델 → 데이터베이스
                                     ↓
                                  외부 API
```

## 코드 구조

### 디렉토리 구조
```
rocket-sourcer/
├── admin/              # 관리자 인터페이스
├── includes/           # 핵심 클래스
├── public/            # 프론트엔드
├── assets/           # 정적 파일
│   ├── css/
│   ├── js/
│   └── images/
├── languages/        # 번역 파일
├── templates/        # 템플릿 파일
├── tests/           # 테스트 파일
└── vendor/          # Composer 의존성
```

### 주요 클래스
- `Rocket_Sourcer_Crawler`: 데이터 수집
- `Rocket_Sourcer_Analyzer`: 데이터 분석
- `Rocket_Sourcer_Sourcing`: 해외 소싱
- `Rocket_Sourcer_Admin`: 관리자 기능
- `Rocket_Sourcer_Public`: 프론트엔드 기능

## API 문서

### REST API 엔드포인트

#### 키워드 분석
```
POST /wp-json/rocket-sourcer/v1/analyze-keyword
{
    "keyword": "string",
    "category": "string"
}
```

#### 제품 분석
```
POST /wp-json/rocket-sourcer/v1/analyze-product
{
    "url": "string",
    "options": {
        "include_competitors": boolean,
        "forecast_period": number
    }
}
```

#### 마진 계산
```
POST /wp-json/rocket-sourcer/v1/calculate-margin
{
    "product_cost": number,
    "selling_price": number,
    "shipping_cost": number,
    "coupang_fee_rate": number
}
```

### 훅 시스템

#### 액션
```php
// 키워드 분석 전
do_action('rocket_sourcer_before_keyword_analysis', $keyword);

// 제품 분석 후
do_action('rocket_sourcer_after_product_analysis', $result, $product_url);

// 마진 계산 완료
do_action('rocket_sourcer_margin_calculated', $margin_data);
```

#### 필터
```php
// 키워드 점수 수정
add_filter('rocket_sourcer_keyword_score', function($score, $keyword) {
    return $score;
}, 10, 2);

// 제품 분석 결과 수정
add_filter('rocket_sourcer_product_analysis', function($result, $product) {
    return $result;
}, 10, 2);
```

## 개발 가이드라인

### 코딩 표준
- PSR-12 코딩 표준 준수
- WordPress 코딩 표준 준수
- ESLint 및 Prettier 설정 준수

### 커밋 메시지 규칙
```
feat: 새로운 기능 추가
fix: 버그 수정
docs: 문서 수정
style: 코드 포맷팅
refactor: 코드 리팩토링
test: 테스트 코드
chore: 빌드 프로세스 변경
```

### 브랜치 전략
- `main`: 프로덕션 브랜치
- `develop`: 개발 브랜치
- `feature/*`: 기능 개발
- `bugfix/*`: 버그 수정
- `release/*`: 릴리스 준비

## 테스트

### 단위 테스트
```bash
# PHPUnit 테스트 실행
composer test

# 특정 테스트 실행
composer test -- --filter=test_keyword_analysis
```

### 성능 테스트
```bash
# 성능 테스트 실행
composer test-performance

# 특정 성능 테스트 실행
composer test-performance -- --filter=test_bulk_keyword_analysis
```

### 코드 커버리지
```bash
# 커버리지 리포트 생성
composer coverage
```

## 배포

### 빌드 프로세스
```bash
# 프로덕션 빌드
npm run build

# 배포 패키지 생성
./build/build.sh

# WordPress.org 배포
./build/build.sh --deploy
```

### 버전 관리
- 시맨틱 버저닝 사용 (MAJOR.MINOR.PATCH)
- `CHANGELOG.md` 업데이트
- 태그 생성 및 푸시

### 배포 체크리스트
1. 모든 테스트 통과 확인
2. 코드 리뷰 완료
3. 변경 로그 업데이트
4. 버전 번호 업데이트
5. 빌드 실행
6. 배포 패키지 테스트
7. WordPress.org 배포

### 문제 해결

#### 일반적인 문제
1. **빌드 실패**
   - Node.js 버전 확인
   - 의존성 재설치
   - 캐시 삭제

2. **테스트 실패**
   - 테스트 환경 설정 확인
   - 데이터베이스 연결 확인
   - 모킹 데이터 확인

3. **API 오류**
   - API 키 확인
   - 요청 제한 확인
   - 네트워크 연결 확인

#### 디버깅
```php
// 디버그 모드 활성화
define('ROCKET_SOURCER_DEBUG', true);

// 로깅
$logger = new Rocket_Sourcer_Logger();
$logger->log_debug('디버그 메시지');
$logger->log_error('에러 메시지', ['context' => $data]);
```

### 지원 및 문의
- 이슈 트래커: https://github.com/your-username/rocket-sourcer/issues
- 개발자 포럼: https://rocketsourcer.com/forum
- 이메일: dev@rocketsourcer.com 