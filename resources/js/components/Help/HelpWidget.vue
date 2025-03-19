<template>
  <div class="help-widget" :class="{ 'is-open': isOpen }">
    <button 
      class="help-widget__toggle" 
      @click="toggleWidget"
      :aria-label="isOpen ? '도움말 닫기' : '도움말 열기'"
    >
      <i class="fas fa-question-circle" v-if="!isOpen"></i>
      <i class="fas fa-times" v-else></i>
    </button>
    
    <div class="help-widget__content" v-if="isOpen">
      <div class="help-widget__header">
        <h3 class="help-widget__title">도움말 센터</h3>
        <div class="help-widget__search">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="질문을 입력하세요..." 
            @keyup.enter="search"
          />
          <button @click="search">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>
      
      <div class="help-widget__body">
        <div v-if="loading" class="help-widget__loading">
          <i class="fas fa-spinner fa-spin"></i>
          <span>검색 중...</span>
        </div>
        
        <div v-else-if="currentView === 'home'" class="help-widget__home">
          <h4>자주 묻는 질문</h4>
          <ul class="help-widget__faq-list">
            <li v-for="(faq, index) in popularFaqs" :key="index">
              <a href="#" @click.prevent="showArticle(faq.category_slug, faq.slug)">
                {{ faq.title }}
              </a>
            </li>
          </ul>
          
          <h4>도움말 카테고리</h4>
          <ul class="help-widget__category-list">
            <li v-for="category in categories" :key="category.slug">
              <a href="#" @click.prevent="showCategory(category.slug)">
                <i :class="category.icon || 'fas fa-folder'"></i>
                {{ category.name }}
              </a>
            </li>
          </ul>
          
          <div class="help-widget__contact">
            <p>원하는 정보를 찾을 수 없나요?</p>
            <a href="/contact" class="help-widget__contact-link">
              <i class="fas fa-envelope"></i> 문의하기
            </a>
          </div>
        </div>
        
        <div v-else-if="currentView === 'search'" class="help-widget__search-results">
          <button class="help-widget__back-button" @click="showHome">
            <i class="fas fa-arrow-left"></i> 뒤로
          </button>
          
          <h4>"{{ searchQuery }}" 검색 결과</h4>
          
          <div v-if="searchResults.length === 0" class="help-widget__no-results">
            <p>검색 결과가 없습니다.</p>
            <p>다른 키워드로 검색하거나 <a href="/help" target="_blank">도움말 센터</a>를 방문해 보세요.</p>
          </div>
          
          <ul v-else class="help-widget__result-list">
            <li v-for="(result, index) in searchResults" :key="index">
              <a href="#" @click.prevent="showArticle(result.category_slug, result.slug)">
                <h5>{{ result.title }}</h5>
                <p>{{ result.excerpt }}</p>
                <span class="help-widget__category-tag">{{ result.category }}</span>
              </a>
            </li>
          </ul>
        </div>
        
        <div v-else-if="currentView === 'category'" class="help-widget__category">
          <button class="help-widget__back-button" @click="showHome">
            <i class="fas fa-arrow-left"></i> 뒤로
          </button>
          
          <h4>{{ currentCategory.name }}</h4>
          <p>{{ currentCategory.description }}</p>
          
          <ul class="help-widget__article-list">
            <li v-for="article in categoryArticles" :key="article.slug">
              <a href="#" @click.prevent="showArticle(currentCategory.slug, article.slug)">
                {{ article.title }}
              </a>
            </li>
          </ul>
        </div>
        
        <div v-else-if="currentView === 'article'" class="help-widget__article">
          <button class="help-widget__back-button" @click="showCategory(currentCategory.slug)">
            <i class="fas fa-arrow-left"></i> 뒤로
          </button>
          
          <h4>{{ currentArticle.title }}</h4>
          
          <div class="help-widget__article-content" v-html="currentArticle.content"></div>
          
          <div class="help-widget__article-feedback">
            <p>이 정보가 도움이 되었나요?</p>
            <div class="help-widget__feedback-buttons">
              <button @click="sendFeedback(true)">
                <i class="fas fa-thumbs-up"></i> 예
              </button>
              <button @click="sendFeedback(false)">
                <i class="fas fa-thumbs-down"></i> 아니오
              </button>
            </div>
          </div>
          
          <div v-if="currentArticle.related_articles && currentArticle.related_articles.length > 0" class="help-widget__related">
            <h5>관련 항목</h5>
            <ul>
              <li v-for="related in currentArticle.related_articles" :key="related.slug">
                <a href="#" @click.prevent="showArticle(currentCategory.slug, related.slug)">
                  {{ related.title }}
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="help-widget__footer">
        <a href="/help" target="_blank" class="help-widget__full-link">
          <i class="fas fa-external-link-alt"></i> 도움말 센터 방문하기
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HelpWidget',
  
  data() {
    return {
      isOpen: false,
      loading: false,
      searchQuery: '',
      currentView: 'home',
      categories: [],
      popularFaqs: [],
      searchResults: [],
      currentCategory: null,
      categoryArticles: [],
      currentArticle: null
    };
  },
  
  mounted() {
    this.fetchInitialData();
  },
  
  methods: {
    toggleWidget() {
      this.isOpen = !this.isOpen;
      
      if (this.isOpen && this.categories.length === 0) {
        this.fetchInitialData();
      }
    },
    
    async fetchInitialData() {
      this.loading = true;
      
      try {
        const response = await axios.get('/api/help/initial-data');
        this.categories = response.data.categories;
        this.popularFaqs = response.data.popular_articles;
      } catch (error) {
        console.error('도움말 데이터를 가져오는 중 오류가 발생했습니다:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async search() {
      if (!this.searchQuery.trim()) {
        return;
      }
      
      this.loading = true;
      this.currentView = 'search';
      
      try {
        const response = await axios.get('/api/help/search', {
          params: { q: this.searchQuery }
        });
        this.searchResults = response.data.results;
      } catch (error) {
        console.error('검색 중 오류가 발생했습니다:', error);
        this.searchResults = [];
      } finally {
        this.loading = false;
      }
    },
    
    async showCategory(categorySlug) {
      this.loading = true;
      this.currentView = 'category';
      
      try {
        const response = await axios.get(`/api/help/category/${categorySlug}`);
        this.currentCategory = response.data.category;
        this.categoryArticles = response.data.articles;
      } catch (error) {
        console.error('카테고리 데이터를 가져오는 중 오류가 발생했습니다:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async showArticle(categorySlug, articleSlug) {
      this.loading = true;
      this.currentView = 'article';
      
      try {
        const response = await axios.get(`/api/help/article/${categorySlug}/${articleSlug}`);
        this.currentCategory = response.data.category;
        this.currentArticle = response.data.article;
      } catch (error) {
        console.error('도움말 항목을 가져오는 중 오류가 발생했습니다:', error);
      } finally {
        this.loading = false;
      }
    },
    
    showHome() {
      this.currentView = 'home';
      this.searchQuery = '';
      this.searchResults = [];
    },
    
    async sendFeedback(isHelpful) {
      try {
        await axios.post('/api/help/feedback', {
          category_slug: this.currentCategory.slug,
          article_slug: this.currentArticle.slug,
          is_helpful: isHelpful
        });
        
        // 피드백 제출 후 간단한 알림 표시
        alert(isHelpful ? '피드백을 주셔서 감사합니다!' : '불편을 드려 죄송합니다. 더 나은 도움말을 제공하기 위해 노력하겠습니다.');
      } catch (error) {
        console.error('피드백을 보내는 중 오류가 발생했습니다:', error);
      }
    }
  }
};
</script>

<style scoped>
.help-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  font-family: 'Noto Sans KR', sans-serif;
}

.help-widget__toggle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: #4a6cf7;
  color: white;
  border: none;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  transition: background-color 0.3s;
}

.help-widget__toggle:hover {
  background-color: #3a5ce5;
}

.help-widget__content {
  position: absolute;
  bottom: 60px;
  right: 0;
  width: 350px;
  max-height: 500px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.help-widget__header {
  padding: 15px;
  background-color: #4a6cf7;
  color: white;
}

.help-widget__title {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: 500;
}

.help-widget__search {
  display: flex;
  background-color: white;
  border-radius: 4px;
  overflow: hidden;
}

.help-widget__search input {
  flex: 1;
  padding: 8px 12px;
  border: none;
  outline: none;
  font-size: 14px;
}

.help-widget__search button {
  background-color: #f5f5f5;
  border: none;
  padding: 0 12px;
  cursor: pointer;
  color: #555;
}

.help-widget__body {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
}

.help-widget__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px 0;
  color: #666;
}

.help-widget__loading i {
  font-size: 24px;
  margin-bottom: 10px;
  color: #4a6cf7;
}

.help-widget__home h4,
.help-widget__category h4,
.help-widget__search-results h4 {
  margin: 0 0 15px 0;
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.help-widget__faq-list,
.help-widget__category-list,
.help-widget__article-list,
.help-widget__result-list {
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
}

.help-widget__faq-list li,
.help-widget__category-list li,
.help-widget__article-list li {
  margin-bottom: 8px;
}

.help-widget__faq-list a,
.help-widget__category-list a,
.help-widget__article-list a {
  display: block;
  padding: 8px 10px;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.help-widget__faq-list a:hover,
.help-widget__category-list a:hover,
.help-widget__article-list a:hover {
  background-color: #f5f7ff;
}

.help-widget__category-list a i {
  margin-right: 8px;
  color: #4a6cf7;
}

.help-widget__contact {
  background-color: #f5f7ff;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
}

.help-widget__contact p {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #555;
}

.help-widget__contact-link {
  display: inline-block;
  padding: 6px 12px;
  background-color: #4a6cf7;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-size: 14px;
}

.help-widget__back-button {
  background: none;
  border: none;
  color: #4a6cf7;
  cursor: pointer;
  padding: 0;
  margin-bottom: 15px;
  font-size: 14px;
  display: flex;
  align-items: center;
}

.help-widget__back-button i {
  margin-right: 5px;
}

.help-widget__result-list li {
  margin-bottom: 15px;
}

.help-widget__result-list a {
  display: block;
  padding: 10px;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.help-widget__result-list a:hover {
  background-color: #f5f7ff;
}

.help-widget__result-list h5 {
  margin: 0 0 5px 0;
  font-size: 15px;
  font-weight: 500;
  color: #4a6cf7;
}

.help-widget__result-list p {
  margin: 0 0 5px 0;
  font-size: 13px;
  color: #666;
}

.help-widget__category-tag {
  display: inline-block;
  font-size: 12px;
  color: #777;
  background-color: #f0f0f0;
  padding: 2px 6px;
  border-radius: 3px;
}

.help-widget__article-content {
  font-size: 14px;
  line-height: 1.6;
  color: #333;
  margin-bottom: 20px;
}

.help-widget__article-content h1,
.help-widget__article-content h2,
.help-widget__article-content h3 {
  margin-top: 20px;
  margin-bottom: 10px;
  font-weight: 500;
}

.help-widget__article-content p {
  margin: 0 0 15px 0;
}

.help-widget__article-content ul,
.help-widget__article-content ol {
  margin: 0 0 15px 0;
  padding-left: 20px;
}

.help-widget__article-content a {
  color: #4a6cf7;
  text-decoration: none;
}

.help-widget__article-content a:hover {
  text-decoration: underline;
}

.help-widget__article-content code {
  background-color: #f5f5f5;
  padding: 2px 4px;
  border-radius: 3px;
  font-family: monospace;
  font-size: 13px;
}

.help-widget__article-feedback {
  background-color: #f5f7ff;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 15px;
}

.help-widget__article-feedback p {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #555;
  text-align: center;
}

.help-widget__feedback-buttons {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.help-widget__feedback-buttons button {
  background-color: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 5px 10px;
  cursor: pointer;
  font-size: 13px;
  display: flex;
  align-items: center;
  transition: background-color 0.2s;
}

.help-widget__feedback-buttons button:hover {
  background-color: #f0f0f0;
}

.help-widget__feedback-buttons button i {
  margin-right: 5px;
}

.help-widget__related h5 {
  margin: 0 0 10px 0;
  font-size: 15px;
  font-weight: 500;
}

.help-widget__related ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.help-widget__related li {
  margin-bottom: 5px;
}

.help-widget__related a {
  display: block;
  padding: 5px 0;
  color: #4a6cf7;
  text-decoration: none;
  font-size: 13px;
}

.help-widget__related a:hover {
  text-decoration: underline;
}

.help-widget__footer {
  padding: 12px 15px;
  background-color: #f5f5f5;
  text-align: center;
}

.help-widget__full-link {
  color: #4a6cf7;
  text-decoration: none;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
}

.help-widget__full-link i {
  margin-right: 5px;
  font-size: 12px;
}

.help-widget__full-link:hover {
  text-decoration: underline;
}

.help-widget__no-results {
  text-align: center;
  padding: 20px 0;
  color: #666;
}

.help-widget__no-results p {
  margin: 0 0 10px 0;
}

.help-widget__no-results a {
  color: #4a6cf7;
  text-decoration: none;
}

.help-widget__no-results a:hover {
  text-decoration: underline;
}
</style> 