<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">收支管理</h1>
    
    <!-- 筛选与搜索区域 -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-3">
          <select 
            v-model="filters.type" 
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">所有类型</option>
            <option value="income">收入</option>
            <option value="expense">支出</option>
          </select>
          
          <select 
            v-model="filters.account_id" 
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">所有账户</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
              {{ account.name }}
            </option>
          </select>
          
          <select 
            v-model="filters.category_id" 
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">所有分类</option>
            <optgroup label="收入分类">
              <option v-for="category in incomeCategories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </optgroup>
            <optgroup label="支出分类">
              <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </optgroup>
          </select>
          
          <div class="flex items-center gap-2">
            <input 
              type="date" 
              v-model="filters.start_date"
              class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
            <span class="text-gray-500">至</span>
            <input 
              type="date" 
              v-model="filters.end_date"
              class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <div class="relative">
            <input 
              type="text" 
              v-model="filters.keyword" 
              placeholder="搜索..."
              class="border border-gray-300 rounded-md pl-9 pr-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 w-48"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <button 
            @click="resetFilters" 
            class="text-gray-600 hover:text-gray-800"
          >
            重置
          </button>
        </div>
      </div>
    </div>
    
    <!-- 操作按钮 -->
    <div class="flex justify-between items-center mb-4">
      <div class="flex items-center space-x-4">
        <button 
          @click="showAddModal('income')" 
          class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 flex items-center"
        >
          <span class="mr-1">+</span> 添加收入
        </button>
        <button 
          @click="showAddModal('expense')" 
          class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 flex items-center"
        >
          <span class="mr-1">+</span> 添加支出
        </button>
      </div>
      
      <div class="text-sm text-gray-600">
        共 {{ totalTransactions }} 条记录
      </div>
    </div>
    
    <!-- 数据表格 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="py-20 text-center text-gray-500">
        <p class="text-lg">加载中...</p>
      </div>
      
      <div v-else-if="transactions.length === 0" class="py-16 text-center text-gray-500">
        <p class="text-lg">暂无交易记录</p>
        <div class="mt-4 flex justify-center space-x-4">
          <button 
            @click="showAddModal('income')" 
            class="text-green-600 hover:text-green-800"
          >
            添加收入
          </button>
          <button 
            @click="showAddModal('expense')" 
            class="text-red-600 hover:text-red-800"
          >
            添加支出
          </button>
        </div>
      </div>
      
      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  日期
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  类型
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  分类
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  账户
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  描述
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  金额
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(transaction.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="[
                      'inline-flex px-2 py-0.5 rounded-full text-xs font-medium', 
                      transaction.type === 'income' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ transaction.type === 'income' ? '收入' : '支出' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ transaction.category_name || '未分类' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ transaction.account_name || '未知账户' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                  {{ transaction.description || '无' }}
                </td>
                <td 
                  :class="[
                    'px-6 py-4 whitespace-nowrap text-sm font-medium text-right', 
                    transaction.type === 'income' ? 'text-green-600' : 'text-red-600'
                  ]"
                >
                  {{ transaction.type === 'income' ? '+' : '-' }}¥{{ formatNumber(transaction.amount) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button 
                    @click="editTransaction(transaction)" 
                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                  >
                    编辑
                  </button>
                  <button 
                    @click="confirmDelete(transaction)" 
                    class="text-red-600 hover:text-red-900"
                  >
                    删除
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- 分页 -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
          <div class="flex-1 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-700">
                显示第 
                <span class="font-medium">{{ (currentPage - 1) * pageSize + 1 }}</span>
                到
                <span class="font-medium">{{ Math.min(currentPage * pageSize, totalTransactions) }}</span>
                条，共
                <span class="font-medium">{{ totalTransactions }}</span>
                条结果
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="changePage(currentPage - 1)"
                  :disabled="currentPage <= 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  :class="{'opacity-50 cursor-not-allowed': currentPage <= 1}"
                >
                  <span class="sr-only">上一页</span>
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
                
                <button
                  v-for="page in displayedPages"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === currentPage
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
                
                <button
                  @click="changePage(currentPage + 1)"
                  :disabled="currentPage >= totalPages"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}"
                >
                  <span class="sr-only">下一页</span>
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 添加/编辑交易记录弹窗 -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          {{ editMode ? '编辑交易记录' : (transactionType === 'income' ? '添加收入' : '添加支出') }}
        </h3>
        
        <form @submit.prevent="handleSubmit">
          <div class="space-y-4">
            <!-- 交易类型 -->
            <div>
              <label class="block text-sm font-medium text-gray-700">交易类型</label>
              <div class="mt-1 flex">
                <button 
                  type="button" 
                  @click="transactionType = 'income'"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-l-md focus:outline-none',
                    transactionType === 'income'
                      ? 'bg-green-100 text-green-800 border-green-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  收入
                </button>
                <button 
                  type="button" 
                  @click="transactionType = 'expense'"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-r-md focus:outline-none',
                    transactionType === 'expense'
                      ? 'bg-red-100 text-red-800 border-red-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  支出
                </button>
              </div>
            </div>
            
            <!-- 金额 -->
            <div>
              <label for="amount" class="block text-sm font-medium text-gray-700">金额</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">¥</span>
                </div>
                <input 
                  type="number" 
                  step="0.01" 
                  id="amount" 
                  v-model="transactionForm.amount"
                  required
                  min="0.01"
                  class="pl-7 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            
            <!-- 日期 -->
            <div>
              <label for="date" class="block text-sm font-medium text-gray-700">日期</label>
              <input 
                type="date" 
                id="date" 
                v-model="transactionForm.date"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <!-- 账户 -->
            <div>
              <label for="account_id" class="block text-sm font-medium text-gray-700">账户</label>
              <select 
                id="account_id" 
                v-model="transactionForm.account_id"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">请选择账户</option>
                <option v-for="account in accounts" :key="account.id" :value="account.id">
                  {{ account.name }} (余额: ¥{{ formatNumber(account.amount) }})
                </option>
              </select>
            </div>
            
            <!-- 分类 -->
            <div>
              <label for="category_id" class="block text-sm font-medium text-gray-700">分类</label>
              <select 
                id="category_id" 
                v-model="transactionForm.category_id"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">请选择分类</option>
                <option 
                  v-for="category in filteredCategories" 
                  :key="category.id" 
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
              <div class="flex flex-wrap gap-2 mt-2">
                <button 
                  type="button"
                  @click="showAddCategoryModal = true"
                  class="text-xs text-indigo-600 hover:text-indigo-900"
                >
                  + 添加新分类
                </button>
              </div>
            </div>
            
            <!-- 描述 -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">描述</label>
              <textarea 
                id="description" 
                v-model="transactionForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '提交中...' : '确定' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 添加分类弹窗 -->
    <div v-if="showAddCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">添加新分类</h3>
        
        <form @submit.prevent="handleAddCategory">
          <div class="space-y-4">
            <!-- 分类类型 -->
            <div>
              <label class="block text-sm font-medium text-gray-700">分类类型</label>
              <div class="mt-1 flex">
                <button 
                  type="button" 
                  @click="categoryForm.type = 'income'"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-l-md focus:outline-none',
                    categoryForm.type === 'income'
                      ? 'bg-green-100 text-green-800 border-green-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  收入分类
                </button>
                <button 
                  type="button" 
                  @click="categoryForm.type = 'expense'"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-r-md focus:outline-none',
                    categoryForm.type === 'expense'
                      ? 'bg-red-100 text-red-800 border-red-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  支出分类
                </button>
              </div>
            </div>
            
            <!-- 分类名称 -->
            <div>
              <label for="category_name" class="block text-sm font-medium text-gray-700">分类名称</label>
              <input 
                type="text" 
                id="category_name" 
                v-model="categoryForm.name"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <!-- 父分类 -->
            <div>
              <label for="parent_id" class="block text-sm font-medium text-gray-700">父分类 (可选)</label>
              <select 
                id="parent_id" 
                v-model="categoryForm.parent_id"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">无父分类</option>
                <option 
                  v-for="category in categoryForm.type === 'income' ? incomeCategories : expenseCategories" 
                  :key="category.id" 
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
            </div>
            
            <!-- 图标颜色 -->
            <div>
              <label for="category_color" class="block text-sm font-medium text-gray-700">颜色</label>
              <div class="mt-1 flex items-center space-x-2">
                <input 
                  type="color" 
                  id="category_color" 
                  v-model="categoryForm.color"
                  class="h-8 w-8 border border-gray-300 rounded cursor-pointer"
                />
                <div 
                  v-for="color in predefinedColors" 
                  :key="color" 
                  :style="`background-color: ${color}`"
                  class="h-6 w-6 rounded-full cursor-pointer hover:opacity-80"
                  @click="categoryForm.color = color"
                ></div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showAddCategoryModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '提交中...' : '确定' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 确认删除弹窗 -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">确认删除</h3>
        <p class="text-gray-600">
          确定要删除该交易记录吗？此操作不可恢复。
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            @click="showDeleteConfirm = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="handleDelete" 
            :disabled="isSubmitting"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-red-300"
          >
            {{ isSubmitting ? '删除中...' : '确认删除' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';

interface Transaction {
  id: number;
  type: string;
  amount: number;
  date: string;
  category_id: number;
  category_name?: string;
  description?: string;
  account_id: number;
  account_name?: string;
}

interface Account {
  id: number;
  name: string;
  type: string;
  amount: number;
}

interface Category {
  id: number;
  name: string;
  type: string;
  parent_id?: number;
  color?: string;
}

interface TransactionForm {
  amount: number | string;
  date: string;
  description: string;
  category_id: number | string;
  account_id: number | string;
}

interface CategoryForm {
  name: string;
  type: string;
  parent_id: number | string;
  color: string;
}

interface Filters {
  type: string;
  account_id: number | string;
  category_id: number | string;
  start_date: string;
  end_date: string;
  keyword: string;
}

// 状态
const loading = ref<boolean>(true);
const isSubmitting = ref<boolean>(false);
const showModal = ref<boolean>(false);
const showAddCategoryModal = ref<boolean>(false);
const showDeleteConfirm = ref<boolean>(false);
const editMode = ref<boolean>(false);
const transactionType = ref<string>('expense');
const currentTransaction = ref<Transaction | null>(null);

// 预定义颜色
const predefinedColors = [
  '#4299e1', // 蓝色
  '#48bb78', // 绿色
  '#ed8936', // 橙色
  '#9f7aea', // 紫色
  '#f56565', // 红色
  '#38b2ac', // 青色
  '#667eea', // 靛蓝色
  '#d53f8c'  // 粉色
];

// 数据
const transactions = ref<Transaction[]>([]);
const accounts = ref<Account[]>([]);
const incomeCategories = ref<Category[]>([]);
const expenseCategories = ref<Category[]>([]);
const totalTransactions = ref<number>(0);
const currentPage = ref<number>(1);
const pageSize = ref<number>(10);

// 表单
const transactionForm = reactive<TransactionForm>({
  amount: '',
  date: new Date().toISOString().substr(0, 10),
  description: '',
  category_id: '',
  account_id: ''
});

// 分类表单
const categoryForm = reactive<CategoryForm>({
  name: '',
  type: 'expense',
  parent_id: '',
  color: '#4299e1'
});

// 筛选条件
const filters = reactive<Filters>({
  type: '',
  account_id: '',
  category_id: '',
  start_date: '',
  end_date: '',
  keyword: ''
});

// 计算总页数
const totalPages = computed(() => {
  return Math.ceil(totalTransactions.value / pageSize.value) || 1;
});

// 计算显示的页码
const displayedPages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const delta = 2; // 当前页码前后显示的页码数
  
  const pages: number[] = [];
  
  for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
    pages.push(i);
  }
  
  return pages;
});

// 根据当前交易类型筛选分类
const filteredCategories = computed(() => {
  return transactionType.value === 'income' ? incomeCategories.value : expenseCategories.value;
});

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 格式化日期
const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

// 加载交易记录
const loadTransactions = async () => {
  loading.value = true;
  
  try {
    // 构建请求参数
    const params: Record<string, any> = {
      page: currentPage.value,
      page_size: pageSize.value
    };
    
    // 添加筛选条件
    if (filters.type) params.type = filters.type;
    if (filters.account_id) params.account_id = filters.account_id;
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;
    if (filters.keyword) params.keyword = filters.keyword;
    
    const response = await axios.get('/api/transactions', { params });
    
    transactions.value = response.data.data || [];
    totalTransactions.value = response.data.total || 0;
  } catch (error) {
    console.error('加载交易记录失败:', error);
  } finally {
    loading.value = false;
  }
};

// 加载账户列表
const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/balance');
    accounts.value = response.data;
  } catch (error) {
    console.error('加载账户列表失败:', error);
  }
};

// 加载分类列表
const loadCategories = async () => {
  try {
    // 加载收入分类
    const incomeResponse = await axios.get('/api/categories', { params: { type: 'income' } });
    incomeCategories.value = incomeResponse.data;
    
    // 加载支出分类
    const expenseResponse = await axios.get('/api/categories', { params: { type: 'expense' } });
    expenseCategories.value = expenseResponse.data;
  } catch (error) {
    console.error('加载分类列表失败:', error);
  }
};

// 重置筛选条件
const resetFilters = () => {
  filters.type = '';
  filters.account_id = '';
  filters.category_id = '';
  filters.start_date = '';
  filters.end_date = '';
  filters.keyword = '';
  
  loadTransactions();
};

// 显示添加交易记录弹窗
const showAddModal = (type: string) => {
  editMode.value = false;
  transactionType.value = type;
  resetTransactionForm();
  showModal.value = true;
};

// 编辑交易记录
const editTransaction = (transaction: Transaction) => {
  currentTransaction.value = transaction;
  editMode.value = true;
  transactionType.value = transaction.type;
  
  // 填充表单
  transactionForm.amount = transaction.amount;
  transactionForm.date = transaction.date;
  transactionForm.description = transaction.description || '';
  transactionForm.category_id = transaction.category_id;
  transactionForm.account_id = transaction.account_id;
  
  showModal.value = true;
};

// 确认删除
const confirmDelete = (transaction: Transaction) => {
  currentTransaction.value = transaction;
  showDeleteConfirm.value = true;
};

// 提交交易记录表单
const handleSubmit = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  try {
    const data = {
      type: transactionType.value,
      amount: Number(transactionForm.amount),
      date: transactionForm.date,
      description: transactionForm.description,
      category_id: Number(transactionForm.category_id),
      account_id: Number(transactionForm.account_id)
    };
    
    if (editMode.value && currentTransaction.value) {
      // 更新交易记录
      await axios.put(`/api/transactions/${currentTransaction.value.id}`, data);
    } else {
      // 添加新交易记录
      await axios.post('/api/transactions', data);
    }
    
    // 刷新数据
    await loadTransactions();
    await loadAccounts();
    
    // 重置表单和状态
    resetTransactionForm();
    showModal.value = false;
    editMode.value = false;
    currentTransaction.value = null;
  } catch (error) {
    console.error('保存交易记录失败:', error);
    alert('保存交易记录失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 添加分类
const handleAddCategory = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  try {
    const data = {
      name: categoryForm.name,
      type: categoryForm.type,
      color: categoryForm.color
    };
    
    // 如果有父分类
    if (categoryForm.parent_id) {
      data['parent_id'] = Number(categoryForm.parent_id);
    }
    
    // 添加新分类
    const response = await axios.post('/api/categories', data);
    
    // 刷新分类列表
    await loadCategories();
    
    // 选中新添加的分类
    const newCategory = response.data;
    transactionForm.category_id = newCategory.id;
    
    // 关闭模态框
    showAddCategoryModal.value = false;
    
    // 重置分类表单
    resetCategoryForm();
  } catch (error) {
    console.error('添加分类失败:', error);
    alert('添加分类失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 删除交易记录
const handleDelete = async () => {
  if (isSubmitting.value || !currentTransaction.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.delete(`/api/transactions/${currentTransaction.value.id}`);
    
    // 刷新数据
    await loadTransactions();
    await loadAccounts();
    
    // 重置状态
    showDeleteConfirm.value = false;
    currentTransaction.value = null;
  } catch (error) {
    console.error('删除交易记录失败:', error);
    alert('删除交易记录失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 切换页码
const changePage = (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  
  currentPage.value = page;
  loadTransactions();
};

// 重置交易记录表单
const resetTransactionForm = () => {
  transactionForm.amount = '';
  transactionForm.date = new Date().toISOString().substr(0, 10);
  transactionForm.description = '';
  transactionForm.category_id = '';
  transactionForm.account_id = '';
};

// 重置分类表单
const resetCategoryForm = () => {
  categoryForm.name = '';
  categoryForm.type = transactionType.value; // 默认使用当前交易类型
  categoryForm.parent_id = '';
  categoryForm.color = '#4299e1';
};

// 监听筛选条件变化
watch([filters], () => {
  currentPage.value = 1; // 重置页码
  loadTransactions();
}, { deep: true });

// 监听交易类型变化
watch([transactionType], () => {
  // 当交易类型变化时，重置分类选择
  transactionForm.category_id = '';
});

// 组件挂载时加载数据
onMounted(() => {
  // 设置默认日期范围
  const now = new Date();
  const lastMonth = new Date(now);
  lastMonth.setMonth(lastMonth.getMonth() - 1);
  
  filters.end_date = now.toISOString().split('T')[0];
  filters.start_date = lastMonth.toISOString().split('T')[0];
  
  // 加载数据
  loadTransactions();
  loadAccounts();
  loadCategories();
});
</script>