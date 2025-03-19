import React, { useState, useEffect, useCallback, createContext, useContext } from 'react';
import { 
  Box, 
  Button, 
  Dialog, 
  DialogActions, 
  DialogContent, 
  DialogTitle, 
  Divider, 
  IconButton, 
  List, 
  ListItem, 
  ListItemText, 
  Paper, 
  Tooltip, 
  Typography, 
  useMediaQuery 
} from '@mui/material';
import { 
  Close as CloseIcon, 
  Keyboard as KeyboardIcon, 
  Search as SearchIcon,
  Add as AddIcon,
  Settings as SettingsIcon,
  Help as HelpIcon
} from '@mui/icons-material';
import { useTheme } from './ThemeProvider';

// 단축키 컨텍스트 생성
const ShortcutsContext = createContext({
  registerShortcut: () => {},
  unregisterShortcut: () => {},
  showShortcutsDialog: () => {},
  shortcuts: [],
});

/**
 * 단축키 컨텍스트 사용을 위한 훅
 * @returns {Object} 단축키 컨텍스트
 */
export const useShortcuts = () => useContext(ShortcutsContext);

/**
 * 키 조합을 문자열로 변환
 * @param {Object} shortcut - 단축키 객체
 * @returns {string} 키 조합 문자열
 */
const formatKeyCombination = (shortcut) => {
  const { ctrlKey, altKey, shiftKey, metaKey, key } = shortcut;
  const parts = [];
  
  if (ctrlKey) parts.push('Ctrl');
  if (altKey) parts.push('Alt');
  if (shiftKey) parts.push('Shift');
  if (metaKey) parts.push(navigator.platform.includes('Mac') ? '⌘' : 'Win');
  
  // 특수 키 처리
  let displayKey = key;
  if (key === ' ') displayKey = 'Space';
  else if (key === 'ArrowUp') displayKey = '↑';
  else if (key === 'ArrowDown') displayKey = '↓';
  else if (key === 'ArrowLeft') displayKey = '←';
  else if (key === 'ArrowRight') displayKey = '→';
  else if (key === 'Escape') displayKey = 'Esc';
  else if (key === 'Delete') displayKey = 'Del';
  else if (key === 'Insert') displayKey = 'Ins';
  
  // 단일 문자 키는 대문자로 표시
  if (displayKey.length === 1) {
    displayKey = displayKey.toUpperCase();
  }
  
  parts.push(displayKey);
  
  return parts.join(' + ');
};

/**
 * 단축키 키 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {string} props.keyName - 키 이름
 */
const KeyComponent = ({ keyName }) => {
  return (
    <Box
      component="span"
      sx={{
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        minWidth: '28px',
        height: '28px',
        padding: '0 6px',
        margin: '0 2px',
        borderRadius: '4px',
        backgroundColor: 'background.paper',
        border: '1px solid',
        borderColor: 'divider',
        boxShadow: '0 1px 1px rgba(0,0,0,0.1)',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.8rem',
      }}
    >
      {keyName}
    </Box>
  );
};

/**
 * 단축키 조합 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {Object} props.shortcut - 단축키 객체
 */
export const ShortcutKeys = ({ shortcut }) => {
  const keys = formatKeyCombination(shortcut).split(' + ');
  
  return (
    <Box sx={{ display: 'inline-flex', alignItems: 'center' }}>
      {keys.map((key, index) => (
        <React.Fragment key={index}>
          <KeyComponent keyName={key} />
          {index < keys.length - 1 && (
            <Typography variant="body2" sx={{ mx: 0.5 }}>+</Typography>
          )}
        </React.Fragment>
      ))}
    </Box>
  );
};

/**
 * 단축키 도움말 다이얼로그 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {boolean} props.open - 다이얼로그 열림 상태
 * @param {Function} props.onClose - 다이얼로그 닫기 핸들러
 * @param {Array} props.shortcuts - 단축키 목록
 */
export const ShortcutsDialog = ({ open, onClose, shortcuts }) => {
  const { theme } = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const [searchTerm, setSearchTerm] = useState('');
  
  // 단축키 카테고리별 그룹화
  const groupedShortcuts = shortcuts.reduce((acc, shortcut) => {
    const { category } = shortcut;
    if (!acc[category]) {
      acc[category] = [];
    }
    acc[category].push(shortcut);
    return acc;
  }, {});
  
  // 검색어에 따른 필터링
  const filteredGroups = Object.entries(groupedShortcuts).reduce((acc, [category, shortcuts]) => {
    const filtered = shortcuts.filter(shortcut => 
      shortcut.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
      formatKeyCombination(shortcut).toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    if (filtered.length > 0) {
      acc[category] = filtered;
    }
    
    return acc;
  }, {});
  
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
        <Box sx={{ display: 'flex', alignItems: 'center' }}>
          <KeyboardIcon sx={{ mr: 1 }} />
          <Typography variant="h6">키보드 단축키</Typography>
        </Box>
        <IconButton edge="end" onClick={onClose} aria-label="close">
          <CloseIcon />
        </IconButton>
      </DialogTitle>
      
      <Box sx={{ 
        p: 2, 
        display: 'flex', 
        alignItems: 'center',
        borderBottom: `1px solid ${theme.palette.divider}`,
      }}>
        <SearchIcon sx={{ color: 'text.secondary', mr: 1 }} />
        <input
          type="text"
          placeholder="단축키 검색..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          style={{
            border: 'none',
            outline: 'none',
            width: '100%',
            padding: '8px',
            backgroundColor: 'transparent',
            color: theme.palette.text.primary,
            fontSize: '1rem',
          }}
        />
      </Box>
      
      <DialogContent sx={{ p: 0 }}>
        {Object.keys(filteredGroups).length === 0 ? (
          <Box sx={{ p: 4, textAlign: 'center' }}>
            <Typography variant="body1" color="text.secondary">
              검색 결과가 없습니다.
            </Typography>
          </Box>
        ) : (
          Object.entries(filteredGroups).map(([category, shortcuts]) => (
            <Box key={category} sx={{ mb: 3, p: 2 }}>
              <Typography variant="subtitle1" sx={{ mb: 1, fontWeight: 'bold' }}>
                {category}
              </Typography>
              <Divider sx={{ mb: 2 }} />
              <List disablePadding>
                {shortcuts.map((shortcut, index) => (
                  <ListItem 
                    key={index} 
                    sx={{ 
                      py: 1,
                      px: 2,
                      borderRadius: 1,
                      '&:hover': {
                        backgroundColor: 'action.hover',
                      }
                    }}
                  >
                    <ListItemText 
                      primary={shortcut.description} 
                      primaryTypographyProps={{ variant: 'body2' }}
                    />
                    <ShortcutKeys shortcut={shortcut} />
                  </ListItem>
                ))}
              </List>
            </Box>
          ))
        )}
      </DialogContent>
      
      <DialogActions sx={{ 
        borderTop: `1px solid ${theme.palette.divider}`,
        p: 2,
        justifyContent: 'space-between'
      }}>
        <Button 
          startIcon={<SettingsIcon />} 
          onClick={() => {
            // 단축키 설정 페이지로 이동 또는 설정 다이얼로그 표시
            onClose();
          }}
        >
          단축키 설정
        </Button>
        <Button onClick={onClose} variant="contained">
          닫기
        </Button>
      </DialogActions>
    </Dialog>
  );
};

/**
 * 단축키 제공자 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 * @param {React.ReactNode} props.children - 자식 요소
 */
export const ShortcutsProvider = ({ children }) => {
  const [shortcuts, setShortcuts] = useState([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // 단축키 등록
  const registerShortcut = useCallback((shortcut) => {
    setShortcuts(prev => {
      // 이미 등록된 단축키인지 확인
      const existingIndex = prev.findIndex(s => 
        s.key === shortcut.key &&
        s.ctrlKey === shortcut.ctrlKey &&
        s.altKey === shortcut.altKey &&
        s.shiftKey === shortcut.shiftKey &&
        s.metaKey === shortcut.metaKey
      );
      
      if (existingIndex >= 0) {
        // 이미 등록된 단축키가 있으면 업데이트
        const updated = [...prev];
        updated[existingIndex] = shortcut;
        return updated;
      } else {
        // 새로운 단축키 추가
        return [...prev, shortcut];
      }
    });
  }, []);
  
  // 단축키 해제
  const unregisterShortcut = useCallback((shortcutId) => {
    setShortcuts(prev => prev.filter(s => s.id !== shortcutId));
  }, []);
  
  // 단축키 다이얼로그 표시
  const showShortcutsDialog = useCallback(() => {
    setIsDialogOpen(true);
  }, []);
  
  // 단축키 다이얼로그 닫기
  const hideShortcutsDialog = useCallback(() => {
    setIsDialogOpen(false);
  }, []);
  
  // 키 이벤트 핸들러
  const handleKeyDown = useCallback((event) => {
    // 입력 필드에서는 단축키 비활성화
    if (
      event.target.tagName === 'INPUT' || 
      event.target.tagName === 'TEXTAREA' || 
      event.target.isContentEditable
    ) {
      return;
    }
    
    // 단축키 다이얼로그 표시 (Shift + ?)
    if (event.shiftKey && event.key === '?') {
      event.preventDefault();
      showShortcutsDialog();
      return;
    }
    
    // 등록된 단축키 확인
    const matchedShortcut = shortcuts.find(shortcut => 
      shortcut.key === event.key &&
      shortcut.ctrlKey === event.ctrlKey &&
      shortcut.altKey === event.altKey &&
      shortcut.shiftKey === event.shiftKey &&
      shortcut.metaKey === event.metaKey
    );
    
    if (matchedShortcut) {
      event.preventDefault();
      matchedShortcut.handler(event);
    }
  }, [shortcuts, showShortcutsDialog]);
  
  // 키 이벤트 리스너 등록
  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => {
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [handleKeyDown]);
  
  // 기본 단축키 등록
  useEffect(() => {
    // 도움말 단축키 (Shift + ?)
    registerShortcut({
      id: 'show-shortcuts',
      key: '?',
      shiftKey: true,
      ctrlKey: false,
      altKey: false,
      metaKey: false,
      description: '단축키 도움말 표시',
      category: '일반',
      handler: showShortcutsDialog
    });
    
    // 검색 단축키 (Ctrl + K)
    registerShortcut({
      id: 'global-search',
      key: 'k',
      ctrlKey: true,
      altKey: false,
      shiftKey: false,
      metaKey: false,
      description: '전역 검색',
      category: '일반',
      handler: () => {
        // 검색 기능 구현
        console.log('Global search');
      }
    });
    
    // 새로고침 단축키 (Ctrl + R)
    registerShortcut({
      id: 'refresh-data',
      key: 'r',
      ctrlKey: true,
      altKey: false,
      shiftKey: false,
      metaKey: false,
      description: '데이터 새로고침',
      category: '일반',
      handler: () => {
        // 새로고침 기능 구현
        console.log('Refresh data');
      }
    });
    
    // 저장 단축키 (Ctrl + S)
    registerShortcut({
      id: 'save-changes',
      key: 's',
      ctrlKey: true,
      altKey: false,
      shiftKey: false,
      metaKey: false,
      description: '변경사항 저장',
      category: '편집',
      handler: () => {
        // 저장 기능 구현
        console.log('Save changes');
      }
    });
    
    // 새 항목 추가 단축키 (Ctrl + N)
    registerShortcut({
      id: 'new-item',
      key: 'n',
      ctrlKey: true,
      altKey: false,
      shiftKey: false,
      metaKey: false,
      description: '새 항목 추가',
      category: '편집',
      handler: () => {
        // 새 항목 추가 기능 구현
        console.log('Add new item');
      }
    });
    
    // 삭제 단축키 (Delete)
    registerShortcut({
      id: 'delete-item',
      key: 'Delete',
      ctrlKey: false,
      altKey: false,
      shiftKey: false,
      metaKey: false,
      description: '선택한 항목 삭제',
      category: '편집',
      handler: () => {
        // 삭제 기능 구현
        console.log('Delete selected item');
      }
    });
    
    // 대시보드로 이동 단축키 (Alt + D)
    registerShortcut({
      id: 'goto-dashboard',
      key: 'd',
      ctrlKey: false,
      altKey: true,
      shiftKey: false,
      metaKey: false,
      description: '대시보드로 이동',
      category: '탐색',
      handler: () => {
        // 대시보드로 이동 기능 구현
        console.log('Navigate to dashboard');
      }
    });
    
    // 제품 목록으로 이동 단축키 (Alt + P)
    registerShortcut({
      id: 'goto-products',
      key: 'p',
      ctrlKey: false,
      altKey: true,
      shiftKey: false,
      metaKey: false,
      description: '제품 목록으로 이동',
      category: '탐색',
      handler: () => {
        // 제품 목록으로 이동 기능 구현
        console.log('Navigate to products');
      }
    });
    
    // 소싱 추천으로 이동 단축키 (Alt + R)
    registerShortcut({
      id: 'goto-recommendations',
      key: 'r',
      ctrlKey: false,
      altKey: true,
      shiftKey: false,
      metaKey: false,
      description: '소싱 추천으로 이동',
      category: '탐색',
      handler: () => {
        // 소싱 추천으로 이동 기능 구현
        console.log('Navigate to recommendations');
      }
    });
    
    // 설정으로 이동 단축키 (Alt + S)
    registerShortcut({
      id: 'goto-settings',
      key: 's',
      ctrlKey: false,
      altKey: true,
      shiftKey: false,
      metaKey: false,
      description: '설정으로 이동',
      category: '탐색',
      handler: () => {
        // 설정으로 이동 기능 구현
        console.log('Navigate to settings');
      }
    });
  }, [registerShortcut, showShortcutsDialog]);
  
  return (
    <ShortcutsContext.Provider
      value={{
        registerShortcut,
        unregisterShortcut,
        showShortcutsDialog,
        shortcuts,
      }}
    >
      {children}
      <ShortcutsDialog
        open={isDialogOpen}
        onClose={hideShortcutsDialog}
        shortcuts={shortcuts}
      />
    </ShortcutsContext.Provider>
  );
};

/**
 * 단축키 버튼 컴포넌트
 * @param {Object} props - 컴포넌트 속성
 */
export const ShortcutsButton = (props) => {
  const { showShortcutsDialog } = useShortcuts();
  
  return (
    <Tooltip title="키보드 단축키 (Shift + ?)">
      <IconButton
        onClick={showShortcutsDialog}
        aria-label="키보드 단축키"
        {...props}
      >
        <KeyboardIcon />
      </IconButton>
    </Tooltip>
  );
};

/**
 * 단축키 훅
 * @param {Object} options - 단축키 옵션
 * @param {string} options.id - 단축키 ID
 * @param {string} options.key - 키
 * @param {boolean} options.ctrlKey - Ctrl 키 사용 여부
 * @param {boolean} options.altKey - Alt 키 사용 여부
 * @param {boolean} options.shiftKey - Shift 키 사용 여부
 * @param {boolean} options.metaKey - Meta 키 사용 여부
 * @param {string} options.description - 단축키 설명
 * @param {string} options.category - 단축키 카테고리
 * @param {Function} options.handler - 단축키 핸들러
 */
export const useShortcut = (options) => {
  const { registerShortcut, unregisterShortcut } = useShortcuts();
  
  useEffect(() => {
    registerShortcut(options);
    
    return () => {
      unregisterShortcut(options.id);
    };
  }, [registerShortcut, unregisterShortcut, options]);
};

export default ShortcutsProvider; 