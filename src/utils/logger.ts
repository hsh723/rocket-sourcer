type LogLevel = 'info' | 'warning' | 'error';

export const logger = {
  log(level: LogLevel, message: string, data?: any) {
    const timestamp = new Date().toISOString();
    const logData = {
      timestamp,
      level,
      message,
      data
    };

    switch (level) {
      case 'error':
        console.error(logData);
        // TODO: 에러 모니터링 서비스로 전송
        break;
      case 'warning':
        console.warn(logData);
        break;
      default:
        console.log(logData);
    }
  }
};
