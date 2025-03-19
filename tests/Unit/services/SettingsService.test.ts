import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { SettingsService } from '@/services/SettingsService';
import { ThemeSettings, LanguageSettings } from '@/types/Settings';

describe('SettingsService', () => {
  let service: SettingsService;
  
  beforeEach(() => {
    service = new SettingsService();
    localStorage.clear();
    jest.clearAllMocks();
  });

  describe('getThemeSettings', () => {
    it('테마 설정을 가져와야 합니다', async () => {
      const mockThemeSettings: ThemeSettings = {
        mode: 'light',
        colorScheme: '기본',
        isDense: false,
        fontSize: 14,
        borderRadius: 4
      };

      jest.spyOn(service['api'], 'get').mockResolvedValue({ 
        data: mockThemeSettings 
      });

      const result = await service.getThemeSettings();
      expect(result).toEqual(mockThemeSettings);
    });
  });

  describe('updateThemeSettings', () => {
    it('테마 설정을 업데이트해야 합니다', async () => {
      const themeSettings: ThemeSettings = {
        mode: 'dark',
        colorScheme: '퍼플',
        isDense: true,
        fontSize: 16,
        borderRadius: 8
      };

      jest.spyOn(service['api'], 'put').mockResolvedValue({
        data: { message: '테마 설정이 업데이트되었습니다.' }
      });

      const result = await service.updateThemeSettings(themeSettings);
      expect(result).toHaveProperty('message');
    });
  });

  describe('getLanguageSettings', () => {
    it('언어 설정을 가져와야 합니다', async () => {
      const mockLanguageSettings: LanguageSettings = {
        language: 'ko',
        dateFormat: 'YYYY-MM-DD',
        timeFormat: '24',
        timezone: 'Asia/Seoul',
        numberFormat: 'ko-KR'
      };

      jest.spyOn(service['api'], 'get').mockResolvedValue({
        data: mockLanguageSettings
      });

      const result = await service.getLanguageSettings();
      expect(result).toEqual(mockLanguageSettings);
    });
  });

  describe('updateLanguageSettings', () => {
    it('언어 설정을 업데이트해야 합니다', async () => {
      const languageSettings: LanguageSettings = {
        language: 'en',
        dateFormat: 'MM/DD/YYYY',
        timeFormat: '12',
        timezone: 'America/New_York',
        numberFormat: 'en-US'
      };

      jest.spyOn(service['api'], 'put').mockResolvedValue({
        data: { message: '언어 설정이 업데이트되었습니다.' }
      });

      const result = await service.updateLanguageSettings(languageSettings);
      expect(result).toHaveProperty('message');
    });
  });

  describe('exportData', () => {
    it('데이터를 내보내기해야 합니다', async () => {
      const exportOptions = {
        format: 'excel',
        options: {
          products: true,
          calculations: true
        }
      };

      const mockBlob = new Blob(['test data'], { type: 'application/vnd.ms-excel' });
      jest.spyOn(service['api'], 'post').mockResolvedValue({
        data: mockBlob
      });

      const result = await service.exportData(exportOptions);
      expect(result).toBeInstanceOf(Blob);
    });
  });

  describe('importData', () => {
    it('데이터를 가져오기해야 합니다', async () => {
      const formData = new FormData();
      const file = new File(['test data'], 'test.xlsx', { type: 'application/vnd.ms-excel' });
      formData.append('file', file);
      formData.append('options', JSON.stringify({ products: true }));

      jest.spyOn(service['api'], 'post').mockResolvedValue({
        data: { message: '데이터가 성공적으로 가져와졌습니다.' }
      });

      const result = await service.importData(formData);
      expect(result).toHaveProperty('message');
    });
  });

  describe('getBackups', () => {
    it('백업 목록을 가져와야 합니다', async () => {
      const mockBackups = [
        {
          id: '1',
          filename: 'backup-2024-01-01.zip',
          size: 1024,
          created_at: '2024-01-01T00:00:00Z',
          type: 'manual',
          status: 'completed'
        }
      ];

      jest.spyOn(service['api'], 'get').mockResolvedValue({
        data: mockBackups
      });

      const result = await service.getBackups();
      expect(result).toEqual(mockBackups);
      expect(result[0]).toHaveProperty('id');
      expect(result[0]).toHaveProperty('filename');
    });
  });

  describe('createBackup', () => {
    it('새로운 백업을 생성해야 합니다', async () => {
      jest.spyOn(service['api'], 'post').mockResolvedValue({
        data: { message: '백업이 성공적으로 생성되었습니다.' }
      });

      const result = await service.createBackup();
      expect(result).toHaveProperty('message');
    });
  });
}); 