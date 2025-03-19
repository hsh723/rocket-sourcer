# Rocket Sourcer 개발자 통합 가이드

## 목차

1. [소개](#소개)
2. [설치 및 설정](#설치-및-설정)
3. [도움말 시스템 통합](#도움말-시스템-통합)
4. [온보딩 시스템 통합](#온보딩-시스템-통합)
5. [API 참조](#api-참조)
6. [고급 사용법](#고급-사용법)
7. [문제 해결](#문제-해결)

## 소개

이 가이드는 개발자가 Rocket Sourcer의 도움말 및 온보딩 시스템을 애플리케이션에 통합하는 방법을 설명합니다. 프론트엔드 컴포넌트 통합, API 사용, 이벤트 처리 등 필요한 모든 정보를 제공합니다.

## 설치 및 설정

### NPM을 통한 설치

```bash
npm install @rocket-sourcer/help @rocket-sourcer/onboarding
```

또는 Yarn을 사용하는 경우:

```bash
yarn add @rocket-sourcer/help @rocket-sourcer/onboarding
```

### 설정

애플리케이션의 진입점 파일(예: `main.js` 또는 `app.js`)에서 Rocket Sourcer를 초기화합니다:

```javascript
import { initializeHelp } from '@rocket-sourcer/help';
import { initializeOnboarding } from '@rocket-sourcer/onboarding';

// 도움말 시스템 초기화
initializeHelp({
  apiUrl: 'https://your-api-url.com/api/help',
  apiKey: 'your-api-key',
  defaultLanguage: 'ko',
  theme: 'light', // 'light', 'dark', 또는 'auto'
});

// 온보딩 시스템 초기화
initializeOnboarding({
  apiUrl: 'https://your-api-url.com/api/onboarding',
  apiKey: 'your-api-key',
  autoStart: true, // 첫 방문 시 자동으로 온보딩 시작
});
```

## 도움말 시스템 통합

### 도움말 위젯 추가

Vue.js 애플리케이션에서:

```javascript
<template>
  <div>
    <!-- 애플리케이션 콘텐츠 -->
    <HelpWidget />
  </div>
</template>

<script>
import { HelpWidget } from '@rocket-sourcer/help';

export default {
  components: {
    HelpWidget
  }
}
</script>
```

React 애플리케이션에서:

```javascript
import { HelpWidget } from '@rocket-sourcer/help/react';

function App() {
  return (
    <div>
      {/* 애플리케이션 콘텐츠 */}
      <HelpWidget />
    </div>
  );
}
```

### 컨텍스트 기반 도움말 설정

특정 페이지나 컴포넌트에 대한 컨텍스트 기반 도움말을 설정하려면:

```javascript
import { setHelpContext } from '@rocket-sourcer/help';

// 컴포넌트가 마운트될 때
mounted() {
  setHelpContext('user-profile-page', {
    additionalData: {
      userRole: this.userRole,
      section: 'profile-settings'
    }
  });
}

// 컴포넌트가 언마운트될 때
beforeUnmount() {
  clearHelpContext('user-profile-page');
}
```

### 도움말 항목 직접 표시

특정 도움말 항목을 프로그래밍 방식으로 표시하려면:

```javascript
import { showHelpArticle } from '@rocket-sourcer/help';

// 카테고리와 항목 슬러그로 도움말 항목 표시
showHelpArticle('account-settings', 'change-password');

// 또는 항목 ID로 표시
showHelpArticle({ id: 'help-123' });
```

## 온보딩 시스템 통합

### 온보딩 투어 시작

특정 투어를 시작하려면:

```javascript
import { startTour } from '@rocket-sourcer/onboarding';

// 투어 ID로 시작
startTour('dashboard-tour');

// 옵션과 함께 시작
startTour('dashboard-tour', {
  force: true, // 이미 완료된 투어도 다시 시작
  onComplete: () => {
    console.log('투어가 완료되었습니다!');
  }
});
```

### 온보딩 진행 상황 확인

사용자의 온보딩 진행 상황을 확인하려면:

```javascript
import { getOnboardingProgress } from '@rocket-sourcer/onboarding';

async function checkProgress() {
  const progress = await getOnboardingProgress();
  console.log('완료된 투어:', progress.completedTours);
  console.log('현재 투어:', progress.currentTour);
  console.log('전체 진행률:', progress.overallProgress);
}
```

### 온보딩 이벤트 처리

온보딩 이벤트를 수신하고 처리하려면:

```javascript
import { onboardingEvents } from '@rocket-sourcer/onboarding';

// 투어 시작 이벤트
onboardingEvents.on('tourStart', (tourId) => {
  console.log(`투어 시작: ${tourId}`);
});

// 투어 단계 변경 이벤트
onboardingEvents.on('stepChange', (tourId, stepIndex) => {
  console.log(`투어 ${tourId}의 ${stepIndex} 단계로 이동`);
});

// 투어 완료 이벤트
onboardingEvents.on('tourComplete', (tourId) => {
  console.log(`투어 완료: ${tourId}`);
});

// 온보딩 완료 이벤트
onboardingEvents.on('onboardingComplete', () => {
  console.log('모든 온보딩 투어 완료!');
});
```

## API 참조

### 도움말 API

```javascript
// 도움말 카테고리 가져오기
import { getCategories } from '@rocket-sourcer/help';
const categories = await getCategories();

// 특정 카테고리의 항목 가져오기
import { getArticlesByCategory } from '@rocket-sourcer/help';
const articles = await getArticlesByCategory('account-settings');

// 도움말 검색
import { searchHelp } from '@rocket-sourcer/help';
const results = await searchHelp('비밀번호 변경');

// 피드백 제출
import { submitFeedback } from '@rocket-sourcer/help';
await submitFeedback({
  articleId: 'help-123',
  helpful: true,
  comment: '매우 유용한 정보였습니다!'
});
```

### 온보딩 API

```javascript
// 사용 가능한 모든 투어 가져오기
import { getAllTours } from '@rocket-sourcer/onboarding';
const tours = await getAllTours();

// 특정 투어 정보 가져오기
import { getTour } from '@rocket-sourcer/onboarding';
const tour = await getTour('dashboard-tour');

// 투어 완료로 표시
import { completeTour } from '@rocket-sourcer/onboarding';
await completeTour('dashboard-tour');

// 현재 투어 설정
import { setCurrentTour } from '@rocket-sourcer/onboarding';
await setCurrentTour('feature-tour');

// 다음 추천 투어 가져오기
import { getNextRecommendedTour } from '@rocket-sourcer/onboarding';
const nextTour = await getNextRecommendedTour();
```

## 고급 사용법

### 커스텀 테마 적용

도움말 및 온보딩 시스템의 모양을 애플리케이션 테마에 맞게 사용자 정의할 수 있습니다:

```javascript
import { setTheme } from '@rocket-sourcer/help';

setTheme({
  primaryColor: '#3498db',
  secondaryColor: '#2ecc71',
  textColor: '#333333',
  backgroundColor: '#ffffff',
  borderRadius: '4px',
  fontFamily: 'Noto Sans KR, sans-serif',
  // 기타 테마 옵션...
});
```

### 커스텀 렌더러 사용

도움말 콘텐츠의 렌더링 방식을 사용자 정의할 수 있습니다:

```javascript
import { setContentRenderer } from '@rocket-sourcer/help';
import MyCustomRenderer from './MyCustomRenderer';

setContentRenderer(MyCustomRenderer);
```

### 온보딩 단계 조건부 표시

특정 조건에 따라 온보딩 단계를 표시하거나 숨길 수 있습니다:

```javascript
import { configureTour } from '@rocket-sourcer/onboarding';

configureTour('dashboard-tour', {
  steps: [
    {
      id: 'step-1',
      // 기본 단계 설정...
    },
    {
      id: 'step-2',
      // 기본 단계 설정...
      condition: () => {
        // 사용자가 관리자인 경우에만 이 단계 표시
        return userIsAdmin();
      }
    }
  ]
});
```

## 문제 해결

### 일반적인 문제

1. **도움말 위젯이 표시되지 않음**
   - 초기화 코드가 올바르게 실행되었는지 확인합니다.
   - API URL과 API 키가 올바른지 확인합니다.
   - 콘솔 오류를 확인합니다.

2. **온보딩 투어가 시작되지 않음**
   - `autoStart` 설정이 `true`로 설정되었는지 확인합니다.
   - 투어 ID가 올바른지 확인합니다.
   - 사용자가 이미 투어를 완료했는지 확인합니다.

3. **API 호출 실패**
   - 네트워크 연결을 확인합니다.
   - API 엔드포인트가 올바른지 확인합니다.
   - 인증 토큰이 유효한지 확인합니다.

### 디버깅

디버깅 모드를 활성화하여 자세한 로그를 확인할 수 있습니다:

```javascript
import { enableDebug } from '@rocket-sourcer/help';
import { enableDebug as enableOnboardingDebug } from '@rocket-sourcer/onboarding';

// 도움말 시스템 디버깅 활성화
enableDebug();

// 온보딩 시스템 디버깅 활성화
enableOnboardingDebug();
```

### 지원 요청

통합 중 문제가 발생하면 다음 방법으로 지원을 요청할 수 있습니다:

- GitHub 이슈: [github.com/rocket-sourcer/issues](https://github.com/rocket-sourcer/issues)
- 개발자 포럼: [community.rocketsourcer.com](https://community.rocketsourcer.com)
- 이메일: dev-support@rocketsourcer.com 