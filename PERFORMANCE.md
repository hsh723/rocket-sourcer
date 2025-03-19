# 성능 최적화 가이드

로켓소서 애플리케이션의 성능을 최적화하기 위한 가이드입니다.

## 서버 요구사항

최적의 성능을 위해 다음 요구사항을 충족하는 서버를 사용하세요:

- **CPU**: 2코어 이상
- **RAM**: 4GB 이상
- **디스크**: SSD 권장, 최소 20GB 여유 공간
- **PHP**: 버전 8.0 이상, OPcache 활성화
- **MySQL**: 버전 5.7 이상, InnoDB 스토리지 엔진

## PHP 최적화

### OPcache 설정

php.ini 파일에 다음 설정을 추가하세요:

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=0
```
