import { describe, expect, it, beforeEach } from '@jest/globals';
import { execSync } from 'child_process';
import { resolve } from 'path';
import { readFileSync, existsSync, mkdirSync, writeFileSync } from 'fs';
import { CICDService } from '@/services/CICDService';
import { DeploymentService } from '@/services/DeploymentService';

describe('CI/CD 파이프라인 테스트', () => {
  let cicdService: CICDService;
  let deploymentService: DeploymentService;
  const testDir = resolve(__dirname, '../../../.pipeline-test');
  const configPath = resolve(testDir, 'pipeline-config.json');

  beforeEach(() => {
    cicdService = new CICDService();
    deploymentService = new DeploymentService();
    
    // 테스트 디렉토리 생성
    if (!existsSync(testDir)) {
      mkdirSync(testDir, { recursive: true });
    }
    
    // 테스트용 파이프라인 설정 파일 생성
    const pipelineConfig = {
      stages: [
        {
          name: 'build',
          steps: [
            { name: 'install-dependencies', command: 'npm ci' },
            { name: 'build', command: 'npm run build' }
          ]
        },
        {
          name: 'test',
          steps: [
            { name: 'lint', command: 'npm run lint' },
            { name: 'unit-tests', command: 'npm run test:unit' },
            { name: 'integration-tests', command: 'npm run test:integration' }
          ]
        },
        {
          name: 'deploy',
          steps: [
            { name: 'deploy-staging', command: 'npm run deploy:staging' }
          ],
          environments: ['staging']
        }
      ],
      environments: {
        staging: {
          url: 'https://staging.example.com',
          variables: {
            NODE_ENV: 'staging'
          }
        },
        production: {
          url: 'https://example.com',
          variables: {
            NODE_ENV: 'production'
          }
        }
      }
    };
    
    writeFileSync(configPath, JSON.stringify(pipelineConfig, null, 2));
  });

  describe('파이프라인 설정', () => {
    it('파이프라인 설정 파일이 유효해야 합니다', async () => {
      const validationResult = await cicdService.validatePipelineConfig(configPath);
      
      expect(validationResult.valid).toBeTruthy();
      expect(validationResult.errors).toHaveLength(0);
    });

    it('필수 스테이지가 모두 정의되어 있어야 합니다', async () => {
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      const requiredStages = ['build', 'test', 'deploy'];
      
      const stages = config.stages.map(stage => stage.name);
      
      requiredStages.forEach(requiredStage => {
        expect(stages).toContain(requiredStage);
      });
    });

    it('환경별 설정이 올바르게 정의되어 있어야 합니다', async () => {
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      
      expect(config.environments).toBeDefined();
      expect(config.environments.staging).toBeDefined();
      expect(config.environments.production).toBeDefined();
      
      expect(config.environments.staging.url).toBeDefined();
      expect(config.environments.production.url).toBeDefined();
    });
  });

  describe('빌드 프로세스', () => {
    it('빌드 스크립트가 성공적으로 실행되어야 합니다', async () => {
      const buildResult = await cicdService.runBuild({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(buildResult.success).toBeTruthy();
      expect(buildResult.stages.build.success).toBeTruthy();
    });

    it('빌드 결과물이 올바르게 생성되어야 합니다', async () => {
      const buildResult = await cicdService.runBuild({
        configPath,
        environment: 'staging',
        dryRun: false
      });
      
      expect(buildResult.success).toBeTruthy();
      
      // 빌드 결과물 확인
      expect(existsSync(resolve(__dirname, '../../../dist'))).toBeTruthy();
      expect(existsSync(resolve(__dirname, '../../../dist/main.js'))).toBeTruthy();
    });

    it('환경별 빌드 설정이 적용되어야 합니다', async () => {
      // 스테이징 환경 빌드
      const stagingBuildResult = await cicdService.runBuild({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(stagingBuildResult.environment).toBe('staging');
      expect(stagingBuildResult.environmentVariables.NODE_ENV).toBe('staging');
      
      // 프로덕션 환경 빌드
      const productionBuildResult = await cicdService.runBuild({
        configPath,
        environment: 'production',
        dryRun: true
      });
      
      expect(productionBuildResult.environment).toBe('production');
      expect(productionBuildResult.environmentVariables.NODE_ENV).toBe('production');
    });
  });

  describe('테스트 프로세스', () => {
    it('모든 테스트가 성공적으로 실행되어야 합니다', async () => {
      const testResult = await cicdService.runTests({
        configPath,
        dryRun: true
      });
      
      expect(testResult.success).toBeTruthy();
      expect(testResult.stages.test.success).toBeTruthy();
      
      // 각 테스트 단계 확인
      expect(testResult.stages.test.steps.lint.success).toBeTruthy();
      expect(testResult.stages.test.steps['unit-tests'].success).toBeTruthy();
      expect(testResult.stages.test.steps['integration-tests'].success).toBeTruthy();
    });

    it('코드 커버리지가 임계값을 넘어야 합니다', async () => {
      const coverageResult = await cicdService.checkCodeCoverage({
        threshold: 80
      });
      
      expect(coverageResult.success).toBeTruthy();
      expect(coverageResult.coverage.total).toBeGreaterThanOrEqual(80);
    });

    it('테스트 실패 시 파이프라인이 중단되어야 합니다', async () => {
      // 실패하는 테스트 케이스 추가
      const failingTestPath = resolve(testDir, 'failing-test.js');
      writeFileSync(failingTestPath, `
        test('This test will fail', () => {
          expect(true).toBe(false);
        });
      `);
      
      // 파이프라인 설정 수정
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages[1].steps.push({
        name: 'failing-test',
        command: `jest ${failingTestPath}`
      });
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 테스트 실행
      const testResult = await cicdService.runTests({
        configPath,
        dryRun: true,
        continueOnError: false
      });
      
      expect(testResult.success).toBeFalsy();
      expect(testResult.stages.test.success).toBeFalsy();
      
      // 이후 단계가 실행되지 않았는지 확인
      expect(testResult.stages.deploy).toBeUndefined();
    });
  });

  describe('배포 프로세스', () => {
    it('스테이징 환경에 성공적으로 배포되어야 합니다', async () => {
      const deployResult = await cicdService.runDeploy({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(deployResult.success).toBeTruthy();
      expect(deployResult.environment).toBe('staging');
      expect(deployResult.stages.deploy.success).toBeTruthy();
    });

    it('배포 후 헬스 체크가 성공해야 합니다', async () => {
      // 배포 실행
      await cicdService.runDeploy({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      // 헬스 체크
      const healthCheckResult = await deploymentService.performHealthCheck({
        endpoint: '/health',
        url: 'https://staging.example.com',
        timeout: 5000,
        dryRun: true
      });
      
      expect(healthCheckResult.status).toBe('healthy');
      expect(healthCheckResult.responseTime).toBeLessThan(1000);
    });

    it('롤백 기능이 정상적으로 동작해야 합니다', async () => {
      // 이전 배포 버전 저장
      const previousVersion = '1.0.0';
      await deploymentService.recordDeployment({
        version: previousVersion,
        environment: 'staging',
        timestamp: new Date(),
        artifacts: {
          mainJs: 'main.1.0.0.js',
          vendorJs: 'vendor.1.0.0.js'
        }
      });
      
      // 새 버전 배포
      const newVersion = '1.1.0';
      const deployResult = await cicdService.runDeploy({
        configPath,
        environment: 'staging',
        version: newVersion,
        dryRun: true
      });
      
      expect(deployResult.success).toBeTruthy();
      
      // 롤백 실행
      const rollbackResult = await deploymentService.rollback({
        environment: 'staging',
        version: previousVersion,
        dryRun: true
      });
      
      expect(rollbackResult.success).toBeTruthy();
      expect(rollbackResult.version).toBe(previousVersion);
      
      // 현재 배포된 버전 확인
      const currentVersion = await deploymentService.getCurrentVersion('staging');
      expect(currentVersion).toBe(previousVersion);
    });
  });

  describe('파이프라인 통합', () => {
    it('전체 파이프라인이 순차적으로 실행되어야 합니다', async () => {
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(pipelineResult.success).toBeTruthy();
      
      // 각 스테이지가 순서대로 실행되었는지 확인
      const stageOrder = Object.keys(pipelineResult.stages);
      expect(stageOrder).toEqual(['build', 'test', 'deploy']);
      
      // 모든 스테이지가 성공했는지 확인
      Object.values(pipelineResult.stages).forEach(stage => {
        expect(stage.success).toBeTruthy();
      });
    });

    it('병렬 실행 스테이지가 올바르게 처리되어야 합니다', async () => {
      // 병렬 실행 스테이지 추가
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages[1].parallel = true;
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 파이프라인 실행
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(pipelineResult.success).toBeTruthy();
      
      // 병렬 스테이지의 실행 시간 확인
      const testStage = pipelineResult.stages.test;
      const totalStepTime = Object.values(testStage.steps).reduce(
        (sum, step: any) => sum + step.duration,
        0
      );
      
      // 병렬 실행이므로 전체 스테이지 시간이 개별 스텝 시간의 합보다 작아야 함
      expect(testStage.duration).toBeLessThan(totalStepTime);
    });

    it('조건부 스테이지가 올바르게 처리되어야 합니다', async () => {
      // 조건부 스테이지 추가
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages.push({
        name: 'production-deploy',
        condition: "environment === 'production'",
        steps: [
          { name: 'deploy-production', command: 'npm run deploy:production' }
        ]
      });
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 스테이징 환경에서 파이프라인 실행
      const stagingResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      // production-deploy 스테이지가 실행되지 않았는지 확인
      expect(stagingResult.stages['production-deploy']).toBeUndefined();
      
      // 프로덕션 환경에서 파이프라인 실행
      const productionResult = await cicdService.runPipeline({
        configPath,
        environment: 'production',
        dryRun: true
      });
      
      // production-deploy 스테이지가 실행되었는지 확인
      expect(productionResult.stages['production-deploy']).toBeDefined();
      expect(productionResult.stages['production-deploy'].success).toBeTruthy();
    });
  });

  describe('알림 및 보고', () => {
    it('파이프라인 실행 결과가 올바르게 보고되어야 합니다', async () => {
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      const reportResult = await cicdService.generateReport(pipelineResult);
      
      expect(reportResult.success).toBeTruthy();
      expect(reportResult.report).toBeDefined();
      expect(reportResult.report.summary).toBeDefined();
      expect(reportResult.report.stages).toBeDefined();
      
      // 보고서에 모든 스테이지가 포함되어 있는지 확인
      Object.keys(pipelineResult.stages).forEach(stageName => {
        expect(reportResult.report.stages[stageName]).toBeDefined();
      });
    });

    it('파이프라인 실패 시 알림이 전송되어야 합니다', async () => {
      // 실패하는 스텝 추가
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages[0].steps.push({
        name: 'failing-step',
        command: 'exit 1'
      });
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 파이프라인 실행
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true,
        notifications: {
          email: 'team@example.com',
          slack: 'https://hooks.slack.com/services/xxx'
        }
      });
      
      expect(pipelineResult.success).toBeFalsy();
      
      // 알림이 전송되었는지 확인
      const notifications = await cicdService.getNotifications();
      const failureNotification = notifications.find(n => 
        n.type === 'pipeline_failure' && 
        n.pipelineId === pipelineResult.id
      );
      
      expect(failureNotification).toBeDefined();
      expect(failureNotification.recipients).toContain('team@example.com');
    });
  });

  describe('보안 검사', () => {
    it('의존성 취약점 검사가 파이프라인에 통합되어야 합니다', async () => {
      // 보안 검사 스텝 추가
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages[1].steps.push({
        name: 'security-scan',
        command: 'npm audit'
      });
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 파이프라인 실행
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true
      });
      
      expect(pipelineResult.success).toBeTruthy();
      expect(pipelineResult.stages.test.steps['security-scan']).toBeDefined();
      expect(pipelineResult.stages.test.steps['security-scan'].success).toBeTruthy();
    });

    it('심각한 보안 취약점이 있으면 파이프라인이 실패해야 합니다', async () => {
      // 취약점이 있는 의존성 추가 (시뮬레이션)
      const packageJsonPath = resolve(testDir, 'package.json');
      const packageJson = {
        name: 'test-project',
        dependencies: {
          'vulnerable-package': '1.0.0'
        }
      };
      
      writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2));
      
      // 보안 검사 스텝 추가 (취약점 발견 시 실패하도록 설정)
      const config = JSON.parse(readFileSync(configPath, 'utf-8'));
      config.stages[1].steps.push({
        name: 'security-scan',
        command: `npm audit --audit-level=high --json > ${testDir}/audit-result.json || true`,
        postCheck: `node -e "process.exit(require('${testDir}/audit-result.json').metadata.vulnerabilities.high > 0 ? 1 : 0)"`
      });
      
      writeFileSync(configPath, JSON.stringify(config, null, 2));
      
      // 파이프라인 실행
      const pipelineResult = await cicdService.runPipeline({
        configPath,
        environment: 'staging',
        dryRun: true,
        packageJsonPath
      });
      
      // 보안 검사 실패로 인해 파이프라인이 실패해야 함
      expect(pipelineResult.success).toBeFalsy();
      expect(pipelineResult.stages.test.steps['security-scan'].success).toBeFalsy();
    });
  });
}); 