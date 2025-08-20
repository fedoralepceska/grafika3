<template>
  <MainLayout>
    <div class="pl-7 pr-7">
      <Header title="Article Categories" subtitle="Manage article categories" icon="Materials.png" link="/article-categories" />
      <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
          
          <!-- Header Section -->
          <div class="header-section">
            <div class="header-info">
              <h2 class="sub-title">Article Categories</h2>
              <p class="subtitle-description">Organize your articles by format type</p>
            </div>
            <button class="btn btn-primary" @click="goToCreate">
              <span class="btn-icon">+</span>
              Create Category
            </button>
          </div>

          <!-- Search and Filter Section -->
          <div class="search-filter-section">
            <div class="search-box">
              <div class="search-input-wrapper">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="m21 21-4.35-4.35"/>
                </svg>
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Search by category name..."
                  class="search-input"
                  @input="debounceSearch"
                />
              </div>
            </div>
            
            <div class="filter-controls">
              <select v-model="selectedSize" class="filter-select" @change="applyFilters">
                <option value="">All Sizes</option>
                <option value="large">Large Material</option>
                <option value="small">Small Material</option>
              </select>
            </div>
          </div>

          <!-- Categories Grid -->
          <div v-if="categories.length === 0" class="empty-state">
            <div class="empty-icon">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
              </svg>
            </div>
            <h3>No Categories Found</h3>
            <p>{{ searchQuery || selectedSize ? 'Try adjusting your search or filters' : 'Create your first article category to get started' }}</p>
            <button v-if="!searchQuery && !selectedSize" class="btn btn-primary" @click="goToCreate">Create Category</button>
          </div>

          <div v-else class="categories-grid">
            <div 
              v-for="category in categories" 
              :key="category.id" 
              class="category-card"
            >
              <!-- Card Header -->
              <div class="card-header">
                <div class="category-info">
                  <div class="category-folder">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                  </div>
                  <div class="category-details">
                    <h3 class="category-name">{{ category.name }}</h3>
                    <span class="category-type">{{ category.type }} format</span>
                  </div>
                </div>
                <div class="category-stats">
                  <span class="article-count">
                    {{ category.articles ? category.articles.length : 0 }} articles
                  </span>
                </div>
              </div>

              <!-- Card Content -->
              <div class="card-content ">
                <div v-if="!category.articles || category.articles.length === 0" class="flex justify-center items-center space-x-2">
                  <p class="empty-category">No articles assigned</p>
                  <button class="btn btn-secondary flex-1" @click="goToEdit(category.id)">Edit</button>
                  <button class="btn btn-danger flex-1" @click="deleteCategory(category.id)">Delete</button>
                </div>
                
                <div v-else class="articles-preview flex ">
                
                  <button class="btn btn-primary btn-view-articles" @click="showAllArticles(category)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    View
                  </button>
                  <button class="btn btn-secondary" @click="goToEdit(category.id)">
                  Edit
                </button>
                <button class="btn btn-danger" @click="deleteCategory(category.id)">
                  Delete
                </button>

                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="pagination">
            <button 
              class="pagination-btn" 
              :disabled="pagination.current_page === 1"
              @click="goToPage(pagination.current_page - 1)"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15,18 9,12 15,6"/>
              </svg>
              Previous
            </button>
            
            <div class="page-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                class="page-number"
                :class="{ active: page === pagination.current_page }"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              class="pagination-btn" 
              :disabled="pagination.current_page === pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
            >
              Next
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9,18 15,12 9,6"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- All Articles Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ selectedCategory?.name }} - All Articles</h3>
            <button class="modal-close" @click="closeModal">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div v-if="selectedCategory?.articles?.length === 0" class="no-articles">
              <p>No articles in this category</p>
            </div>
            <div v-else class="articles-list-modal">
              <div 
                v-for="article in selectedCategory?.articles" 
                :key="article.id"
                class="article-item-modal"
              >
                <div class="article-info">
                  <span class="article-name-modal">{{ article.name }}</span>
                  <span class="article-code-modal">Article Code: {{ article.code }}</span>
                </div>
                <div class="article-actions">
                  <button class="btn btn-sm btn-secondary" @click="viewArticle(article)">
                    View
                  </button>
                </div>
              </div>
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
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';

export default {
  components: { MainLayout, Header, Link },
  props: {
    categories: Array,
    pagination: Object,
    filters: Object
  },
  data() {
    return {
      searchQuery: '',
      selectedSize: '',
      showModal: false,
      selectedCategory: null,
      searchTimeout: null
    };
  },
  computed: {
    visiblePages() {
      const pages = [];
      const maxVisible = 5;
      const currentPage = this.pagination.current_page;
      const lastPage = this.pagination.last_page;
      
      if (lastPage <= maxVisible) {
        for (let i = 1; i <= lastPage; i++) {
          pages.push(i);
        }
      } else {
        let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let end = Math.min(lastPage, start + maxVisible - 1);
        
        if (end - start + 1 < maxVisible) {
          start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
          pages.push(i);
        }
      }
      
      return pages;
    }
  },
  mounted() {
    // Initialize filters from props
    this.searchQuery = this.filters.search || '';
    this.selectedSize = this.filters.size || '';
  },
  methods: {
    goToCreate() {
      this.$inertia.visit('/article-categories/create');
    },
    goToEdit(id) {
      this.$inertia.visit(`/article-categories/${id}/edit`);
    },
    goToPage(page) {
      this.$inertia.visit('/article-categories', {
        data: {
          page: page,
          search: this.searchQuery,
          size: this.selectedSize
        },
        preserveState: true,
        replace: true
      });
    },
    debounceSearch() {
      // Clear existing timeout
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      // Set new timeout for search
      this.searchTimeout = setTimeout(() => {
        this.applyFilters();
      }, 500);
    },
    applyFilters() {
      this.$inertia.visit('/article-categories', {
        data: {
          search: this.searchQuery,
          size: this.selectedSize,
          page: 1 // Reset to first page when filtering
        },
        preserveState: true,
        replace: true
      });
    },
    showAllArticles(category) {
      this.selectedCategory = category;
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.selectedCategory = null;
    },
    viewArticle(article) {
      // Close the modal first
      this.closeModal();
      // Navigate to the article detail page
      this.$inertia.visit(`/articles/${article.id}/view?from=categories`);
    },
    async deleteCategory(id) {
      try {
        const response = await axios.delete(`/article-categories/${id}`);
        
        const toast = useToast();
        toast.success('Category deleted successfully.');
        
        // Reload with current filters
        this.$inertia.visit('/article-categories', {
          data: {
            search: this.searchQuery,
            size: this.selectedSize,
            page: this.pagination.current_page
          },
          preserveState: true,
          replace: true
        });
      } catch (error) {
        const toast = useToast();
        if (error.response && error.response.data && error.response.data.message) {
          toast.error(error.response.data.message);
        } else {
          toast.error('Failed to delete category. Please try again.');
        }
      }
    }
  }
};
</script>

<style scoped lang="scss">
.dark-gray {
  background-color: $dark-gray;
}

.light-gray {
  background-color: $light-gray;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid $gray;
  
  .header-info {
    .sub-title {
      font-size: 24px;
      font-weight: bold;
      color: white;
      margin: 0 0 4px 0;
    }
    
    .subtitle-description {
      color: $white;
      font-size: 14px;
      margin: 0;
    }
  }
}

.search-filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 20px;
  
  .search-box {
    flex: 1;
    max-width: 400px;
    
    .search-input-wrapper {
      position: relative;
      
      .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: $gray;
        z-index: 1;
      }
      
      .search-input {
        width: 100%;
        padding: 12px 12px 12px 40px;
        border: 2px solid $gray;
        border-radius: 8px;
        background-color: $dark-gray;
        color: white;
        font-size: 14px;
        transition: all 0.2s;
        
        &:focus {
          outline: none;
          border-color: $blue;
          box-shadow: 0 0 0 3px rgba(100, 149, 237, 0.1);
        }
        
        &::placeholder {
          color: $gray;
        }
      }
    }
  }
  
  .filter-controls {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .filter-select {
      padding: 10px 16px;
      border: 2px solid $gray;
      border-radius: 6px;
      background-color: $dark-gray;
      color: white;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s;
      
      &:focus {
        outline: none;
        border-color: $blue;
      }
      
      option {
        background-color: $dark-gray;
        color: white;
      }
    }
    
    .results-info {
      color: $white;
      font-size: 14px;
      white-space: nowrap;
    }
  }
}

.btn {
  padding: 12px 20px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 6px;
  
  &.btn-primary {
    background-color: $blue;
    color: white;
    
    &:hover {
      background-color: cornflowerblue;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(100, 149, 237, 0.3);
    }
  }
  
  &.btn-secondary {
    background-color: $gray;
    color: white;
    
    &:hover {
      background-color: darkgray;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(128, 128, 128, 0.3);
    }
  }
  
  &.btn-danger {
    background-color: $red;
    color: white;
    
    &:hover {
      background-color: darkred;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
    }
  }
  
  &.btn-sm {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .btn-icon {
    font-size: 16px;
    font-weight: bold;
  }
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: $white;
  
  .empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
  }
  
  h3 {
    font-size: 20px;
    margin: 0 0 8px 0;
    color: white;
  }
  
  p {
    margin: 0 0 24px 0;
    color: $white;
  }
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.category-card {
  background: $dark-gray;
  border: 2px solid $gray;
  border-radius: 12px;
  padding: 10px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, $blue, cornflowerblue);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }
  
  &:hover {
    border-color: $blue;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    
    &::before {
      transform: scaleX(1);
    }
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    
    .category-info {
      display: flex;
      align-items: center;
      gap: 16px;
      
      .category-folder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, $blue, cornflowerblue);
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(100, 149, 237, 0.3);
      }
      
      .category-details {
        .category-name {
          font-size: 20px;
          font-weight: 700;
          color: white;
          margin: 0 0 6px 0;
        }
        
        .category-type {
          font-size: 13px;
          color: $white;
          text-transform: capitalize;
          background-color: rgba(255, 255, 255, 0.1);
          padding: 4px 8px;
          border-radius: 12px;
        }
      }
    }
    
    .category-stats {
      .article-count {
        background: linear-gradient(135deg, $blue, cornflowerblue);
        color: white;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(100, 149, 237, 0.3);
      }
    }
  }
  
  .card-content {
    padding: 10px;
    justify-content: space-between;
    gap: 12px;
    
    .empty-category {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
     
      color: $white;
      font-style: italic;
      background-color: rgba(255, 255, 255, 0.05);
      border-radius: 8px;
      
      p {
        margin: 0;
      }
    }
    
    .articles-preview {
      
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      
      .btn {
        flex: 1;
        justify-content: center;
        min-width: 0;
      }
      
      .articles-info {
        text-align: center;
        color: $white;
        
        .articles-count {
          font-size: 18px;
          font-weight: 600;
        }
        
        .articles-description {
          font-size: 13px;
          color: $gray;
        }
      }
      
      .btn-view-articles {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        font-size: 14px;
        border-radius: 8px;
        background-color: $blue;
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        
        &:hover {
          background-color: cornflowerblue;
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(100, 149, 237, 0.3);
        }
      }
    }
  }
  
  .card-actions {
    display: flex;
    gap: 12px;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    
    .btn {
      flex: 1;
      justify-content: center;
      padding: 12px 16px;
      font-size: 13px;
      border-radius: 8px;
    }
  }
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid $gray;
  
  .pagination-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid $gray;
    background-color: transparent;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
    
    &:hover:not(:disabled) {
      border-color: $blue;
      background-color: rgba(100, 149, 237, 0.1);
    }
    
    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }
  
  .page-numbers {
    display: flex;
    gap: 8px;
    
    .page-number {
      width: 40px;
      height: 40px;
      border: 2px solid $gray;
      background-color: transparent;
      color: white;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      
      &:hover {
        border-color: $blue;
        background-color: rgba(100, 149, 237, 0.1);
      }
      
      &.active {
        background-color: $blue;
        border-color: $blue;
        color: white;
      }
    }
  }
}

// Modal Styles
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background-color: $dark-gray;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 80vh;
  overflow: hidden;
  border: 2px solid $gray;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid $gray;
  
  h3 {
    margin: 0;
    color: white;
    font-size: 20px;
    font-weight: 600;
  }
  
  .modal-close {
    background: none;
    border: none;
    color: $gray;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s;
    
    &:hover {
      color: white;
      background-color: rgba(255, 255, 255, 0.1);
    }
  }
}

.modal-body {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
  
  .no-articles {
    text-align: center;
    color: $white;
    padding: 40px 20px;
  }
  
  .articles-list-modal {
    .article-item-modal {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px;
      margin-bottom: 12px;
      background-color: rgba(255, 255, 255, 0.05);
      border-radius: 8px;
      transition: all 0.2s;
      
      &:hover {
        background-color: rgba(255, 255, 255, 0.1);
      }
      
      .article-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
        
        .article-name-modal {
          color: white;
          font-weight: 500;
          font-size: 14px;
        }
        
        .article-code-modal {
          color: $white;
          font-size: 12px;
          background-color: rgba(255, 255, 255, 0.1);
          padding: 2px 6px;
          border-radius: 4px;
          display: inline-block;
        }
      }
      
      .article-actions {
        .btn {
          padding: 6px 12px;
          font-size: 12px;
        }
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .categories-grid {
    grid-template-columns: 1fr;
  }
  
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .search-filter-section {
    flex-direction: column;
    align-items: stretch;
    
    .search-box {
      max-width: none;
    }
    
    .filter-controls {
      justify-content: space-between;
    }
  }
  
  .pagination {
    flex-direction: column;
    gap: 16px;
  }
  
  .modal-content {
    margin: 20px;
    max-height: 90vh;
  }
}
</style> 