$sourceDir = "작업 디렉토리 경로"
$backupDir = "백업 디렉토리 경로"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = Join-Path $backupDir "backup_$timestamp"

# 디렉토리 생성
New-Item -ItemType Directory -Path $backupPath -Force

# 파일 복사
Copy-Item -Path "$sourceDir\*" -Destination $backupPath -Recurse
