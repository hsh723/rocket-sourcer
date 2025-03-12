#!/bin/bash

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 버전 정보 가져오기
VERSION=$(grep "Version:" ../rocket-sourcer.php | cut -d' ' -f4)
PLUGIN_SLUG="rocket-sourcer"
BUILD_DIR="../build"
DIST_DIR="../dist"
PACKAGE_NAME="$PLUGIN_SLUG-$VERSION"

echo -e "${YELLOW}Rocket Sourcer 빌드 스크립트${NC}"
echo "버전: $VERSION"

# 이전 빌드 정리
echo -e "\n${YELLOW}이전 빌드 정리 중...${NC}"
rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

# 소스 파일 복사
echo -e "\n${YELLOW}소스 파일 복사 중...${NC}"
rsync -av --progress ../ "$DIST_DIR/$PACKAGE_NAME" \
    --exclude=".*" \
    --exclude="node_modules" \
    --exclude="tests" \
    --exclude="build" \
    --exclude="dist" \
    --exclude="composer.json" \
    --exclude="composer.lock" \
    --exclude="package.json" \
    --exclude="package-lock.json" \
    --exclude="phpunit.xml" \
    --exclude="webpack.config.js" \
    --exclude="*.md" \
    --exclude="*.sh" \
    --exclude="*.zip"

# Composer 의존성 설치 (프로덕션 모드)
echo -e "\n${YELLOW}Composer 의존성 설치 중...${NC}"
cd "$DIST_DIR/$PACKAGE_NAME"
composer install --no-dev --optimize-autoloader
cd -

# JavaScript 빌드
echo -e "\n${YELLOW}JavaScript 빌드 중...${NC}"
cd ..
npm install
npm run build
cd -

# JavaScript 빌드 파일 복사
echo -e "\n${YELLOW}빌드된 JavaScript 파일 복사 중...${NC}"
cp -r ../assets/dist/* "$DIST_DIR/$PACKAGE_NAME/assets/"

# 불필요한 파일 제거
echo -e "\n${YELLOW}불필요한 파일 제거 중...${NC}"
cd "$DIST_DIR/$PACKAGE_NAME"
find . -name "*.map" -type f -delete
find . -name "*.log" -type f -delete
find . -name ".git*" -type f -delete
find . -name ".env*" -type f -delete
find . -name "phpcs.xml" -type f -delete
find . -name ".eslintrc*" -type f -delete
find . -name ".prettier*" -type f -delete
cd -

# 버전 번호 업데이트
echo -e "\n${YELLOW}버전 번호 업데이트 중...${NC}"
sed -i "s/Version: .*/Version: $VERSION/" "$DIST_DIR/$PACKAGE_NAME/rocket-sourcer.php"
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" "$DIST_DIR/$PACKAGE_NAME/package.json"

# ZIP 파일 생성
echo -e "\n${YELLOW}ZIP 파일 생성 중...${NC}"
cd "$DIST_DIR"
zip -r "$PACKAGE_NAME.zip" "$PACKAGE_NAME"
cd -

# 체크섬 생성
echo -e "\n${YELLOW}체크섬 생성 중...${NC}"
cd "$DIST_DIR"
sha256sum "$PACKAGE_NAME.zip" > "$PACKAGE_NAME.zip.sha256"
md5sum "$PACKAGE_NAME.zip" > "$PACKAGE_NAME.zip.md5"
cd -

# WordPress.org SVN 업데이트 (선택적)
if [ "$1" == "--deploy" ]; then
    echo -e "\n${YELLOW}WordPress.org SVN 업데이트 중...${NC}"
    SVN_DIR="$BUILD_DIR/svn"
    SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG"
    
    # SVN 저장소 체크아웃 또는 업데이트
    if [ -d "$SVN_DIR" ]; then
        cd "$SVN_DIR"
        svn update
    else
        svn checkout "$SVN_URL" "$SVN_DIR"
        cd "$SVN_DIR"
    fi
    
    # trunk 디렉토리 정리
    rm -rf trunk/*
    
    # 새 버전 복사
    cp -r "$DIST_DIR/$PACKAGE_NAME/"* trunk/
    
    # 변경사항 확인
    svn status
    
    # 새 파일 추가
    svn add --force trunk/*
    
    # 삭제된 파일 제거
    svn status | grep '^!' | awk '{print $2}' | xargs -I% svn rm %
    
    # 변경사항 커밋
    svn commit -m "Release $VERSION"
    
    # 태그 생성
    svn copy trunk tags/$VERSION
    svn commit -m "Tagging version $VERSION"
    
    cd -
fi

# 결과 출력
echo -e "\n${GREEN}빌드 완료!${NC}"
echo "패키지 위치: $DIST_DIR/$PACKAGE_NAME.zip"
echo "SHA256: $(cat "$DIST_DIR/$PACKAGE_NAME.zip.sha256")"
echo "MD5: $(cat "$DIST_DIR/$PACKAGE_NAME.zip.md5")"

# 정리
if [ "$2" == "--cleanup" ]; then
    echo -e "\n${YELLOW}임시 파일 정리 중...${NC}"
    rm -rf "$DIST_DIR/$PACKAGE_NAME"
fi

echo -e "\n${GREEN}모든 작업이 완료되었습니다!${NC}" 