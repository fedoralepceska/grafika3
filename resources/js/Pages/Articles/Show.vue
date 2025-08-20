<template>
  <MainLayout>
    <div class="pl-7 pr-7">
      <div class="flex justify-between items-center">
        <Header title="Article Details" subtitle="View article information and analytics" icon="Materials.png" link="/articles" />
        <button class="btn btn-secondary" @click="goBack">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15,18 9,12 15,6"/>
          </svg>
          {{ backButtonText }}
        </button>
      </div>
      <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">

          <!-- Compact Article Header -->
          <div class="article-header-compact">
            <div class="article-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10,9 9,9 8,9"/>
              </svg>
            </div>
            <div class="article-title-compact">
              <h1>{{ article.name }}</h1>
              <div class="article-meta-compact">
                <span class="article-code">Article Code: {{ article.code }}</span>
                <span class="article-type">{{ article.type }}</span>
                <span class="article-price">MKD {{ formatCurrency(article.price_1, 2) }}</span>
              </div>
            </div>
          </div>

          <!-- Analytics Overview Cards -->
          <div class="analytics-overview">
            <div class="analytics-card" :class="{ 'low-stock': stockInfo?.is_low_stock }">
              <div class="analytics-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                  <polyline points="7.5,4.21 12,6.81 16.5,4.21"/>
                  <polyline points="7.5,19.79 7.5,14.6 3,12"/>
                  <polyline points="21,12 16.5,14.6 16.5,19.79"/>
                </svg>
              </div>
              <div class="analytics-data">
                <span class="analytics-value">{{ formatQuantity(stockInfo?.stock?.current_stock || 0) }}</span>
                <span class="analytics-label">Current Stock</span>
              </div>
            </div>

            <div class="analytics-card">
              <div class="analytics-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14,2 14,8 20,8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                  <polyline points="10,9 9,9 8,9"/>
                </svg>
              </div>
              <div class="analytics-data">
                <span class="analytics-value">{{ ordersCount }}</span>
                <span class="analytics-label">Total Orders</span>
              </div>
            </div>

            <div class="analytics-card">
              <div class="analytics-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                </svg>
              </div>
              <div class="analytics-data">
                <span class="analytics-value">{{ Math.round(monthlyUsage?.average_monthly_usage || 0) }}</span>
                <span class="analytics-label">Monthly Avg Usage</span>
              </div>
            </div>

            <div class="analytics-card">
              <div class="analytics-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="1" x2="12" y2="23"/>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
              </div>
              <div class="analytics-data">
                <span class="analytics-value">MKD {{ formatCurrency(ordersTotalValue, 2) }}</span>
                <span class="analytics-label">Total Value</span>
              </div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="tabs-container">
            <div class="tabs">
              <button 
                class="tab" 
                :class="{ active: activeTab === 'details' }"
                @click="activeTab = 'details'"
              >
                Details
              </button>
              <button 
                class="tab" 
                :class="{ active: activeTab === 'stock' }"
                @click="activeTab = 'stock'"
              >
                Stock & Warehouse
              </button>
              <button 
                class="tab" 
                :class="{ active: activeTab === 'orders' }"
                @click="activeTab = 'orders'"
              >
                Order Usage
              </button>
              <button 
                class="tab" 
                :class="{ active: activeTab === 'analytics' }"
                @click="activeTab = 'analytics'"
              >
                Usage Analytics
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Details Tab -->
            <div v-if="activeTab === 'details'" class="details-content">
              <div class="details-grid">
                <!-- Compact Basic Info -->
                <div class="detail-card">
                  <h4>Basic Information</h4>
                  <div class="detail-items">
                    <div class="detail-row">
                      <span class="detail-label">Code:</span>
                      <span>{{ article.code }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Type:</span>
                      <span>{{ article.type }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Tax Type:</span>
                      <span>{{ article.tax_type }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Barcode:</span>
                      <span>{{ article.barcode || 'N/A' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Dimensions -->
                <div class="detail-card">
                  <h4>Dimensions</h4>
                  <div class="detail-items">
                    <div class="detail-row">
                      <span class="detail-label">Size:</span>
                      <span>{{ article.width }}×{{ article.height }}×{{ article.length }} mm</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Weight:</span>
                      <span>{{ article.weight }} g</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Color:</span>
                      <span>{{ article.color || 'N/A' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Pricing -->
                <div class="detail-card">
                  <h4>Pricing</h4>
                  <div class="detail-items">
                    <div class="detail-row">
                      <span class="detail-label">Purchase:</span>
                      <span class="price">MKD {{ formatCurrency(article.purchase_price, 2) }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Factory:</span>
                      <span class="price">MKD {{ formatCurrency(article.factory_price, 2) }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Sale Price:</span>
                      <span class="price">MKD {{ formatCurrency(article.price_1, 2) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Categories -->
                <div class="detail-card">
                  <h4>Categories</h4>
                  <div v-if="article.categories && article.categories.length > 0" class="categories-compact">
                    <span 
                      v-for="category in article.categories" 
                      :key="category.id"
                      class="category-badge"
                    >
                      {{ category.name }}
                    </span>
                  </div>
                  <div v-else class="no-data">
                    <span>No categories assigned</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stock Tab -->
            <div v-if="activeTab === 'stock'" class="stock-content">
              <div v-if="stockInfo" class="stock-details">
                <div class="stock-card">
                  <h4>Current Inventory</h4>
                  <div class="stock-info">
                    <div class="stock-main">
                      <span class="stock-number" :class="{ 'low-stock': stockInfo.is_low_stock }">
                        {{ formatQuantity(stockInfo.stock.current_stock) }}
                      </span>
                      <span class="stock-unit">units</span>
                    </div>
                    <div class="stock-meta">
                      <p><strong>Material:</strong> {{ stockInfo.stock.material_name }}</p>
                      <p><strong>Location:</strong> {{ stockInfo.stock.warehouse_location }}</p>
                      <p><strong>Type:</strong> {{ stockInfo.stock.type.replace('_', ' ').toUpperCase() }}</p>
                      <div v-if="stockInfo.stock.dimensions" class="dimensions">
                        <strong>Dimensions:</strong> {{ stockInfo.stock.dimensions.width }}×{{ stockInfo.stock.dimensions.height }} mm
                      </div>
                    </div>
                  </div>
                  
                  <div v-if="stockInfo.is_low_stock" class="low-stock-warning">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                      <line x1="12" y1="9" x2="12" y2="13"/>
                      <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    Low stock warning - below {{ stockInfo.low_stock_threshold }} units
                  </div>
                </div>
              </div>
              <div v-else class="loading">
                Loading stock information...
              </div>
            </div>

            <!-- Orders Tab -->
            <div v-if="activeTab === 'orders'" class="orders-content">
              <div v-if="orderUsage" class="orders-details">
                <div class="orders-header">
                  <h4>Order Usage History</h4>
                  <div class="orders-summary">
                    <span class="summary-item">{{ ordersCount }} orders</span>
                    <span class="summary-item">{{ formatQuantity(ordersTotalQuantity) }} units used</span>
                    <span class="summary-item">MKD {{ formatCurrency(ordersTotalValue, 2) }} total value</span>
                  </div>
                </div>
                
                <div class="filters-section">
                  <div class="filters-row">
                    <div class="filter-group">
                      <label>Search:</label>
                      <input 
                        v-model="orderFilters.search" 
                        @input="debounceOrderSearch"
                        type="text" 
                        placeholder="Search invoices or clients..."
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>Client:</label>
                      <div class="client-dropdown">
                        <input 
                          v-model="clientSearchTerm"
                          @input="filterClients"
                          @focus="showClientDropdown = true"
                          @blur="setTimeout(() => showClientDropdown = false, 200)"
                          type="text" 
                          placeholder="Search clients..."
                          class="filter-input"
                        />
                        <div v-if="showClientDropdown && filteredClients.length > 0" class="client-dropdown-menu">
                          <div 
                            v-for="client in filteredClients" 
                            :key="client.id"
                            @click="selectClient(client)"
                            class="client-option"
                            :title="client.name"
                          >
                            <span class="client-name-text">{{ client.name }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="filter-group">
                      <label>Start Date:</label>
                      <input 
                        v-model="orderFilters.startDate" 
                        @change="applyOrderFilters"
                        type="date" 
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>End Date:</label>
                      <input 
                        v-model="orderFilters.endDate" 
                        @change="applyOrderFilters"
                        type="date" 
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>Min Usage:</label>
                      <input 
                        v-model="orderFilters.minUsage" 
                        @change="applyOrderFilters"
                        type="number" 
                        step="0.01"
                        placeholder="Min units"
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>Max Usage:</label>
                      <input 
                        v-model="orderFilters.maxUsage" 
                        @change="applyOrderFilters"
                        type="number" 
                        step="0.01"
                        placeholder="Max units"
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>Min Value:</label>
                      <input 
                        v-model="orderFilters.minValue" 
                        @change="applyOrderFilters"
                        type="number" 
                        step="0.01"
                        placeholder="Min MKD"
                        class="filter-input"
                      />
                    </div>
                    <div class="filter-group">
                      <label>Max Value:</label>
                      <input 
                        v-model="orderFilters.maxValue" 
                        @change="applyOrderFilters"
                        type="number" 
                        step="0.01"
                        placeholder="Max MKD"
                        class="filter-input"
                      />
                    </div>
                  </div>
                </div>
                
                <div v-if="orderUsage?.invoices?.length > 0" class="orders-list">
                  <div 
                    v-for="order in paginatedOrders" 
                    :key="order.invoice_id"
                    class="order-item"
                  >
                    <div class="order-main">
                      <div class="order-info">
                        <h5>Order: {{ order.invoice_title }}</h5>
                        <p class="client-name">Client: {{ order.client_name }}</p>
                      </div>
                      <div class="order-stats">
                        <div class="stat">
                          <span class="stat-value">{{ formatQuantity(computeInvoiceQuantity(order)) }}</span>
                          <span class="stat-label">units</span>
                        </div>
                        <div class="stat">
                          <span class="stat-value">{{ order.jobs?.length || 0 }}</span>
                          <span class="stat-label">jobs</span>
                        </div>
                        <div class="stat">
                          <span class="stat-value">MKD {{ formatCurrency(computeInvoiceQuantity(order) * (Number(article?.price_1) || 0), 2) }}</span>
                          <span class="stat-label">value</span>
                        </div>
                      </div>
                    </div>
                    <div class="order-meta">
                      <span class="order-date">{{ formatDate(order.start_date) }}</span>
                      <span class="order-status" :class="statusClass(order.status)">{{ order.status }}</span>
                    </div>
                  </div>
                  
                  <!-- Pagination -->
                  <div class="pagination-container">
                    <div class="pagination-info">
                      Showing {{ paginationStart + 1 }}-{{ paginationEnd }} of {{ ordersCount }} orders
                    </div>
                    <div class="pagination-controls">
                      <button 
                        @click="currentPage = Math.max(1, currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="pagination-btn"
                        :class="{ disabled: currentPage === 1 }"
                      >
                        Previous
                      </button>
                      <div class="page-numbers">
                        <button 
                          v-for="page in visiblePages" 
                          :key="page"
                          @click="currentPage = page"
                          class="page-btn"
                          :class="{ active: page === currentPage }"
                        >
                          {{ page }}
                        </button>
                      </div>
                      <button 
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="pagination-btn"
                        :class="{ disabled: currentPage === totalPages }"
                      >
                        Next
                      </button>
                    </div>
                  </div>
                </div>
                <div v-else class="no-data">
                  <p>No orders found using this article</p>
                </div>
              </div>
              <div v-else class="loading">
                Loading order usage...
              </div>
            </div>

                         <!-- Analytics Tab -->
             <div v-if="activeTab === 'analytics'" class="analytics-content">
               <div v-if="monthlyUsage" class="analytics-details">
                 <div class="analytics-header">
                   <h4>Monthly Usage Analytics</h4>
                   <div class="analytics-summary">
                     <span class="summary-item">{{ monthlyUsage.period }} period</span>
                     <span class="summary-item">{{ Math.round(monthlyUsage.average_monthly_usage) }} avg/month</span>
                     <span class="summary-item">{{ monthlyUsage.total_quantity }} total units</span>
                   </div>
                 </div>
                 
                 <!-- Data Discrepancy Warning -->
                 <div v-if="hasActiveFilters && monthlyUsage.total_quantity !== monthlyUsage.monthly_usage?.reduce((sum, item) => sum + (item.quantity_used || 0), 0)" 
                      class="data-warning" 
                      style="background: rgba(255,193,7,0.1); border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin: 16px 0; color: #ffc107;">
                   <strong>⚠️ Data Discrepancy Detected:</strong> The total units shown ({{ monthlyUsage.total_quantity }}) 
                   doesn't match the sum of individual data points. This may indicate backend data inconsistencies 
                   or jobs without proper invoice relationships. Please verify with your administrator.
                 </div>
                
                                 <div class="filters-section">
                   <div class="analytics-filters-row">
                     <div class="filter-group">
                       <label>Start Date:</label>
                       <input 
                         v-model="analyticsFilters.startDate" 
                         @change="applyAnalyticsFilters"
                         type="date" 
                         class="filter-input"
                       />
                     </div>
                     <div class="filter-group">
                       <label>End Date:</label>
                       <input 
                         v-model="analyticsFilters.endDate" 
                         @change="applyAnalyticsFilters"
                         type="date" 
                         class="filter-input"
                       />
                     </div>
                     <div class="filter-group">
                       <label>Quick Month:</label>
                       <select 
                         v-model="analyticsFilters.quickMonth" 
                         @change="applyQuickMonthFilter"
                         class="filter-select"
                       >
                         <option value="">Select Month</option>
                         <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                           {{ month.label }}
                         </option>
                       </select>
                     </div>
                     <div class="filter-group">
                       <button @click="resetAnalyticsFilters" class="reset-btn">Reset to 12 Months</button>
                     </div>
                   </div>
                 </div>

                                 <div class="chart-container">
                   <h3>Usage Over Time</h3>
                   <p class="period-info">{{ monthlyUsage.period }}</p>
                   
                  <div class="chart" v-if="monthlyUsage.monthly_usage && monthlyUsage.monthly_usage.length > 0">
                                         <!-- Line Chart for Day-by-Day -->
                     <div v-if="monthlyUsage.group === 'day'" class="line-chart">
                       <svg :width="chartWidth" :height="chartHeight" class="chart-svg">
                         <defs>
                           <linearGradient id="lineGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                             <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.8" />
                             <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0.2" />
                           </linearGradient>
                         </defs>
                         
                         <!-- Grid lines -->
                         <g class="grid-lines">
                           <line 
                             x1="60" y1="60" 
                             :x2="chartWidth - 60" y2="60" 
                             stroke="rgba(255,255,255,0.1)" 
                             stroke-width="1"
                           />
                           <line 
                             x1="60" y1="120" 
                             :x2="chartWidth - 60" y2="120" 
                             stroke="rgba(255,255,255,0.1)" 
                             stroke-width="1"
                           />
                           <line 
                             x1="60" y1="180" 
                             :x2="chartWidth - 60" y2="180" 
                             stroke="rgba(255,255,255,0.1)" 
                             stroke-width="1"
                           />
                           <line 
                             x1="60" y1="240" 
                             :x2="chartWidth - 60" y2="240" 
                             stroke="rgba(255,255,255,0.1)" 
                             stroke-width="1"
                           />
                         </g>
                         
                         <!-- Area chart (background) -->
                         <path 
                           :d="areaChartPath" 
                           fill="url(#lineGradient)"
                         />
                         
                         <!-- Line chart -->
                         <path 
                           :d="lineChartPath" 
                           fill="none" 
                           stroke="#3b82f6" 
                           stroke-width="3"
                           stroke-linecap="round"
                           stroke-linejoin="round"
                         />
                         
                         <!-- Data points -->
                         <g v-for="(point, index) in lineChartPoints" :key="index">
                           <circle 
                             :cx="point.x" 
                             :cy="point.y" 
                             r="5" 
                             fill="#3b82f6"
                             stroke="white"
                             stroke-width="2"
                             class="chart-point"
                           />
                           
                           <!-- Value labels above points (only show for > 0) -->
                           <text 
                             v-if="point.quantity > 0"
                             :x="point.x" 
                             :y="point.y - 15" 
                             text-anchor="middle" 
                             class="chart-value-label"
                           >
                             {{ formatQuantity(point.quantity) }}
                           </text>
                           
                           <!-- Date labels below chart -->
                           <text 
                             :x="point.x" 
                             :y="chartHeight - 20" 
                             text-anchor="middle" 
                             class="chart-date-label"
                             v-if="index % 3 === 0 || index === lineChartPoints.length - 1"
                           >
                             {{ point.label }}
                           </text>
                         </g>
                       </svg>
                     </div>
                    
                    <!-- Bar Chart for Monthly -->
                    <div v-else class="bar-chart">
                      <div class="chart-bars">
                        <div 
                          v-for="item in monthlyUsage.monthly_usage" 
                          :key="item.period"
                          class="chart-bar"
                          :style="{ height: getBarHeight(item.quantity_used) + 'px' }"
                        >
                          <div class="bar-value">{{ formatQuantity(item.quantity_used) }}</div>
                        </div>
                      </div>
                      <div class="chart-labels">
                        <div 
                          v-for="item in monthlyUsage.monthly_usage" 
                          :key="item.period"
                          class="chart-label"
                        >
                          {{ item.label }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                                 <!-- Only show usage details when no filters are applied -->
                 <div v-if="!hasActiveFilters" class="usage-details">
                   <h4>Usage Details</h4>
                   <div class="usage-grid">
                     <div 
                       v-for="item in groupedUsageData" 
                       :key="item.period"
                       class="usage-item"
                     >
                       <div class="usage-period">
                         <span class="period-label">{{ item.period_label }}</span>
                         <span class="period-date">{{ item.date_range }}</span>
                       </div>
                       <div class="usage-stats">
                         <span class="usage-quantity">{{ formatQuantity(item.quantity_used) }} units</span>
                         <span class="usage-value">MKD {{ formatCurrency(item.value, 0) }}</span>
                       </div>
                     </div>
                   </div>
                 </div>
              </div>
              <div v-else class="loading">
                Loading analytics...
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
import axios from 'axios';

// Debounce utility function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

export default {
  components: { MainLayout, Header },
  props: {
    article: Object
  },
  data() {
    return {
      activeTab: 'details',
      stockInfo: null,
      orderUsage: null,
      monthlyUsage: null,
      loading: {
        stock: false,
        orders: false,
        analytics: false
      },
      orderFilters: {
        search: '',
        clientId: '',
        startDate: '',
        endDate: '',
        minUsage: '',
        maxUsage: '',
        minValue: '',
        maxValue: ''
      },
             analyticsFilters: {
         startDate: '',
         endDate: '',
         quickMonth: ''
       },
      clientSearchTerm: '',
      showClientDropdown: false,
      filteredClients: [],
      currentPage: 1,
      ordersPerPage: 10
    };
  },
  computed: {
    ordersCount() {
      const list = this.orderUsage?.invoices || [];
      return Array.isArray(list) ? list.length : 0;
    },
    backButtonText() {
      const params = new URLSearchParams(window.location.search);
      const from = params.get('from');
      if (from === 'small_materials') return 'Back to Small Materials';
      if (from === 'large_materials') return 'Back to Large Materials';
      if (from === 'categories') return 'Back to Categories';
      return 'Back to Categories';
    },
    ordersTotalQuantity() {
      const list = this.orderUsage?.invoices || [];
      if (!Array.isArray(list)) return 0;
      return list.reduce((acc, inv) => acc + this.computeInvoiceQuantity(inv), 0);
    },
    ordersTotalValue() {
      const unitPrice = Number(this.article?.price_1) || 0;
      return this.ordersTotalQuantity * unitPrice;
    },
    availableClients() {
      return this.orderUsage?.clients || [];
    },
    totalPages() {
      return Math.ceil(this.ordersCount / this.ordersPerPage);
    },
    paginationStart() {
      return (this.currentPage - 1) * this.ordersPerPage;
    },
    paginationEnd() {
      return Math.min(this.currentPage * this.ordersPerPage, this.ordersCount);
    },
    visiblePages() {
      const pages = [];
      const start = Math.max(1, this.currentPage - 2);
      const end = Math.min(this.totalPages, this.currentPage + 2);
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    },
    paginatedOrders() {
      const start = this.paginationStart;
      const end = this.paginationEnd;
      return this.orderUsage?.invoices?.slice(start, end) || [];
    },
         chartWidth() {
       return window.innerWidth > 1200 ? 1200 : Math.max(800, window.innerWidth - 100); // Responsive width
     },
    chartHeight() {
      return 300; // Default height for line chart
    },
         lineChartPoints() {
       if (!this.monthlyUsage || !this.monthlyUsage.monthly_usage) return [];
       const data = this.monthlyUsage.monthly_usage;
       const maxQuantity = Math.max(...data.map(item => item.quantity_used));
       const minQuantity = Math.min(...data.map(item => item.quantity_used));
       const quantityRange = maxQuantity - minQuantity;
       
       const padding = 60;
       const chartAreaWidth = this.chartWidth - (padding * 2);
       const chartAreaHeight = this.chartHeight - (padding * 2);
       
       return data.map((item, index) => {
         const x = padding + (index / (data.length - 1)) * chartAreaWidth;
         
         // Calculate y position with proper scaling and inverted coordinates (SVG y increases downward)
         let y;
         if (quantityRange === 0) {
           y = padding + chartAreaHeight / 2; // Center if all values are the same
         } else {
           const normalizedValue = (item.quantity_used - minQuantity) / quantityRange;
           y = padding + chartAreaHeight - (normalizedValue * chartAreaHeight);
         }
         
         return {
           x: Math.round(x),
           y: Math.round(y),
           label: item.label,
           quantity: item.quantity_used
         };
       });
     },
    lineChartPath() {
      if (!this.lineChartPoints || this.lineChartPoints.length < 2) return '';
      return this.lineChartPoints.map((p, i) => {
        if (i === 0) return `M${p.x},${p.y}`;
        return `L${p.x},${p.y}`;
      }).join('');
    },
              areaChartPath() {
        if (!this.lineChartPoints || this.lineChartPoints.length < 2) return '';
        const points = this.lineChartPoints.map(p => `L${p.x},${p.y}`).join(' ');
        const bottomY = this.chartHeight - 60; // Account for padding
        return `M${this.lineChartPoints[0].x},${bottomY} ${points} L${this.lineChartPoints[this.lineChartPoints.length - 1].x},${bottomY} Z`;
     },
     
           groupedUsageData() {
        if (!this.monthlyUsage || !this.monthlyUsage.monthly_usage) return [];
        
        const data = this.monthlyUsage.monthly_usage;
        const isDaily = this.monthlyUsage.group === 'day';
        
        // Debug: Log the data structure to understand what we're working with
        console.log('Monthly Usage Data:', {
          group: this.monthlyUsage.group,
          dataLength: data.length,
          sampleData: data.slice(0, 3),
          isDaily
        });
        
        // If daily data and more than 12 points, group into weeks
        if (isDaily && data.length > 12) {
          console.log('Grouping daily data into weeks...');
          return this.groupIntoWeeks(data);
        }
        
        // For monthly data or small daily datasets, return as is
        return data.map(item => ({
          period: item.period,
          period_label: isDaily ? 'Day' : 'Month',
          date_range: isDaily ? this.formatDate(item.label) : item.label,
          quantity_used: item.quantity_used,
          value: item.value
        }));
      },
      
             hasActiveFilters() {
         return this.analyticsFilters.startDate || this.analyticsFilters.endDate || this.analyticsFilters.quickMonth;
       },
       
       availableMonths() {
         const months = [];
         const currentDate = new Date();
         
         // Generate last 24 months for selection
         for (let i = 0; i < 24; i++) {
           const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
           // Build YYYY-MM using local time to avoid timezone shifting to previous month
           const y = date.getFullYear();
           const m = String(date.getMonth() + 1).padStart(2, '0');
           months.push({
             value: `${y}-${m}`,
             label: date.toLocaleDateString('en-US', {
               year: 'numeric',
               month: 'long'
             })
           });
         }
         
         return months;
       }
   },
        watch: {
       availableClients: {
         handler(newClients) {
           if (newClients && newClients.length > 0) {
             this.filteredClients = newClients;
           }
         },
         immediate: true
       },
       
       // Clear quick month when manual date filters are applied
       'analyticsFilters.startDate'() {
         if (this.analyticsFilters.startDate || this.analyticsFilters.endDate) {
           this.analyticsFilters.quickMonth = '';
         }
       },
       
       'analyticsFilters.endDate'() {
         if (this.analyticsFilters.startDate || this.analyticsFilters.endDate) {
           this.analyticsFilters.quickMonth = '';
         }
       }
     },
       async mounted() {
      // Load analytics data
      await this.loadAnalyticsData();
      
      // Add window resize listener for responsive chart
      window.addEventListener('resize', this.handleResize);
    },
    
    beforeUnmount() {
      // Clean up event listener
      window.removeEventListener('resize', this.handleResize);
    },
  methods: {
    statusClass(status) {
      if (!status) return '';
      const s = String(status).toLowerCase();
      if (s.includes('completed')) return 'completed';
      if (s.includes('in progress')) return 'in-progress';
      if (s.includes('not started')) return 'not-started-yet';
      return s.replace(/\s+/g, '-');
    },
    formatQuantity(value) {
      const num = Number(value || 0);
      return num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    },
    formatCurrency(value, decimals = 2) {
      const num = Number(value || 0);
      return num.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    },
    computeInvoiceQuantity(order) {
      const jobs = order?.jobs || [];
      let total = 0;
      for (const j of jobs) {
        if (j?.used_via === 'pivot') {
          total += Number(j.pivot_quantity || 0);
        } else if (['small_fk','large_fk','small_name','large_name'].includes(j?.used_via)) {
          total += Number(j.fallback_quantity || 0);
        }
      }
      return total;
    },
    async loadAnalyticsData() {
      try {
        // Load all analytics data in parallel
        const [stockResponse, ordersResponse, analyticsResponse] = await Promise.all([
          axios.get(`/articles/${this.article.id}/analytics/stock`),
          axios.get(`/articles/${this.article.id}/analytics/orders`),
          axios.get(`/articles/${this.article.id}/analytics/monthly`)
        ]);

        this.stockInfo = stockResponse.data;
        this.orderUsage = ordersResponse.data;
        this.monthlyUsage = analyticsResponse.data;
      } catch (error) {
        console.error('Error loading analytics data:', error);
        // Handle error gracefully - data will remain null and loading states will show
      }
    },

    getBarHeight(value) {
      if (!this.monthlyUsage || !this.monthlyUsage.monthly_usage) return 0;
      
      const maxValue = Math.max(...this.monthlyUsage.monthly_usage.map(m => m.quantity_used));
      if (maxValue === 0) return 0;
      
      // Return height as percentage, minimum 5% for visibility
      return Math.max(5, (value / maxValue) * 100);
    },

    goBack() {
      // Determine where to go back based on query param `from`
      const params = new URLSearchParams(window.location.search);
      const from = params.get('from');
      if (from === 'small_materials') {
        this.$inertia.visit('/materials/small');
        return;
      }
      if (from === 'large_materials') {
        this.$inertia.visit('/materials/large');
        return;
      }
      if (from === 'categories') {
        this.$inertia.visit('/article-categories');
        return;
      }
      // Default back to categories
      this.$inertia.visit('/article-categories');
    },
    
    editArticle() {
      this.$inertia.visit(`/articles/${this.article.id}/edit`);
    },
    
    manageCategories() {
      this.$inertia.visit('/article-categories');
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    async applyOrderFilters() {
      this.loading.orders = true;
      try {
        const params = {
          search: this.orderFilters.search,
          client_id: this.orderFilters.clientId,
          start_date: this.orderFilters.startDate,
          end_date: this.orderFilters.endDate,
          min_usage: this.orderFilters.minUsage,
          max_usage: this.orderFilters.maxUsage,
          min_value: this.orderFilters.minValue,
          max_value: this.orderFilters.maxValue
        };
        const response = await axios.get(`/articles/${this.article.id}/analytics/orders`, { params });
        this.orderUsage = response.data;
        this.currentPage = 1; // Reset pagination when filters change
      } catch (error) {
        console.error('Error applying order filters:', error);
      } finally {
        this.loading.orders = false;
      }
    },

         async applyAnalyticsFilters() {
       this.loading.analytics = true;
       try {
         const params = {
           start_date: this.analyticsFilters.startDate,
           end_date: this.analyticsFilters.endDate
         };
         const response = await axios.get(`/articles/${this.article.id}/analytics/monthly`, { params });
         this.monthlyUsage = response.data;
       } catch (error) {
         console.error('Error applying analytics filters:', error);
       } finally {
         this.loading.analytics = false;
       }
     },
     
           async applyQuickMonthFilter() {
        if (!this.analyticsFilters.quickMonth) return;
        
        this.loading.analytics = true;
        try {
          // Clear other filters when quick month is selected
          this.analyticsFilters.startDate = '';
          this.analyticsFilters.endDate = '';
          
          // Get the selected month and calculate start/end dates
          const [year, month] = this.analyticsFilters.quickMonth.split('-');
          const startDate = `${year}-${month}-01`;
          
          // Calculate the last day of the selected month correctly
          // parseInt(month) - 1 because months are 0-indexed in JavaScript Date constructor
          // Calculate the last day of the selected month correctly
          // parseInt(month) - 1 because months are 0-indexed in JavaScript Date constructor
          // Fix: parseInt(month) - 1 because months are 0-indexed in JavaScript Date constructor
          const lastDay = new Date(parseInt(year), parseInt(month), 0).getDate();
          const endDate = `${year}-${month}-${lastDay}`;
          
          console.log('Quick Month Filter:', {
            selected: this.analyticsFilters.quickMonth,
            startDate,
            endDate,
            year,
            month,
            lastDay
          });
          
          const params = {
            start_date: startDate,
            end_date: endDate
          };
          
          const response = await axios.get(`/articles/${this.article.id}/analytics/monthly`, { params });
          this.monthlyUsage = response.data;
        } catch (error) {
          console.error('Error applying quick month filter:', error);
        } finally {
          this.loading.analytics = false;
        }
      },

                   resetAnalyticsFilters() {
        this.analyticsFilters.startDate = '';
        this.analyticsFilters.endDate = '';
        this.analyticsFilters.quickMonth = '';
        this.applyAnalyticsFilters();
       },
     
     debounceOrderSearch: debounce(function() {
       this.applyOrderFilters();
     }, 300),

         filterClients() {
       const term = this.clientSearchTerm.toLowerCase();
       if (!this.availableClients || this.availableClients.length === 0) {
         this.filteredClients = [];
         return;
       }
       this.filteredClients = this.availableClients.filter(client => 
         client.name.toLowerCase().includes(term)
       );
     },

         selectClient(client) {
       this.orderFilters.clientId = client.id;
       this.clientSearchTerm = client.name;
       this.showClientDropdown = false;
       this.applyOrderFilters();
     },
     
           groupIntoWeeks(dailyData) {
        const weeks = [];
        let currentWeek = [];
        let weekStart = null;
        
        dailyData.forEach((day, index) => {
          // Parse the date from the label, handling different date formats
          let date;
          try {
            // Try to parse the date from the label
            if (day.label && typeof day.label === 'string') {
              date = new Date(day.label);
              // Check if date is valid
              if (isNaN(date.getTime())) {
                // If label is not a valid date, try to use the period or create a fallback
                date = new Date();
              }
            } else {
              date = new Date();
            }
          } catch (e) {
            date = new Date();
          }
          
          if (index === 0) {
            weekStart = date;
          }
          
          currentWeek.push(day);
          
          // Start new week on Sunday (0) or every 7 days
          if (date.getDay() === 0 || currentWeek.length === 7 || index === dailyData.length - 1) {
            const weekEnd = date;
            
            // Calculate weekly totals
            const weekTotal = currentWeek.reduce((sum, d) => sum + (d.quantity_used || 0), 0);
            const weekValue = currentWeek.reduce((sum, d) => sum + (d.value || 0), 0);
            
            // Format dates safely
            const startDate = weekStart ? this.formatDate(weekStart) : 'Unknown';
            const endDate = weekEnd ? this.formatDate(weekEnd) : 'Unknown';
            
            weeks.push({
              period: `week_${weeks.length + 1}`,
              period_label: 'Week',
              date_range: `${startDate} - ${endDate}`,
              quantity_used: weekTotal,
              value: weekValue
            });
            
            currentWeek = [];
            weekStart = null;
          }
        });
        
        return weeks;
      },
     
                formatDate(dateString) {
       if (!dateString) return 'Unknown';
       
       // If it's already a Date object, use it directly
       let date;
       if (dateString instanceof Date) {
         date = dateString;
       } else {
         try {
           date = new Date(dateString);
           // Check if date is valid
           if (isNaN(date.getTime())) {
             return 'Invalid Date';
           }
         } catch (e) {
           return 'Invalid Date';
         }
       }
       
       try {
         return date.toLocaleDateString('en-US', {
           month: 'short',
           day: 'numeric'
         });
       } catch (e) {
         return 'Format Error';
       }
     },
     
     handleResize() {
       // Force chart to recalculate dimensions
       this.$nextTick(() => {
         // Trigger reactivity update
         this.chartWidth = this.chartWidth;
       });
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

.back-section {
  margin-bottom: 20px;
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

  &.btn-sm {
    padding: 8px 12px;
    font-size: 12px;
  }
}

// Compact Header
.article-header-compact {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
    background: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  margin-bottom: 24px;
  
  .article-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, $blue, cornflowerblue);
    border-radius: 12px;
    padding: 8px;
  }
  
  .article-title-compact {
    flex: 1;
    margin-left: 16px;
    
    h1 {
      font-size: 24px;
      font-weight: 700;
      color: white;
      margin: 0 0 8px 0;
    }
    
    .article-meta-compact {
      display: flex;
      gap: 12px;
      align-items: center;
      
      .article-code {
        background: linear-gradient(135deg, $blue, cornflowerblue);
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
      }
      
      .article-type {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        text-transform: capitalize;
      }

      .article-price {
        color: #4ade80;
        font-weight: 600;
        font-size: 14px;
      }
    }
  }
}

// Analytics Overview
.analytics-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
  
  .analytics-card {
    background: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s;
    
    &:hover {
      border-color: rgba(100, 149, 237, 0.3);
      transform: translateY(-1px);
    }

    &.low-stock {
      border-color: #ef4444;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    }
    
    .analytics-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, $blue, cornflowerblue);
      border-radius: 12px;
      color: white;
    }
    
    .analytics-data {
      display: flex;
      flex-direction: column;
      
      .analytics-value {
        font-size: 24px;
        font-weight: 700;
        color: white;
        line-height: 1;
      }
      
      .analytics-label {
        font-size: 12px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
      }
    }
  }
}

// Tabs
.tabs-container {
  margin-bottom: 24px;
  
  .tabs {
    display: flex;
    gap: 4px;
    background: $dark-gray;
    border-radius: 8px;
    padding: 4px;
    
    .tab {
      flex: 1;
      padding: 12px 16px;
      border: none;
      background: transparent;
      color: whitesmoke;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      font-size: 14px;
      
      &.active {
        background: $blue;
        color: white;
      }
      
      &:hover:not(.active) {
        background: rgba(255, 255, 255, 0.05);
        color: white;
      }
    }
  }
}

// Tab Content
.tab-content {
  background: $dark-gray;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 24px;
}

// Details Tab
.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.detail-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 8px;
  padding: 16px;
  
  h4 {
    font-size: 16px;
    font-weight: 600;
    color: white;
    margin: 0 0 16px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .detail-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
    
    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      
      .detail-label {
        font-size: 12px;
        color: white;
        font-weight: 500;
      }
      
      span:not(.detail-label) {
        color: white;
        font-weight: 500;
        
        &.price {
          color: #4ade80;
          font-weight: 600;
        }
      }
    }
  }
  
  .categories-compact {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    
    .category-badge {
      background: rgba(100, 149, 237, 0.2);
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
  }
  
  .no-data {
    color: $gray;
    font-style: italic;
    text-align: center;
    padding: 20px;
  }
}

// Stock Tab
.stock-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 20px;
  
  h4 {
    font-size: 18px;
    font-weight: 600;
    color: white;
    margin: 0 0 20px 0;
  }
  
  .stock-info {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 20px;
    
    .stock-main {
      display: flex;
      align-items: baseline;
      gap: 8px;
      
      .stock-number {
        font-size: 48px;
        font-weight: 700;
        color: #4ade80;
        
        &.low-stock {
          color: #ef4444;
        }
      }
      
      .stock-unit {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
      }
    }
    
    .stock-meta {
      flex: 1;
      
      p {
        margin: 0 0 8px 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        font-weight: 100;
        
        strong {
          font-weight: 500;
          color: white;
        }
      }
      
      .dimensions {
        color: white;
        font-size: 14px;
        
        strong {
          color: $gray;
        }
      }
    }
  }
  
  .low-stock-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid #ef4444;
    border-radius: 8px;
    padding: 12px;
    color: #ef4444;
    font-size: 14px;
    font-weight: 500;
  }
}

// Orders Tab
.orders-header {
  margin-bottom: 20px;
  
  h4 {
    font-size: 18px;
    font-weight: 600;
    color: white;
    margin: 0 0 12px 0;
  }
  
  .orders-summary {
    display: flex;
    gap: 20px;
    
    .summary-item {
      font-size: 14px;
      color: white;
      font-weight: 500;
    }
  }
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  
  .order-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 16px;
    transition: all 0.2s;
    
    &:hover {
      border-color: rgba(100, 149, 237, 0.3);
    }
    
    .order-main {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
      
      .order-info {
        h5 {
          font-size: 16px;
          font-weight: 600;
          color: white;
          margin: 0 0 4px 0;
        }
        
        .client-name {
          font-size: 12px;
          color: white;
          font-weight: 500;
          margin: 0;
        }
      }
      
      .order-stats {
        display: flex;
        gap: 16px;
        
        .stat {
          display: flex;
          flex-direction: column;
          align-items: center;
          
          .stat-value {
            font-size: 16px;
            font-weight: 600;
            color: white;
          }
          
          .stat-label {
            font-size: 10px;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
          }
        }
      }
    }
    
    .order-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      
      .order-date {
        font-size: 12px;
        color: white;
        font-weight: 500;
      }
      
      .order-status {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        
        &.completed {
          background: rgba(34, 197, 94, 0.2);
          color: #22c55e;
        }
        
        &.in-progress {
          background: rgba(0, 115, 169, 0.2);
          color: #0073a9;
        }
        
        &.not-started-yet {
          background: rgba(251, 191, 36, 0.2);
          color: #fbbf24;
        }
      }
    }
  }
}

// Analytics Tab
.analytics-header {
  margin-bottom: 24px;
  
  h4 {
    font-size: 18px;
    font-weight: 600;
    color: white;
    margin: 0 0 12px 0;
  }
  
  .analytics-summary {
    display: flex;
    gap: 20px;
    
    .summary-item {
      font-size: 14px;
      color: white;
      font-weight: 500;
    }
  }
}

.chart-container {
  margin-bottom: 24px;
  
  .chart {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
    width: 100%;
    overflow-x: auto;
    
    .chart-bars {
      display: flex;
      align-items: flex-end;
      gap: 8px;
      height: 200px;
      margin-bottom: 20px;
      padding: 0 4px;
      
      .chart-bar {
        flex: 1;
        background: linear-gradient(to top, $blue, cornflowerblue);
        border-radius: 4px 4px 0 0;
        min-height: 4px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        
        &:hover {
          opacity: 0.8;
        }
        
        .bar-value {
          position: absolute;
          top: -25px;
          left: 50%;
          transform: translateX(-50%);
          font-size: 12px;
          color: white;
          font-weight: 600;
          white-space: nowrap;
        }
      }
    }
    
    .chart-labels {
      display: flex;
      gap: 8px;
      padding: 0 4px;
      
      .chart-label {
        flex: 1;
        text-align: center;
        font-size: 12px;
        color: white;
        font-weight: 500;
        padding: 8px 4px;
      }
    }
  }
}

.usage-details {
  margin-top: 24px;
  
  h4 {
    font-size: 18px;
    font-weight: 600;
    color: white;
    margin: 0 0 16px 0;
    text-align: center;
  }
  
  .usage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    
    .usage-item {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 16px;
      transition: all 0.2s;
      
      &:hover {
        border-color: rgba(100, 149, 237, 0.3);
        transform: translateY(-1px);
      }
      
      .usage-period {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 12px;
        
        .period-label {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.7);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
        }
        
        .period-date {
          font-size: 14px;
          color: white;
          font-weight: 600;
        }
      }
      
      .usage-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        .usage-quantity {
          font-size: 14px;
          color: white;
          font-weight: 500;
        }
        
        .usage-value {
          font-size: 14px;
          color: #4ade80;
          font-weight: 600;
        }
      }
    }
  }
}

.loading {
  text-align: center;
  color: $gray;
  padding: 40px;
  font-style: italic;
}

.no-data {
  text-align: center;
  color: $gray;
  padding: 40px;
  
  p {
    margin: 0;
  }
}

/* Filters Section */
.filters-section {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  align-items: end;
}

.analytics-filters-row {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-size: 14px;
  font-weight: 500;
  color: white;
}

.filter-input, .filter-select {
  padding: 10px 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  font-size: 14px;
  background: rgba(255, 255, 255, 0.05);
  color: white;
  transition: all 0.2s;
  cursor: pointer;
}

.filter-select {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px;
  padding-right: 40px;
}

.filter-select option {
  background: $dark-gray;
  color: white;
  padding: 8px 12px;
}

.filter-select:focus {
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
}

.filter-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.filter-input:focus, .filter-select:focus {
  outline: none;
  border-color: $blue;
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 3px rgba(100, 149, 237, 0.2);
  transform: translateY(-1px);
}

.filter-input:hover, .filter-select:hover {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.08);
}

/* Date input calendar icon styling */
.filter-input[type="date"] {
  position: relative;
}

.filter-input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.filter-input[type="date"]::-webkit-calendar-picker-indicator:hover {
  filter: invert(1) brightness(1.2);
  background: rgba(255, 255, 255, 0.1);
}

.filter-input[type="date"]::-webkit-calendar-picker-indicator:active {
  transform: scale(0.95);
}

/* Firefox date input styling */
.filter-input[type="date"]::-moz-calendar-picker-indicator {
  filter: invert(1);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.filter-input[type="date"]::-moz-calendar-picker-indicator:hover {
  filter: invert(1) brightness(1.2);
  background: rgba(255, 255, 255, 0.1);
}

/* Enhanced date input focus state */
.filter-input[type="date"]:focus {
  border-color: $blue;
  box-shadow: 0 0 0 3px rgba(100, 149, 237, 0.2);
}

/* Client Dropdown */
.client-dropdown {
  position: relative;
}

.client-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: $dark-gray;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 4px;
}

.client-option {
  padding: 10px 12px;
  cursor: pointer;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  transition: background-color 0.2s;
  
  .client-name-text {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
  }
}

.client-option:hover {
  background: rgba(100, 149, 237, 0.2);
}

.client-option:last-child {
  border-bottom: none;
}

/* Pagination */
.pagination-container {
  margin-top: 24px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.pagination-info {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-btn {
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.pagination-btn:hover:not(.disabled) {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
}

.pagination-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 4px;
}

.page-btn {
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
  min-width: 40px;
}

.page-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
}

.page-btn.active {
  background: $blue;
  border-color: $blue;
  color: white;
}

.date-separator {
  align-self: center;
  margin: 0 10px;
  color: rgba(255, 255, 255, 0.7);
  font-weight: 500;
}

.reset-btn {
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.reset-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Line Chart */
.line-chart {
  width: 100%;
  overflow-x: auto;
  display: flex;
  justify-content: center;
}

.chart-svg {
  width: 100%;
  max-width: 100%;
  height: auto;
  min-width: 600px; /* Ensure minimum readable width */
}

.chart-point {
  cursor: pointer;
  transition: r 0.2s;
}

.chart-point:hover {
  r: 6;
}

 .chart-label {
   font-size: 12px;
   fill: #6b7280;
   font-weight: 500;
 }
 
 .chart-value-label {
   font-size: 11px;
   fill: white;
   font-weight: 600;
   text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
 }
 
 .chart-date-label {
   font-size: 10px;
   fill: rgba(255, 255, 255, 0.7);
   font-weight: 500;
 }

/* Period Info */
.period-info {
  text-align: center;
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 20px;
  font-style: italic;
}

// Responsive
@media (max-width: 768px) {
  .article-header-compact {
    flex-direction: column;
    text-align: center;
    gap: 16px;
    
    .article-title-compact {
      margin-left: 0;
    }
  }
  
  .analytics-overview {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    
    .analytics-card {
      padding: 16px;
      
      .analytics-data .analytics-value {
        font-size: 20px;
      }
    }
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .chart-container .chart {
    .chart-bars {
      height: 150px;
    }
  }
  
     .usage-details .usage-grid {
     grid-template-columns: 1fr;
   }
  
  /* Filters and Pagination Responsive */
  .filters-row {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
  }
  
  .analytics-filters-row {
    flex-direction: column;
    gap: 16px;
  }
  
  .filter-group {
    min-width: auto;
  }
  
  .pagination-container {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .pagination-controls {
    justify-content: center;
  }
  
  .page-numbers {
    flex-wrap: wrap;
    justify-content: center;
  }
}

.button-container{
  display: flex;
  justify-content: flex-end;
}
</style>

