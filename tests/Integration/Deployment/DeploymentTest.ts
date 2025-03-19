import { describe, expect, it, beforeEach } from '@jest/globals';
import { execSync } from 'child_process';
import { resolve } from 'path';
import { readFileSync, existsSync } from 'fs';
import { build } from '@/scripts/build';
import { DeploymentService } from '@/services/DeploymentService';
import { EnvironmentService } from '@/services/EnvironmentService';

describe('배포 테스트', () => {
  let deploymentService: DeploymentService;
  let environmentService: EnvironmentService;

  beforeEach(() => {
    deploymentService = new DeploymentService();
    environmentService = new EnvironmentService();
  });

  describe('빌드 프로세스', () => {
    it('프로덕션 빌드가 성공적으로 생성되어야 합니다', async () => {
      const result = await build({ environment: 'production' });
      
      expect(result.success).toBeTruthy();
      expect(existsSync(resolve(__dirname, '../../../dist'))).toBeTruthy();
      
      // 번들 파일 확인
      const bundleFiles = [
        'main.js',
        'main.css',
        'vendor.js'
      ];

      bundleFiles.forEach(file => {
        expect(existsSync(resolve(__dirname, `../../../dist/${file}`))).toBeTruthy();
      });
    });

    it('번들 크기가 적절해야 합니다', async () => {
      await build({ environment: 'production' });
      
      const mainBundle = readFileSync(resolve(__dirname, '../../../dist/main.js'));
      const mainBundleSizeInMB = mainBundle.length / (1024 * 1024);
      
      expect(mainBundleSizeInMB).toBeLessThan(1); // 1MB 이하
    });

    it('소스맵이 올바르게 생성되어야 합니다', async () => {
      await build({ environment: 'production' });
      
      const sourceMapFiles = [
        'main.js.map',
        'vendor.js.map'
      ];

      sourceMapFiles.forEach(file => {
        const sourceMap = JSON.parse(
          readFileSync(resolve(__dirname, `../../../dist/${file}`), 'utf-8')
        );
        
        expect(sourceMap.version).toBe(3);
        expect(sourceMap.sources).toBeDefined();
        expect(sourceMap.mappings).toBeDefined();
      });
    });
  });

  describe('환경 설정', () => {
    it('환경 변수가 올바르게 로드되어야 합니다', async () => {
      const env = await environmentService.loadEnvironment('production');
      
      const requiredVars = [
        'DATABASE_URL',
        'REDIS_URL',
        'API_KEY',
        'NODE_ENV'
      ];

      requiredVars.forEach(variable => {
        expect(env[variable]).toBeDefined();
      });
    });

    it('프로덕션 환경에서 디버그 모드가 비활성화되어야 합니다', async () => {
      const env = await environmentService.loadEnvironment('production');
      expect(env.DEBUG).toBeFalsy();
    });

    it('민감한 정보가 안전하게 처리되어야 합니다', async () => {
      const env = await environmentService.loadEnvironment('production');
      
      // 민감한 정보가 암호화되어 있는지 확인
      const sensitiveVars = ['API_KEY', 'DATABASE_PASSWORD'];
      sensitiveVars.forEach(variable => {
        expect(env[variable]).toMatch(/^encrypted:/);
      });
    });
  });

  describe('데이터베이스 배포', () => {
    it('프로덕션 데이터베이스 마이그레이션이 안전하게 실행되어야 합니다', async () => {
      const migrationResult = await deploymentService.runDatabaseMigration({
        environment: 'production',
        dryRun: true
      });

      expect(migrationResult.success).toBeTruthy();
      expect(migrationResult.warnings).toHaveLength(0);
    });

    it('롤백 계획이 준비되어 있어야 합니다', async () => {
      const rollbackPlan = await deploymentService.generateRollbackPlan();
      
      expect(rollbackPlan).toMatchObject({
        steps: expect.any(Array),
        estimatedTime: expect.any(Number)
      });
    });
  });

  describe('서버 배포', () => {
    it('무중단 배포가 가능해야 합니다', async () => {
      const deploymentResult = await deploymentService.performZeroDowntimeDeployment({
        version: '1.0.0',
        dryRun: true
      });

      expect(deploymentResult.success).toBeTruthy();
      expect(deploymentResult.downtime).toBe(0);
    });

    it('이전 버전으로 롤백이 가능해야 합니다', async () => {
      const rollbackResult = await deploymentService.rollback({
        version: '0.9.0',
        dryRun: true
      });

      expect(rollbackResult.success).toBeTruthy();
    });

    it('서버 상태 검사가 올바르게 동작해야 합니다', async () => {
      const healthCheck = await deploymentService.performHealthCheck({
        endpoint: '/health',
        timeout: 5000
      });

      expect(healthCheck.status).toBe('healthy');
      expect(healthCheck.responseTime).toBeLessThan(1000);
    });
  });

  describe('캐시 관리', () => {
    it('캐시 무효화가 올바르게 수행되어야 합니다', async () => {
      const invalidationResult = await deploymentService.invalidateCache({
        patterns: ['/*'],
        dryRun: true
      });

      expect(invalidationResult.success).toBeTruthy();
      expect(invalidationResult.invalidatedKeys).toBeGreaterThan(0);
    });
  });

  describe('보안 검사', () => {
    it('의존성 취약점 검사가 수행되어야 합니다', async () => {
      const securityScan = await deploymentService.performSecurityScan();
      
      expect(securityScan.vulnerabilities).toHaveLength(0);
      expect(securityScan.outdatedDependencies).toHaveLength(0);
    });

    it('환경 설정에 보안 위험이 없어야 합니다', async () => {
      const securityCheck = await deploymentService.checkSecurityConfig();
      
      expect(securityCheck.issues).toHaveLength(0);
    });
  });
}); 