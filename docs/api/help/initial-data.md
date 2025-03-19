# 초기 도움말 데이터 API

이 엔드포인트는 도움말 시스템의 초기 데이터를 가져옵니다. 애플리케이션이 처음 로드될 때 필요한 기본 카테고리 목록, 인기 있는 도움말 항목 등을 포함합니다.

## 요청

```
GET /api/help/initial-data
```

### 헤더

| 이름 | 필수 | 설명 |
|------|------|------|
| X-API-Key | 예 | API 키 |
| Accept-Language | 아니오 | 선호하는 언어 (기본값: en) |

### 쿼리 매개변수

| 이름 | 필수 | 설명 |
|------|------|------|
| popular_limit | 아니오 | 반환할 인기 항목 수 (기본값: 5) |

## 응답

### 성공 응답

**상태 코드:** 200 OK

```json
{
  "categories": [
    {
      "id": "cat-001",
      "slug": "getting-started",
      "name": "시작하기",
      "description": "Rocket Sourcer 사용을 시작하는 방법",
      "icon": "rocket",
      "article_count": 5,
      "order": 1
    },
    {
      "id": "cat-002",
      "slug": "account-settings",
      "name": "계정 설정",
      "description": "계정 관리 및 설정",
      "icon": "user-cog",
      "article_count": 8,
      "order": 2
    }
  ],
  "popular_articles": [
    {
      "id": "art-001",
      "slug": "quick-start-guide",
      "title": "빠른 시작 가이드",
      "category_id": "cat-001",
      "category_slug": "getting-started",
      "category_name": "시작하기",
      "excerpt": "5분 안에 Rocket Sourcer를 시작하는 방법",
      "view_count": 1250
    },
    {
      "id": "art-008",
      "slug": "change-password",
      "title": "비밀번호 변경 방법",
      "category_id": "cat-002",
      "category_slug": "account-settings",
      "category_name": "계정 설정",
      "excerpt": "계정 비밀번호를 안전하게 변경하는 방법",
      "view_count": 980
    }
  ],
  "metadata": {
    "total_categories": 8,
    "total_articles": 45,
    "last_updated": "2023-06-15T10:30:00Z"
  }
}
```

### 오류 응답

**상태 코드:** 401 Unauthorized

```json
{
  "error": {
    "code": "unauthorized",
    "message": "유효하지 않은 API 키"
  }
}
```

**상태 코드:** 500 Internal Server Error

```json
{
  "error": {
    "code": "server_error",
    "message": "서버 오류가 발생했습니다. 나중에 다시 시도해 주세요."
  }
}
```

## 예제

### cURL

```bash
curl -X GET "https://api.rocketsourcer.com/api/help/initial-data?popular_limit=3" \
  -H "X-API-Key: your-api-key" \
  -H "Accept-Language: ko"
```

### JavaScript

```javascript
fetch('https://api.rocketsourcer.com/api/help/initial-data?popular_limit=3', {
  headers: {
    'X-API-Key': 'your-api-key',
    'Accept-Language': 'ko'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### PHP

```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.rocketsourcer.com/api/help/initial-data?popular_limit=3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'X-API-Key: your-api-key',
  'Accept-Language: ko'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

print_r($data);
```

## 참고 사항

- 이 엔드포인트는 캐싱을 지원합니다. 응답에는 `Cache-Control` 헤더가 포함되어 있으며, 기본적으로 5분 동안 캐시됩니다.
- `Accept-Language` 헤더를 사용하여 다른 언어로 콘텐츠를 요청할 수 있습니다. 지원되는 언어 코드: `en`, `ko`, `ja`, `zh`.
- 인기 항목은 지난 30일 동안의 조회수를 기준으로 정렬됩니다. 