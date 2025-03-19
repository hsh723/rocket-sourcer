<template>
  <div class="onboarding-tour" v-if="isActive">
    <div class="onboarding-overlay" v-if="currentStep && currentStep.overlay"></div>
    
    <div 
      v-if="currentStep" 
      class="onboarding-step" 
      :class="[`onboarding-step--${currentStep.position || 'center'}`]"
      :style="stepStyle"
    >
      <div class="onboarding-step__header">
        <h3 class="onboarding-step__title">{{ currentStep.title }}</h3>
        <button class="onboarding-step__close" @click="endTour">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="onboarding-step__content">
        <p v-html="currentStep.content"></p>
        
        <div v-if="currentStep.image" class="onboarding-step__image">
          <img :src="currentStep.image" :alt="currentStep.title">
        </div>
      </div>
      
      <div class="onboarding-step__footer">
        <div class="onboarding-step__progress">
          <span>{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
        </div>
        
        <div class="onboarding-step__actions">
          <button 
            v-if="currentStepIndex > 0" 
            class="onboarding-step__button onboarding-step__button--secondary" 
            @click="prevStep"
          >
            이전
          </button>
          
          <button 
            v-if="currentStepIndex < steps.length - 1" 
            class="onboarding-step__button onboarding-step__button--primary" 
            @click="nextStep"
          >
            다음
          </button>
          
          <button 
            v-else 
            class="onboarding-step__button onboarding-step__button--primary" 
            @click="completeTour"
          >
            완료
          </button>
        </div>
      </div>
    </div>
    
    <div 
      v-if="currentStep && currentStep.highlightSelector" 
      class="onboarding-highlight"
      :style="highlightStyle"
    ></div>
  </div>
</template>

<script>
export default {
  name: 'OnboardingTour',
  
  props: {
    tourId: {
      type: String,
      required: true
    },
    autoStart: {
      type: Boolean,
      default: false
    },
    steps: {
      type: Array,
      required: true,
      validator: (steps) => {
        return steps.every(step => {
          return typeof step.title === 'string' && 
                 typeof step.content === 'string';
        });
      }
    }
  },
  
  data() {
    return {
      isActive: false,
      currentStepIndex: 0,
      highlightElement: null,
      highlightRect: null,
      stepPosition: { top: 0, left: 0 }
    };
  },
  
  computed: {
    currentStep() {
      return this.steps[this.currentStepIndex] || null;
    },
    
    highlightStyle() {
      if (!this.highlightRect) {
        return { display: 'none' };
      }
      
      return {
        top: `${this.highlightRect.top}px`,
        left: `${this.highlightRect.left}px`,
        width: `${this.highlightRect.width}px`,
        height: `${this.highlightRect.height}px`
      };
    },
    
    stepStyle() {
      if (!this.currentStep) {
        return {};
      }
      
      if (this.currentStep.position === 'auto' && this.highlightRect) {
        return this.calculateAutoPosition();
      }
      
      if (this.stepPosition.top !== 0 || this.stepPosition.left !== 0) {
        return {
          top: `${this.stepPosition.top}px`,
          left: `${this.stepPosition.left}px`
        };
      }
      
      return {};
    }
  },
  
  watch: {
    currentStepIndex() {
      this.updateHighlightElement();
      this.updateStepPosition();
    }
  },
  
  mounted() {
    if (this.autoStart && !this.hasCompletedTour()) {
      this.startTour();
    }
    
    window.addEventListener('resize', this.handleResize);
    window.addEventListener('keydown', this.handleKeyDown);
  },
  
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize);
    window.removeEventListener('keydown', this.handleKeyDown);
  },
  
  methods: {
    startTour() {
      this.isActive = true;
      this.currentStepIndex = 0;
      this.$nextTick(() => {
        this.updateHighlightElement();
        this.updateStepPosition();
      });
      this.$emit('start');
    },
    
    endTour() {
      this.isActive = false;
      this.highlightElement = null;
      this.highlightRect = null;
      this.$emit('end');
    },
    
    completeTour() {
      this.markTourAsCompleted();
      this.endTour();
      this.$emit('complete');
    },
    
    nextStep() {
      if (this.currentStepIndex < this.steps.length - 1) {
        this.currentStepIndex++;
        this.$emit('step', this.currentStepIndex);
      }
    },
    
    prevStep() {
      if (this.currentStepIndex > 0) {
        this.currentStepIndex--;
        this.$emit('step', this.currentStepIndex);
      }
    },
    
    updateHighlightElement() {
      this.highlightElement = null;
      this.highlightRect = null;
      
      if (!this.currentStep || !this.currentStep.highlightSelector) {
        return;
      }
      
      this.$nextTick(() => {
        try {
          const element = document.querySelector(this.currentStep.highlightSelector);
          
          if (element) {
            this.highlightElement = element;
            this.highlightRect = element.getBoundingClientRect();
            
            // 요소가 보이도록 스크롤
            if (this.currentStep.scrollIntoView !== false) {
              element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
              });
            }
          }
        } catch (error) {
          console.error('온보딩 하이라이트 요소를 찾을 수 없습니다:', error);
        }
      });
    },
    
    updateStepPosition() {
      if (!this.currentStep || !this.currentStep.position || this.currentStep.position === 'center') {
        this.stepPosition = { top: 0, left: 0 };
        return;
      }
      
      if (this.currentStep.position === 'auto' && this.highlightRect) {
        const position = this.calculateAutoPosition();
        this.stepPosition = {
          top: parseInt(position.top),
          left: parseInt(position.left)
        };
        return;
      }
      
      if (typeof this.currentStep.position === 'object') {
        this.stepPosition = {
          top: this.currentStep.position.top || 0,
          left: this.currentStep.position.left || 0
        };
        return;
      }
    },
    
    calculateAutoPosition() {
      if (!this.highlightRect) {
        return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
      }
      
      const windowHeight = window.innerHeight;
      const windowWidth = window.innerWidth;
      const stepWidth = 320; // 대략적인 단계 너비
      const stepHeight = 200; // 대략적인 단계 높이
      const margin = 20; // 여백
      
      // 기본 위치: 요소 아래
      let top = this.highlightRect.bottom + margin;
      let left = this.highlightRect.left + (this.highlightRect.width / 2) - (stepWidth / 2);
      let position = 'bottom';
      
      // 화면 아래에 공간이 부족한 경우 위에 배치
      if (top + stepHeight > windowHeight) {
        top = this.highlightRect.top - stepHeight - margin;
        position = 'top';
        
        // 위에도 공간이 부족한 경우 오른쪽에 배치
        if (top < 0) {
          top = this.highlightRect.top + (this.highlightRect.height / 2) - (stepHeight / 2);
          left = this.highlightRect.right + margin;
          position = 'right';
          
          // 오른쪽에도 공간이 부족한 경우 왼쪽에 배치
          if (left + stepWidth > windowWidth) {
            left = this.highlightRect.left - stepWidth - margin;
            position = 'left';
            
            // 모든 방향에 공간이 부족한 경우 중앙에 배치
            if (left < 0) {
              top = windowHeight / 2 - stepHeight / 2;
              left = windowWidth / 2 - stepWidth / 2;
              position = 'center';
            }
          }
        }
      }
      
      // 왼쪽 경계 확인
      if (left < margin) {
        left = margin;
      }
      
      // 오른쪽 경계 확인
      if (left + stepWidth > windowWidth - margin) {
        left = windowWidth - stepWidth - margin;
      }
      
      return { top: `${top}px`, left: `${left}px`, 'data-position': position };
    },
    
    handleResize() {
      if (this.isActive) {
        this.updateHighlightElement();
        this.updateStepPosition();
      }
    },
    
    handleKeyDown(event) {
      if (!this.isActive) {
        return;
      }
      
      switch (event.key) {
        case 'Escape':
          this.endTour();
          break;
        case 'ArrowRight':
        case 'Enter':
          this.nextStep();
          break;
        case 'ArrowLeft':
          this.prevStep();
          break;
      }
    },
    
    hasCompletedTour() {
      const completedTours = JSON.parse(localStorage.getItem('completedOnboardingTours') || '{}');
      return completedTours[this.tourId] === true;
    },
    
    markTourAsCompleted() {
      const completedTours = JSON.parse(localStorage.getItem('completedOnboardingTours') || '{}');
      completedTours[this.tourId] = true;
      localStorage.setItem('completedOnboardingTours', JSON.stringify(completedTours));
    }
  }
};
</script>

<style scoped>
.onboarding-tour {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
  pointer-events: none;
}

.onboarding-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  pointer-events: auto;
}

.onboarding-highlight {
  position: absolute;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
  border-radius: 4px;
  z-index: 1;
  pointer-events: none;
}

.onboarding-step {
  position: absolute;
  width: 320px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
  z-index: 2;
  pointer-events: auto;
}

.onboarding-step--center {
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.onboarding-step--top {
  bottom: calc(100% - 80px);
}

.onboarding-step--bottom {
  top: calc(100% + 20px);
}

.onboarding-step--left {
  right: calc(100% + 20px);
}

.onboarding-step--right {
  left: calc(100% + 20px);
}

.onboarding-step__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 15px 10px;
  border-bottom: 1px solid #f0f0f0;
}

.onboarding-step__title {
  margin: 0;
  font-size: 18px;
  font-weight: 500;
  color: #333;
}

.onboarding-step__close {
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  font-size: 16px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.onboarding-step__close:hover {
  background-color: #f0f0f0;
  color: #666;
}

.onboarding-step__content {
  padding: 15px;
}

.onboarding-step__content p {
  margin: 0 0 15px;
  font-size: 14px;
  line-height: 1.5;
  color: #555;
}

.onboarding-step__content p:last-child {
  margin-bottom: 0;
}

.onboarding-step__image {
  margin-top: 10px;
  border-radius: 4px;
  overflow: hidden;
}

.onboarding-step__image img {
  display: block;
  max-width: 100%;
  height: auto;
}

.onboarding-step__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px 15px;
  border-top: 1px solid #f0f0f0;
}

.onboarding-step__progress {
  font-size: 13px;
  color: #999;
}

.onboarding-step__actions {
  display: flex;
  gap: 10px;
}

.onboarding-step__button {
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: background-color 0.2s;
}

.onboarding-step__button--primary {
  background-color: #4a6cf7;
  color: white;
}

.onboarding-step__button--primary:hover {
  background-color: #3a5ce5;
}

.onboarding-step__button--secondary {
  background-color: #f0f0f0;
  color: #555;
}

.onboarding-step__button--secondary:hover {
  background-color: #e0e0e0;
}
</style> 