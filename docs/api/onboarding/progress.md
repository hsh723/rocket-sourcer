# 온보딩 진행 상황 API

이 엔드포인트는 현재 인증된 사용자의 온보딩 진행 상황을 가져옵니다. 완료된 투어, 현재 진행 중인 투어, 전체 진행률 등의 정보를 포함합니다.

## 요청

```
GET /api/onboarding/progress
```

### 헤더

| 이름 | 필수 | 설명 |
|------|------|------|
| Authorization | 예 | Bearer 토큰 |
| Accept-Language | 아니오 | 선호하는 언어 (기본값: en) |

## 응답

### 성공 응답

**상태 코드:** 200 OK

```json
{
  "user_id": 12345,
  "completed_tours": [
    {
      "id": "dashboard-tour",
      "name": "대시보드 투어",
      "completed_at": "2023-06-10T15:30:45Z",
      "steps_completed": 5,
      "total_steps": 5
    },
    {
      "id": "profile-tour",
      "name": "프로필 설정 투어",
      "completed_at": "2023-06-11T09:15:22Z",
      "steps_completed": 3,
      "total_steps": 3
    }
  ],
  "current_tour": {
    "id": "features-tour",
    "name": "주요 기능 투어",
    "current_step": 2,
    "total_steps": 7,
    "last_activity": "2023-06-12T14:20:10Z"
  },
  "remaining_tours": [
    {
      "id": "advanced-tour",
      "name": "고급 기능 투어",
      "total_steps": 6,
      "recommended": true
    },
    {
      "id": "settings-tour",
      "name": "설정 투어",
      "total_steps": 4,
      "recommended": false
    }
  ],
  "progress": {
    "completed_tours_count": 2,
    "total_tours_count": 5,
    "completed_steps_count": 10,
    "total_steps_count": 25,
    "percentage": 40
  },
  "last_activity": "2023-06-12T14:20:10Z"
}
```

### 오류 응답

**상태 코드:** 401 Unauthorized

```json
{
  "error": {
    "code": "unauthorized",
    "message": "인증이 필요합니다."
  }
}
```

**상태 코드:** 404 Not Found

```json
{
  "error": {
    "code": "not_found",
    "message": "온보딩 데이터를 찾을 수 없습니다."
  }
}
```

## 예제

### cURL

```bash
curl -X GET "https://api.rocketsourcer.com/api/onboarding/progress" \
  -H "Authorization: Bearer your-token" \
  -H "Accept-Language: ko"
```

### JavaScript

```javascript
fetch('https://api.rocketsourcer.com/api/onboarding/progress', {
  headers: {
    'Authorization': 'Bearer your-token',
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
curl_setopt($ch, CURLOPT_URL, 'https://api.rocketsourcer.com/api/onboarding/progress');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer your-token',
  'Accept-Language: ko'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

print_r($data);
```

## 참고 사항

- 이 엔드포인트는 인증된 사용자만 접근할 수 있습니다.
- 사용자가 온보딩을 시작하지 않은 경우, 기본 온보딩 데이터가 생성됩니다.
- `progress.percentage`는 완료된 단계 수를 기준으로 계산됩니다.
- `recommended` 플래그는 사용자의 역할과 이전 활동을 기반으로 다음에 수행할 투어를 추천합니다. 