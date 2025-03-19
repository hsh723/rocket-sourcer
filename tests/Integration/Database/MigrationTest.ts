import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import { db } from '@/database';
import { resolve } from 'path';
import { readdirSync } from 'fs';

describe('데이터베이스 마이그레이션 테스트', () => {
  beforeEach(async () => {
    // 데이터베이스 초기화
    await db.migrate.rollback({ all: true });
  });

  afterEach(async () => {
    await db.migrate.rollback({ all: true });
  });

  describe('마이그레이션 실행', () => {
    it('모든 마이그레이션이 순차적으로 실행되어야 합니다', async () => {
      const result = await db.migrate.latest();
      const [batchNo, log] = result;

      expect(batchNo).toBe(1);
      expect(log.length).toBeGreaterThan(0);

      // 모든 테이블이 생성되었는지 확인
      const tables = await db.raw(`
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
      `);

      const expectedTables = [
        'users',
        'products',
        'calculations',
        'settings',
        'logs',
        'metrics'
      ];

      expectedTables.forEach(table => {
        expect(tables.rows.some(row => row.table_name === table)).toBeTruthy();
      });
    });

    it('마이그레이션 롤백이 올바르게 동작해야 합니다', async () => {
      // 먼저 마이그레이션 실행
      await db.migrate.latest();

      // 롤백 실행
      const result = await db.migrate.rollback();
      const [batchNo, log] = result;

      expect(batchNo).toBe(0);
      expect(log.length).toBeGreaterThan(0);

      // 테이블이 삭제되었는지 확인
      const tables = await db.raw(`
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
      `);

      expect(tables.rows.length).toBe(0);
    });
  });

  describe('스키마 검증', () => {
    beforeEach(async () => {
      await db.migrate.latest();
    });

    it('users 테이블이 올바른 스키마를 가져야 합니다', async () => {
      const columns = await db('users').columnInfo();

      expect(columns).toMatchObject({
        id: { type: 'uuid', nullable: false },
        email: { type: 'character varying', nullable: false },
        password: { type: 'character varying', nullable: false },
        name: { type: 'character varying', nullable: true },
        created_at: { type: 'timestamp', nullable: false },
        updated_at: { type: 'timestamp', nullable: false }
      });

      // 인덱스 확인
      const indexes = await db.raw(`
        SELECT indexname, indexdef
        FROM pg_indexes
        WHERE tablename = 'users'
      `);

      expect(indexes.rows).toContainEqual(
        expect.objectContaining({
          indexname: 'users_email_unique',
          indexdef: expect.stringContaining('CREATE UNIQUE INDEX')
        })
      );
    });

    it('products 테이블이 올바른 외래 키 제약을 가져야 합니다', async () => {
      const foreignKeys = await db.raw(`
        SELECT
          tc.constraint_name,
          kcu.column_name,
          ccu.table_name AS foreign_table_name,
          ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
          ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage ccu
          ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY'
          AND tc.table_name = 'products'
      `);

      expect(foreignKeys.rows).toContainEqual(
        expect.objectContaining({
          column_name: 'user_id',
          foreign_table_name: 'users',
          foreign_column_name: 'id'
        })
      );
    });
  });

  describe('데이터 마이그레이션', () => {
    it('시드 데이터가 올바르게 적용되어야 합니다', async () => {
      await db.migrate.latest();
      await db.seed.run();

      const categories = await db('categories').select();
      expect(categories.length).toBeGreaterThan(0);

      const adminUser = await db('users')
        .where('email', 'admin@example.com')
        .first();
      expect(adminUser).toBeTruthy();
    });

    it('데이터 변환이 올바르게 수행되어야 합니다', async () => {
      // 이전 버전의 데이터 구조로 데이터 생성
      await db.schema.createTable('old_products', table => {
        table.increments('id');
        table.string('name');
        table.decimal('price', 10, 2);
      });

      await db('old_products').insert([
        { name: '상품 1', price: 10000 },
        { name: '상품 2', price: 20000 }
      ]);

      // 마이그레이션 실행
      await db.migrate.latest();

      // 데이터가 새 구조로 올바르게 변환되었는지 확인
      const newProducts = await db('products').select();
      expect(newProducts).toHaveLength(2);
      expect(newProducts[0]).toMatchObject({
        name: '상품 1',
        price: expect.any(Number),
        created_at: expect.any(Date)
      });
    });
  });

  describe('마이그레이션 파일 검증', () => {
    it('마이그레이션 파일이 올바른 명명 규칙을 따라야 합니다', () => {
      const migrationFiles = readdirSync(resolve(__dirname, '../../../migrations'));
      
      migrationFiles.forEach(file => {
        expect(file).toMatch(/^\d{14}_[a-z_]+\.(js|ts)$/);
      });
    });

    it('각 마이그레이션 파일이 up과 down 메소드를 포함해야 합니다', async () => {
      const migrationFiles = readdirSync(resolve(__dirname, '../../../migrations'));
      
      for (const file of migrationFiles) {
        const migration = require(resolve(__dirname, '../../../migrations', file));
        
        expect(typeof migration.up).toBe('function');
        expect(typeof migration.down).toBe('function');
      });
    });
  });

  describe('동시성 처리', () => {
    it('동시 마이그레이션 실행을 올바르게 처리해야 합니다', async () => {
      const migrations = Array.from({ length: 3 }, () => db.migrate.latest());
      
      await expect(Promise.all(migrations)).resolves.toBeDefined();
      
      // 마이그레이션이 한 번만 실행되었는지 확인
      const [batchNo] = await db.migrate.currentVersion();
      expect(Number(batchNo)).toBe(1);
    });
  });
}); 