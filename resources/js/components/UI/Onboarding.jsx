import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Button, 
  Dialog, 
  DialogActions, 
  DialogContent, 
  DialogTitle, 
  IconButton, 
  MobileStepper, 
  Paper, 
  Tooltip, 
  Typography, 
  useMediaQuery 
} from '@mui/material';
import { 
  Close as CloseIcon, 
  KeyboardArrowLeft, 
  KeyboardArrowRight, 
  LightbulbOutlined as TipIcon 
} from '@mui/icons-material';
import { useTheme } from './ThemeProvider';

/**
 * 온보딩 단계 정의
 * @type {Array<{title: string, content: string, image?: string, tips?: Array<string>}>}
 */
const DEFAULT_STEPS = [
  {
    title: '로켓 소서에 오신 것을 환영합니다!',
    content: '로켓 소서는 효율적인 소싱 작업을 위한 올인원 플랫폼입니다. 이 가이드를 통해 주요 기능을 빠르게 살펴보세요.',
    image: '/images/onboarding/welcome.png',
    tips: ['화면 우측 상단의 프로필 메뉴에서 언제든지 이 가이드를 다시 볼 수 있습니다.']
  },
  {
    title: '대시보드 살펴보기',
    content: '대시보드에서는 판매 성과, 인기 제품, 재고 상태 등 중요한 정보를 한눈에 확인할 수 있습니다.',
    image: '/images/onboarding/dashboard.png',
    tips: ['대시보드 위젯은 드래그 앤 드롭으로 자유롭게 배치할 수 있습니다.', '각 차트는 기간 필터를 통해 원하는 기간의 데이터를 확인할 수 있습니다.']
  },
  {
    title: '소싱 추천 활용하기',
    content: '인공지능 기반 소싱 추천 시스템을 통해 높은 수익성과 성장 가능성이 있는 제품을 발견하세요.',
    image: '/images/onboarding/recommendations.png',
    tips: ['추천 결과에 피드백을 제공하면 더 정확한 추천을 받을 수 있습니다.', '카테고리 필터를 활용하여 특정 분야의 추천 제품을 확인하세요.']
  },
  {
    title: '경쟁사 모니터링',
    content: '경쟁사의 가격, 재고, 리뷰 변화를 실시간으로 모니터링하고 빠르게 대응하세요.',
    image: '/images/onboarding/competitors.png',
    tips: ['중요한 경쟁사 변화는 알림을 통해 즉시 확인할 수 있습니다.', '경쟁사 분석 리포트를 통해 시장 동향을 파악하세요.']
  },
  {
    title: '수익성 계산기',
    content: '제품의 원가, 배송비, 수수료 등을 고려한 정확한 수익성 분석으로 현명한 소싱 결정을 내리세요.',
    image: '/images/onboarding/calculator.png',
    tips: ['여러 시나리오를 비교하여 최적의 판매 가격을 결정하세요.', '계산 결과는 저장하여 나중에 참조할 수 있습니다.']
  },
  {
    title: '이제 시작해볼까요?',
    content: '기본적인 기능을 살펴보았습니다. 이제 로켓 소서와 함께 효율적인 소싱을 시작해보세요!',
    image: '/images/onboarding/start.png',
    tips: ['추가 도움이 필요하면 우측 하단의 도움말 버튼을 클릭하세요.', '새로운 기능은 정기적으로 업데이트됩니다. 공지사항을 확인하세요.']
  }
];

/**
 * 온보딩 팁 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {Array<string>} props.tips - 팁 목록
 */
const OnboardingTips = ({ tips }) => {
  if (!tips || tips.length === 0) return null;
  
  return (
    <Box sx={{ mt: 2, p: 2, bgcolor: 'background.paper', borderRadius: 1, boxShadow: 1 }}>
      <Typography variant="subtitle2" sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
        <TipIcon sx={{ mr: 1, color: 'warning.main' }} />
        유용한 팁
      </Typography>
      <Box component="ul" sx={{ pl: 2, m: 0 }}>
        {tips.map((tip, index) => (
          <Typography component="li" variant="body2" key={index} sx={{ mb: 0.5 }}>
            {tip}
          </Typography>
        ))}
      </Box>
    </Box>
  );
};

/**
 * 온보딩 모달 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {boolean} props.open - 모달 열림 상태
 * @param {Function} props.onClose - 모달 닫기 핸들러
 * @param {Array} props.steps - 온보딩 단계 (기본값: DEFAULT_STEPS)
 * @param {boolean} props.showSkip - 건너뛰기 버튼 표시 여부 (기본값: true)
 */
export const OnboardingModal = ({ 
  open, 
  onClose, 
  steps = DEFAULT_STEPS, 
  showSkip = true 
}) => {
  const { theme } = useTheme();
  const [activeStep, setActiveStep] = useState(0);
  const maxSteps = steps.length;
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  // 다음 단계로 이동
  const handleNext = () => {
    setActiveStep((prevStep) => prevStep + 1);
  };

  // 이전 단계로 이동
  const handleBack = () => {
    setActiveStep((prevStep) => prevStep - 1);
  };

  // 모달이 닫힐 때 activeStep 초기화
  useEffect(() => {
    if (!open) {
      setActiveStep(0);
    }
  }, [open]);

  // 마지막 단계에서 완료 시 처리
  const handleComplete = () => {
    // 온보딩 완료 상태 저장
    localStorage.setItem('onboardingCompleted', 'true');
    onClose();
  };

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="md"
      fullWidth
      fullScreen={isMobile}
      PaperProps={{
        sx: {
          borderRadius: isMobile ? 0 : 2,
          overflow: 'hidden'
        }
      }}
    >
      <DialogTitle sx={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        borderBottom: `1px solid ${theme.palette.divider}`,
        pb: 1
      }}>
        <Typography variant="h6">{steps[activeStep].title}</Typography>
        <IconButton edge="end" onClick={onClose} aria-label="close">
          <CloseIcon />
        </IconButton>
      </DialogTitle>
      
      <DialogContent sx={{ p: 0, position: 'relative' }}>
        <Box sx={{ 
          display: 'flex', 
          flexDirection: isMobile ? 'column' : 'row',
          height: isMobile ? 'auto' : 400
        }}>
          {/* 이미지 영역 */}
          {steps[activeStep].image && (
            <Box 
              sx={{ 
                width: isMobile ? '100%' : '50%',
                height: isMobile ? 200 : '100%',
                backgroundImage: `url(${steps[activeStep].image})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                position: 'relative'
              }}
            />
          )}
          
          {/* 콘텐츠 영역 */}
          <Box sx={{ 
            width: isMobile || !steps[activeStep].image ? '100%' : '50%',
            p: 3,
            display: 'flex',
            flexDirection: 'column'
          }}>
            <Typography variant="body1" sx={{ mb: 2 }}>
              {steps[activeStep].content}
            </Typography>
            
            {/* 팁 영역 */}
            {steps[activeStep].tips && (
              <OnboardingTips tips={steps[activeStep].tips} />
            )}
            
            <Box sx={{ flexGrow: 1 }} />
            
            {/* 스텝퍼 */}
            <MobileStepper
              variant="dots"
              steps={maxSteps}
              position="static"
              activeStep={activeStep}
              sx={{ 
                bgcolor: 'transparent', 
                mt: 2,
                '.MuiMobileStepper-dot': {
                  mx: 0.5
                },
                '.MuiMobileStepper-dotActive': {
                  bgcolor: 'primary.main'
                }
              }}
              nextButton={
                activeStep === maxSteps - 1 ? (
                  <Button size="small" onClick={handleComplete} variant="contained" color="primary">
                    완료
                  </Button>
                ) : (
                  <Button size="small" onClick={handleNext}>
                    다음
                    <KeyboardArrowRight />
                  </Button>
                )
              }
              backButton={
                <Button size="small" onClick={handleBack} disabled={activeStep === 0}>
                  <KeyboardArrowLeft />
                  이전
                </Button>
              }
            />
          </Box>
        </Box>
      </DialogContent>
      
      {showSkip && activeStep < maxSteps - 1 && (
        <DialogActions sx={{ borderTop: `1px solid ${theme.palette.divider}` }}>
          <Button onClick={onClose} color="inherit">건너뛰기</Button>
        </DialogActions>
      )}
    </Dialog>
  );
};

/**
 * 온보딩 툴팁 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {React.ReactNode} props.children - 자식 요소
 * @param {string} props.title - 툴팁 제목
 * @param {string} props.content - 툴팁 내용
 * @param {string} props.placement - 툴팁 위치 (기본값: 'bottom')
 * @param {string} props.featureId - 기능 ID (로컬 스토리지에 저장됨)
 */
export const OnboardingTooltip = ({ 
  children, 
  title, 
  content, 
  placement = 'bottom',
  featureId
}) => {
  const [open, setOpen] = useState(false);
  
  useEffect(() => {
    // 이미 본 툴팁인지 확인
    const viewedTooltips = JSON.parse(localStorage.getItem('viewedTooltips') || '{}');
    if (!viewedTooltips[featureId]) {
      // 약간의 지연 후 툴팁 표시
      const timer = setTimeout(() => {
        setOpen(true);
      }, 1000);
      
      return () => clearTimeout(timer);
    }
  }, [featureId]);
  
  const handleClose = () => {
    setOpen(false);
    
    // 툴팁을 본 것으로 표시
    if (featureId) {
      const viewedTooltips = JSON.parse(localStorage.getItem('viewedTooltips') || '{}');
      viewedTooltips[featureId] = true;
      localStorage.setItem('viewedTooltips', JSON.stringify(viewedTooltips));
    }
  };
  
  return (
    <Tooltip
      open={open}
      onClose={handleClose}
      title={
        <Box>
          {title && <Typography variant="subtitle2">{title}</Typography>}
          <Typography variant="body2">{content}</Typography>
          <Button 
            size="small" 
            color="inherit" 
            onClick={handleClose}
            sx={{ mt: 1, textTransform: 'none' }}
          >
            확인
          </Button>
        </Box>
      }
      placement={placement}
      arrow
      componentsProps={{
        tooltip: {
          sx: {
            bgcolor: 'primary.main',
            maxWidth: 300,
            p: 2,
            '& .MuiTooltip-arrow': {
              color: 'primary.main',
            },
          }
        }
      }}
    >
      {children}
    </Tooltip>
  );
};

/**
 * 온보딩 하이라이트 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {React.ReactNode} props.children - 자식 요소
 * @param {string} props.featureId - 기능 ID (로컬 스토리지에 저장됨)
 * @param {Object} props.sx - 추가 스타일
 */
export const OnboardingHighlight = ({ children, featureId, sx = {} }) => {
  const [highlight, setHighlight] = useState(false);
  
  useEffect(() => {
    // 이미 본 하이라이트인지 확인
    const viewedHighlights = JSON.parse(localStorage.getItem('viewedHighlights') || '{}');
    if (!viewedHighlights[featureId]) {
      setHighlight(true);
      
      // 10초 후 하이라이트 제거
      const timer = setTimeout(() => {
        setHighlight(false);
        
        // 하이라이트를 본 것으로 표시
        viewedHighlights[featureId] = true;
        localStorage.setItem('viewedHighlights', JSON.stringify(viewedHighlights));
      }, 10000);
      
      return () => clearTimeout(timer);
    }
  }, [featureId]);
  
  return (
    <Box
      sx={{
        position: 'relative',
        animation: highlight ? 'pulse 2s infinite' : 'none',
        '@keyframes pulse': {
          '0%': { boxShadow: '0 0 0 0 rgba(63, 81, 181, 0.4)' },
          '70%': { boxShadow: '0 0 0 10px rgba(63, 81, 181, 0)' },
          '100%': { boxShadow: '0 0 0 0 rgba(63, 81, 181, 0)' }
        },
        ...sx
      }}
    >
      {children}
    </Box>
  );
};

/**
 * 온보딩 컨텍스트 훅
 * @returns {{showOnboarding: Function, isFirstVisit: boolean}}
 */
export const useOnboarding = () => {
  const [isOpen, setIsOpen] = useState(false);
  
  // 온보딩 모달 표시
  const showOnboarding = () => {
    setIsOpen(true);
  };
  
  // 온보딩 모달 닫기
  const hideOnboarding = () => {
    setIsOpen(false);
  };
  
  // 첫 방문 여부 확인
  const isFirstVisit = !localStorage.getItem('onboardingCompleted');
  
  return {
    isOpen,
    showOnboarding,
    hideOnboarding,
    isFirstVisit
  };
};

/**
 * 온보딩 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {boolean} props.autoShow - 자동 표시 여부 (기본값: true)
 */
export const Onboarding = ({ autoShow = true }) => {
  const { isOpen, showOnboarding, hideOnboarding, isFirstVisit } = useOnboarding();
  
  // 첫 방문 시 자동으로 온보딩 모달 표시
  useEffect(() => {
    if (autoShow && isFirstVisit) {
      // 약간의 지연 후 온보딩 모달 표시
      const timer = setTimeout(() => {
        showOnboarding();
      }, 1000);
      
      return () => clearTimeout(timer);
    }
  }, [autoShow, isFirstVisit, showOnboarding]);
  
  return (
    <OnboardingModal
      open={isOpen}
      onClose={hideOnboarding}
      steps={DEFAULT_STEPS}
    />
  );
};

export default Onboarding; 