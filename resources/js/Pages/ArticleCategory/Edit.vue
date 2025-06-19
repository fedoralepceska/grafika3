<template>
  <MainLayout>
    <div class="pl-7 pr-7">
      <Header title="Edit Article Category" subtitle="Manage category articles" icon="Materials.png" link="/article-categories" />
      <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
          
          <h2 class="sub-title mb-6">Manage Articles for: {{ category.name }}</h2>
          
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
                  <h3>{{ category.name }}</h3>
                  <span class="folder-type">{{ category.type }} format</span>
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
                <h4>Available Articles ({{ category.type }} format only)</h4>
                
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
            <button type="button" class="btn btn-secondary" @click="goBack">Cancel</button>
            <button type="button" class="btn btn-primary" @click="updateCategory">
              Update Category
            </button>
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
    category: Object,
    articles: Array
  },
  data() {
    return {
      form: {
        name: this.category.name || '',
        icon: this.category.icon || '',
        type: this.category.type || '',
        article_ids: this.category.articles ? this.category.articles.map(a => a.id) : []
      },
      searchQuery: '',
      filteredArticles: []
    }
  },
  computed: {
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
    filterArticles() {
      let filtered = [...this.articles];

      // Only show articles of the same format type as the category
      if (this.category.type) {
        const categoryTypeMap = {
          'small': '1',
          'large': '2'
        };
        const allowedFormatType = categoryTypeMap[this.category.type];
        
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
      
      const expectedFormatType = categoryTypeMap[this.category.type];
      
      if (expectedFormatType && article.format_type != expectedFormatType) {
        const toast = useToast();
        toast.error(`Cannot add ${this.getFormatLabel(article.format_type)} format article to ${this.category.type} format category`);
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
    async updateCategory() {
      try {
        router.put(`/article-categories/${this.category.id}`, this.form, {
          onSuccess: () => {
            const toast = useToast();
            toast.success('Category updated successfully!');
          },
          onError: (errors) => {
            const toast = useToast();
            console.error('Validation errors:', errors);
            toast.error('Failed to update category. Please check your input.');
          }
        });
      } catch (error) {
        console.error('Category update error:', error);
        const toast = useToast();
        toast.error('Failed to update category. Please try again.');
      }
    },
    goBack() {
      this.$inertia.visit('/article-categories');
    }
  }
};
</script>

<style scoped lang="scss">
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