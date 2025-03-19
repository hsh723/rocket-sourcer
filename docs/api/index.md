# Rocket Sourcer API 문서

## 소개

Rocket Sourcer API는 도움말 콘텐츠 관리 및 사용자 온보딩 시스템과 상호 작용할 수 있는 RESTful 인터페이스를 제공합니다. 이 문서는 사용 가능한 모든 API 엔드포인트, 요청 및 응답 형식, 인증 방법 등을 설명합니다.

## 목차

- [인증](#인증)
- [도움말 API](#도움말-api)
- [온보딩 API](#온보딩-api)
- [오류 처리](#오류-처리)
- [속도 제한](#속도-제한)
- [버전 관리](#버전-관리)

## 인증

모든 API 요청은 인증이 필요합니다. Rocket Sourcer는 두 가지 인증 방법을 지원합니다:

### API 키 인증

대부분의 서버 간 통신에 권장됩니다.

```
GET /api/help/categories HTTP/1.1
Host: api.rocketsourcer.com
X-API-Key: your-api-key
```

### Bearer 토큰 인증

사용자 컨텍스트가 필요한 요청에 권장됩니다.

```
GET /api/onboarding/progress HTTP/1.1
Host: api.rocketsourcer.com
Authorization: Bearer your-jwt-token
```

## 도움말 API

도움말 콘텐츠 관리 시스템과 상호 작용하기 위한 엔드포인트입니다.

| 엔드포인트 | 메서드 | 설명 | 문서 링크 |
|------------|--------|------|-----------|
| `/api/help/initial-data` | GET | 초기 도움말 데이터 가져오기 | [자세히 보기](./help/initial-data.md) |
| `/api/help/search` | GET | 도움말 콘텐츠 검색 | [자세히 보기](./help/search.md) |
| `/api/help/category/{categorySlug}` | GET | 카테고리별 도움말 항목 가져오기 | [자세히 보기](./help/category.md) |
| `/api/help/article/{categorySlug}/{articleSlug}` | GET | 특정 도움말 항목 가져오기 | [자세히 보기](./help/article.md) |
| `/api/help/feedback` | POST | 도움말 항목에 대한 피드백 제출 | [자세히 보기](./help/feedback.md) |

## 온보딩 API

사용자 온보딩 시스템과 상호 작용하기 위한 엔드포인트입니다.

| 엔드포인트 | 메서드 | 설명 | 문서 링크 |
|------------|--------|------|-----------|
| `/api/onboarding/status` | GET | 사용자의 온보딩 상태 확인 | [자세히 보기](./onboarding/status.md) |
| `/api/onboarding/progress` | GET | 사용자의 온보딩 진행 상황 가져오기 | [자세히 보기](./onboarding/progress.md) |
| `/api/onboarding/tours` | GET | 모든 온보딩 투어 목록 가져오기 | [자세히 보기](./onboarding/tours.md) |
| `/api/onboarding/tour/{tourId}` | GET | 특정 온보딩 투어 정보 가져오기 | [자세히 보기](./onboarding/tour.md) |
| `/api/onboarding/next-tour` | GET | 다음 추천 투어 가져오기 | [자세히 보기](./onboarding/next-tour.md) |
| `/api/onboarding/complete/{tourId}` | POST | 투어를 완료로 표시 | [자세히 보기](./onboarding/complete.md) |
| `/api/onboarding/set-current` | POST | 현재 투어 설정 | [자세히 보기](./onboarding/set-current.md) |
| `/api/onboarding/update-progress` | POST | 온보딩 진행 상황 업데이트 | [자세히 보기](./onboarding/update-progress.md) |

## 오류 처리

API는 표준 HTTP 상태 코드를 사용하여 요청 성공 또는 실패를 나타냅니다. 오류가 발생하면 응답 본문에 오류에 대한 자세한 정보가 포함됩니다.

### 오류 응답 형식

```json
{
  "error": {
    "code": "invalid_request",
    "message": "요청에 필수 매개변수가 누락되었습니다.",
    "details": {
      "missing_param": "article_id"
    }
  }
}
```

### 일반적인 오류 코드

| 상태 코드 | 오류 코드 | 설명 |
|-----------|-----------|------|
| 400 | `invalid_request` | 요청이 유효하지 않음 |
| 401 | `unauthorized` | 인증 실패 |
| 403 | `forbidden` | 권한 없음 |
| 404 | `not_found` | 리소스를 찾을 수 없음 |
| 429 | `rate_limit_exceeded` | 속도 제한 초과 |
| 500 | `server_error` | 서버 오류 |

## 속도 제한

API 요청은 속도 제한이 적용됩니다. 기본적으로 IP당 분당 60개의 요청이 허용됩니다. 속도 제한에 도달하면 429 상태 코드가 반환됩니다.

속도 제한 정보는 응답 헤더에 포함됩니다:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1623456789
```

## 버전 관리

API는 버전 관리됩니다. 현재 버전은 v1입니다. API 버전은 URL 경로에 지정할 수 있습니다:

```
https://api.rocketsourcer.com/v1/help/categories
```

또는 요청 헤더를 통해 지정할 수 있습니다:

```
Accept: application/json; version=1
```

### 지원되는 버전

| 버전 | 상태 | 지원 종료 예정 |
|------|------|----------------|
| v1 | 안정 | 미정 |

## 추가 리소스

- [API 변경 로그](./changelog.md)
- [클라이언트 라이브러리](./client-libraries.md)
- [샘플 코드](./samples.md)
- [API 상태 대시보드](https://status.rocketsourcer.com)

문의사항이 있으시면 api-support@rocketsourcer.com으로 연락해 주세요.