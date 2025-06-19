<template>
  <MainLayout>
    <div class="pl-7 pr-7">
      <Header title="Create Article Category" subtitle="Add a new article category" icon="Materials.png" link="/article-categories" />
      <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
          
          <!-- Step Progress Indicator -->
          <div class="step-progress mb-6">
            <div class="flex items-center justify-center space-x-8">
              <div class="step-item" :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }">
                <div class="step-circle">1</div>
                <div class="step-label">Category Details</div>
              </div>
              <div class="step-connector" :class="{ 'completed': currentStep > 1 }"></div>
              <div class="step-item" :class="{ 'active': currentStep === 2 }">
                <div class="step-circle">2</div>
                <div class="step-label">Assign Articles</div>
              </div>
            </div>
          </div>

          <!-- Step 1: Category Creation -->
          <div v-if="currentStep === 1" class="step-content">
            <h2 class="sub-title mb-6">Step 1: Category Details</h2>
            
            <div class="category-form">
              <div class="form-group">
                <label for="name">Category Name</label>
                <input 
                  type="text" 
                  id="name" 
                  v-model="form.name" 
                  class="form-input"
                  placeholder="Enter category name"
                  required
                >
              </div>

              <div class="form-group">
                <label for="icon">Icon (optional)</label>
                <input 
                  type="text" 
                  id="icon" 
                  v-model="form.icon" 
                  class="form-input"
                  placeholder="Icon filename (e.g., category-icon.png)"
                >
              </div>

              <div class="form-group">
                <label for="type">Category Type</label>
                <select v-model="form.type" class="form-input" required>
                  <option value="">Select category type</option>
                  <option value="large">Large Format</option>
                  <option value="small">Small Format</option>
                </select>
              </div>
            </div>

            <div class="step-actions">
              <button type="button" class="btn btn-secondary" @click="goBack">Cancel</button>
              <button type="button" class="btn btn-primary" @click="nextStep" :disabled="!canProceedToStep2">
                Next: Assign Articles
              </button>
            </div>
          </div>

          <!-- Step 2: Article Assignment -->
          <div v-if="currentStep === 2" class="step-content">
            <h2 class="sub-title mb-6">Step 2: Assign Articles</h2>
            
            <div class="article-assignment-container">
              <!-- Left Side: Category Folder -->
              <div class="category-folder">
                <div class="folder-header">
                  <div class="folder-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                  </div>
                  <div class="folder-info">
                    <h3>{{ form.name }}</h3>
                    <span class="folder-type">{{ form.type }} format</span>
                    <span class="article-count">{{ form.article_ids.length }} articles</span>
                  </div>
                </div>
                
                <div class="folder-content">
                  <div v-if="form.article_ids.length === 0" class="empty-folder">
                    <p>No articles assigned yet</p>
                    <p class="text-sm">Select articles from the right panel</p>
                  </div>
                  
                  <div v-else class="assigned-articles">
                    <div 
                      v-for="article in assignedArticles" 
                      :key="article.id"
                      class="assigned-article"
                    >
                      <div class="article-info">
                        <span class="article-name">{{ article.name }}</span>
                        <span class="article-code">{{ article.code }}</span>
                      </div>
                      <button 
                        @click="removeArticle(article.id)"
                        class="remove-btn"
                        title="Remove from category"
                      >
                        ×
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Side: Article Selection -->
              <div class="article-selection">
                <div class="selection-header">
                  <h4>Available Articles ({{ form.type }} format only)</h4>
                  
                  <!-- Search -->
                  <div class="search-box">
                    <input 
                      type="text" 
                      v-model="searchQuery"
                      placeholder="Search articles..."
                      class="search-input"
                      @input="filterArticles"
                    >
                  </div>
                </div>

                <div class="articles-list">
                  <div v-if="filteredArticles.length === 0" class="no-articles">
                    <p>No articles found</p>
                  </div>
                  
                  <div 
                    v-for="article in filteredArticles" 
                    :key="article.id"
                    class="article-item"
                    :class="{ 'selected': form.article_ids.includes(article.id) }"
                    @click="toggleArticle(article.id)"
                  >
                    <div class="article-details">
                      <span class="article-name">{{ article.name }}</span>
                      <span class="article-code">Code: {{ article.code }}</span>
                      <span class="article-type">{{ getFormatLabel(article.format_type) }}</span>
                    </div>
                    <div class="article-actions">
                      <button 
                        v-if="!form.article_ids.includes(article.id)"
                        @click.stop="addArticle(article.id)"
                        class="add-btn"
                      >
                        Add
                      </button>
                      <button 
                        v-else
                        @click.stop="removeArticle(article.id)"
                        class="remove-btn"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="step-actions">
              <button type="button" class="btn btn-secondary" @click="previousStep">
                Back: Category Details
              </button>
              <button type="button" class="btn btn-primary" @click="createCategory">
                Create Category
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from 'vue-toastification';
import { router } from '@inertiajs/vue3';

export default {
  components: { MainLayout, Header },
  props: {
    articles: Array
  },
  data() {
    return {
      currentStep: 1,
      form: {
        name: '',
        icon: '',
        type: '',
        article_ids: []
      },
      searchQuery: '',
      filteredArticles: []
    }
  },
  computed: {
    canProceedToStep2() {
      return this.form.name.trim() && this.form.type;
    },
    assignedArticles() {
      return this.articles.filter(article => 
        this.form.article_ids.includes(article.id)
      );
    }
  },
  mounted() {
    this.filterArticles();
  },
  methods: {
    nextStep() {
      if (this.canProceedToStep2) {
        this.currentStep = 2;
        this.filterArticles();
      }
    },
    previousStep() {
      this.currentStep = 1;
    },
    filterArticles() {
      let filtered = [...this.articles];

      // Only show articles of the same format type as the category
      if (this.form.type) {
        const categoryTypeMap = {
          'small': '1',
          'large': '2'
        };
        const allowedFormatType = categoryTypeMap[this.form.type];
        
        if (allowedFormatType) {
          filtered = filtered.filter(article => 
            article.format_type == allowedFormatType
          );
        }
      }

      // Filter by search query
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(article => 
          article.name.toLowerCase().includes(query) ||
          article.code.toLowerCase().includes(query)
        );
      }

      this.filteredArticles = filtered;
    },
    toggleArticle(articleId) {
      if (this.form.article_ids.includes(articleId)) {
        this.removeArticle(articleId);
      } else {
        this.addArticle(articleId);
      }
    },
    addArticle(articleId) {
      const article = this.articles.find(a => a.id === articleId);
      if (!article) return;

      // Validation: Check if article format matches category type
      const categoryTypeMap = {
        'small': '1',
        'large': '2'
      };
      
      const expectedFormatType = categoryTypeMap[this.form.type];
      
      if (expectedFormatType && article.format_type != expectedFormatType) {
        const toast = useToast();
        toast.error(`Cannot add ${this.getFormatLabel(article.format_type)} format article to ${this.form.type} format category`);
        return;
      }

      if (!this.form.article_ids.includes(articleId)) {
        this.form.article_ids.push(articleId);
      }
    },
    removeArticle(articleId) {
      this.form.article_ids = this.form.article_ids.filter(id => id !== articleId);
    },
    getFormatLabel(formatType) {
      const labels = {
        '1': 'Small',
        '2': 'Large',
        '3': 'Other'
      };
      return labels[formatType] || 'Unknown';
    },
    async createCategory() {
      try {
        router.post('/article-categories', this.form, {
          onSuccess: () => {
            const toast = useToast();
            toast.success('Category created successfully!');
          },
          onError: (errors) => {
            const toast = useToast();
            console.error('Validation errors:', errors);
            toast.error('Failed to create category. Please check your input.');
          }
        });
      } catch (error) {
        console.error('Category creation error:', error);
        const toast = useToast();
        toast.error('Failed to create category. Please try again.');
      }
    },
    goBack() {
      this.$inertia.visit('/article-categories');
    }
  }
};
</script>

<style scoped lang="scss">
.step-progress {
  .step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    &.active .step-circle {
      background-color: $blue;
      color: white;
    }
    
    &.completed .step-circle {
      background-color: $green;
      color: white;
    }
  }
  
  .step-circle {
    width: 40px;
    height: 40px;
    background-color: $gray;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
  }
  
  .step-label {
    font-size: 14px;
    color: $white;
  }
  
  .step-connector {
    width: 100px;
    height: 2px;
    background-color: $gray;
    margin-top: 20px;
    
    &.completed {
      background-color: $green;
    }
  }
}

.step-content {
  min-height: 500px;
}

.category-form {
  max-width: 500px;
  margin: 0 auto;
  
  .form-group {
    margin-bottom: 24px;
    
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: white;
    }
    
    .form-input {
      width: 100%;
      padding: 12px;
      border: 1px solid $gray;
      font-size: 16px;
      color: black;
      background-color: white;
      
      &:focus {
        outline: none;
        border-color: $blue;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      }
    }
  }
}

.article-assignment-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  min-height: 500px;
}

.category-folder {
  background-color: $light-gray;
  padding: 20px;
  border: 2px solid $gray;
  
  .folder-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid $gray;
    
    .folder-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 16px;
    }
    
    .folder-info {
      h3 {
        margin: 0 0 4px 0;
        font-size: 18px;
        color: white;
      }
      
      .folder-type {
        display: block;
        font-size: 14px;
        color: $white;
        margin-bottom: 4px;
      }
      
      .article-count {
        display: inline-block;
        background-color: $blue;
        color: white;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 600;
      }
    }
  }
  
  .empty-folder {
    text-align: center;
    padding: 40px 20px;
    color: $white;
    
    p {
      margin: 8px 0;
    }
  }
  
  .assigned-articles {
    .assigned-article {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      background-color: $dark-gray;
      margin-bottom: 8px;
      
      .article-info {
        .article-name {
          display: block;
          color: white;
          font-weight: 500;
        }
        
        .article-code {
          display: block;
          color: $white;
          font-size: 12px;
        }
      }
      
      .remove-btn {
        background-color: $red;
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        
        &:hover {
          background-color: darkred;
        }
      }
    }
  }
}

.article-selection {
  background-color: $light-gray;
  padding: 20px;
  border: 2px solid $gray;
  
  .selection-header {
    margin-bottom: 20px;
    
    h4 {
      margin: 0 0 16px 0;
      color: white;
      font-size: 18px;
    }
    
    .search-box {
      .search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid $gray;
        color: black;
        background-color: white;
        
        &:focus {
          outline: none;
          border-color: $blue;
        }
      }
    }
  }
  
  .articles-list {
    max-height: 400px;
    overflow-y: auto;
    
    .no-articles {
      text-align: center;
      padding: 40px 20px;
      color: $white;
    }
    
    .article-item {
      display: flex;
      border: 1px solid $dark-gray;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: all 0.2s;
      
      &:hover {
        background-color: $dark-gray;
      }
      
      &.selected {
        background-color: rgba(59, 130, 246, 0.1);
        border-color: $blue;
      }
      
      .article-details {
        .article-name {
          display: block;
          color: white;
          font-weight: 500;
          margin-bottom: 4px;
        }
        
        .article-code {
          display: block;
          color: $white;
          font-size: 12px;
          margin-bottom: 2px;
        }
        
        .article-type {
          display: inline-block;
          background-color: $gray;
          color: white;
          padding: 2px 6px;
          font-size: 10px;
        }
      }
      
      .article-actions {
        .add-btn {
          background-color: $green;
          color: white;
          border: none;
          padding: 6px 12px;
          cursor: pointer;
          font-size: 12px;
          
          &:hover {
            background-color: darkgreen;
          }
        }
        
        .remove-btn {
          background-color: $red;
          color: white;
          border: none;
          padding: 6px 12px;
          cursor: pointer;
          font-size: 12px;
          
          &:hover {
            background-color: darkred;
          }
        }
      }
    }
  }
}

.step-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 12px;
}

.btn {
  padding: 12px 24px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  
  &.btn-primary {
    background-color: $blue;
    color: white;
    
    &:hover:not(:disabled) {
      background-color: cornflowerblue;
    }
    
    &:disabled {
      background-color: $gray;
      cursor: not-allowed;
    }
  }
  
  &.btn-secondary {
    background-color: $gray;
    color: white;
    
    &:hover {
      background-color: darkgray;
    }
  }
}

.dark-gray {
  background-color: $dark-gray;
}

.light-gray {
  background-color: $light-gray;
}

.sub-title {
  font-size: 20px;
  font-weight: bold;
  color: white;
}
</style> 