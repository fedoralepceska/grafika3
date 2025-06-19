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

          <!-- Categories Grid -->
          <div v-if="categories.length === 0" class="empty-state">
            <div class="empty-icon">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
              </svg>
            </div>
            <h3>No Categories Yet</h3>
            <p>Create your first article category to get started</p>
            <button class="btn btn-primary" @click="goToCreate">Create Category</button>
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
              <div class="card-content">
                <div v-if="!category.articles || category.articles.length === 0" class="empty-category">
                  <p>No articles assigned</p>
                </div>
                <div v-else class="articles-preview">
                  <div class="articles-list">
                    <div 
                      v-for="article in category.articles.slice(0, 3)" 
                      :key="article.id"
                      class="article-preview"
                    >
                      <span class="article-name">{{ article.name }}</span>
                      <span class="article-code">{{ article.code }}</span>
                    </div>
                  </div>
                  <div v-if="category.articles.length > 3" class="more-articles">
                    +{{ category.articles.length - 3 }} more articles
                  </div>
                </div>
              </div>

              <!-- Card Actions -->
              <div class="card-actions">
                <button class="btn btn-secondary" @click="goToEdit(category.id)">
                  Manage Articles
                </button>
                <button class="btn btn-danger" @click="deleteCategory(category.id)">
                  Delete
                </button>
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
    categories: Array
  },
  methods: {
    goToCreate() {
      this.$inertia.visit('/article-categories/create');
    },
    goToEdit(id) {
      this.$inertia.visit(`/article-categories/${id}/edit`);
    },
    async deleteCategory(id) {
      
      try {
        const response = await axios.delete(`/article-categories/${id}`);
        
        const toast = useToast();
        toast.success('Category deleted successfully.');
        
        this.$inertia.reload();
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
  
  &.btn-primary {
    background-color: $blue;
    color: white;
    
    &:hover {
      background-color: cornflowerblue;
    }
  }
  
  &.btn-secondary {
    background-color: $gray;
    color: white;
    
    &:hover {
      background-color: darkgray;
    }
  }
  
  &.btn-danger {
    background-color: $red;
    color: white;
    
    &:hover {
      background-color: darkred;
    }
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
}

.category-card {
  background-color: $light-gray;
  border: 2px solid $gray;
  padding: 20px;
  transition: all 0.2s;
  
  &:hover {
    border-color: $blue;
    transform: translateY(-2px);
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid $gray;
    
    .category-info {
      display: flex;
      align-items: center;
      gap: 12px;
      
      .category-folder {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .category-details {
        .category-name {
          font-size: 18px;
          font-weight: 600;
          color: white;
          margin: 0 0 4px 0;
        }
        
        .category-type {
          font-size: 12px;
          color: $white;
          text-transform: capitalize;
        }
      }
    }
    
    .category-stats {
      .article-count {
        background-color: $blue;
        color: white;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 600;
      }
    }
  }
  
  .card-content {
    margin-bottom: 20px;
    min-height: 80px;
    
    .empty-category {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 80px;
      color: $white;
      font-style: italic;
      
      p {
        margin: 0;
      }
    }
    
    .articles-preview {
      .articles-list {
        margin-bottom: 8px;
        
        .article-preview {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 8px 0;
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          
          &:last-child {
            border-bottom: none;
          }
          
          .article-name {
            color: white;
            font-weight: 500;
            font-size: 14px;
          }
          
          .article-code {
            color: $white;
            font-size: 12px;
          }
        }
      }
      
      .more-articles {
        color: $white;
        font-size: 12px;
        text-align: center;
        padding: 8px;
        background-color: $dark-gray;
      }
    }
  }
  
  .card-actions {
    display: flex;
    gap: 12px;
    
    .btn {
      flex: 1;
      justify-content: center;
      padding: 10px 16px;
      font-size: 13px;
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
}
</style> 