#!/bin/bash

# 로켓소서 시작 스크립트
echo "로켓소서를 시작합니다..."

DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
ROOT_DIR=$(dirname "$DIR")

if [ -z "$ENV" ]; then
    ENV="development"
fi

echo "실행 환경: $ENV"

mkdir -p "$ROOT_DIR/logs"
mkdir -p "$ROOT_DIR/cache"

chmod -R 755 "$ROOT_DIR/public"
chmod -R 755 "$ROOT_DIR/logs"
chmod -R 755 "$ROOT_DIR/cache"

if [ "$ENV" = "development" ]; then
    echo "개발 서버를 시작합니다..."
    cd "$ROOT_DIR" && php -S localhost:8000 -t public
else
    echo "운영 환경에서는 웹 서버(Apache/Nginx)를 사용하세요."
    echo "설정 파일은 config/server/ 디렉토리에 있습니다."
fi
