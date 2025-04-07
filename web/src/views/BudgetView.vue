<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">预算管理</h1>
    
    <!-- 时间范围选择 -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
          <button
            v-for="period in budgetPeriods"
            :key="period.value"
            @click="currentPeriod = period.value"
            :class="[
              'px-4 py-2 rounded-md text-sm font-medium transition-colors',
              currentPeriod === period.value
                ? 'bg-indigo-100 text-indigo-700 border border-indigo-300'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-transparent'
            ]"
          >
            {{ period.label }}
          </button>
        </div>
        
        <button 
          @click="showAddBudgetModal = true"
          class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 flex items-center"
        >
          <span class="mr-1">+</span> 添加预算
        </button>
      </div>
    </div>
    
    <div v-if="loading" class="py-20 text-center text-gray-500">
      <p class="text-lg">正在加载预算数据...</p>
    </div>
    
    <div v-else>
      <!-- 总预算摘要 -->
      <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-800">总预算</h2>
          <div class="text-sm text-gray-600">
            {{ formatPeriodDate(currentPeriod) }}
          </div>
        </div>
        
        <div v-if="totalBudget">
          <div class="mb-2 flex justify-between">
            <div>
              <span class="text-gray-600 text-sm">总预算金额：</span>
              <span class="font-semibold">¥{{ formatNumber(totalBudget.amount) }}</span>
            </div>
            <div>
              <span class="text-gray-600 text-sm">已使用：</span>
              <span class="font-semibold" :class="getUsageTextColor(totalBudget.usage_percentage)">
                ¥{{ formatNumber(totalBudget.used_amount) }} 
                ({{ Math.round(totalBudget.usage_percentage) }}%)
              </span>
            </div>
            <div>
              <span class="text-gray-600 text-sm">剩余：</span>
              <span class="font-semibold text-green-600">
                ¥{{ formatNumber(totalBudget.amount - totalBudget.used_amount) }}
              </span>
            </div>
          </div>
          
          <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div 
              class="h-2.5 rounded-full" 
              :style="`width: ${Math.min(totalBudget.usage_percentage, 100)}%; background-color: ${getProgressColor(totalBudget.usage_percentage)}`"
            ></div>
          </div>
          
          <div class="flex justify-end mt-4 space-x-2">
            <button 
              @click="editBudget(totalBudget)"
              class="text-indigo-600 hover:text-indigo-800 text-sm"
            >
              编辑
            </button>
            <button 
              @click="confirmDeleteBudget(totalBudget)"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              删除
            </button>
          </div>
        </div>
        
        <div v-else class="text-center py-6 text-gray-500">
          <p>没有设置总预算</p>
          <button 
            @click="showAddTotalBudgetModal()"
            class="mt-2 text-indigo-600 hover:text-indigo-800"
          >
            添加总预算
          </button>
        </div>
      </div>
      
      <!-- 分类预算列表 -->
      <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-800">分类预算</h2>
          <button 
            @click="showAddCategoryBudgetModal()"
            class="text-indigo-600 hover:text-indigo-800"
          >
            添加分类预算
          </button>
        </div>
        
        <div v-if="categoryBudgets.length === 0" class="text-center py-6 text-gray-500">
          <p>暂无分类预算</p>
        </div>
        
        <div v-else class="space-y-4">
          <div 
            v-for="budget in categoryBudgets" 
            :key="budget.id"
            class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center">
                <div 
                  class="w-3 h-3 rounded-full mr-2" 
                  :style="`background-color: ${budget.category_color || '#9ca3af'}`"
                ></div>
                <h3 class="font-medium">{{ budget.category_name }}</h3>
              </div>
              <div class="text-sm text-gray-600">
                <span :class="getUsageTextColor(budget.usage_percentage)">
                  {{ Math.round(budget.usage_percentage) }}%
                </span>
                <span class="mx-1">|</span>
                <span>¥{{ formatNumber(budget.used_amount) }} / ¥{{ formatNumber(budget.amount) }}</span>
              </div>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
              <div 
                class="h-2.5 rounded-full" 
                :style="`width: ${Math.min(budget.usage_percentage, 100)}%; background-color: ${getProgressColor(budget.usage_percentage)}`"
              ></div>
            </div>
            
            <div class="flex justify-between items-center text-sm">
              <div>
                <span class="text-gray-600">剩余: </span>
                <span class="font-medium">¥{{ formatNumber(budget.amount - budget.used_amount) }}</span>
              </div>
              <div class="flex space-x-2">
                <button 
                  @click="editBudget(budget)"
                  class="text-indigo-600 hover:text-indigo-800"
                >
                  编辑
                </button>
                <button 
                  @click="confirmDeleteBudget(budget)"
                  class="text-red-600 hover:text-red-800"
                >
                  删除
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 预算使用详情 -->
      <div v-if="selectedBudget" class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ selectedBudget.category_id ? `${selectedBudget.category_name} - ` : '' }}预算使用详情
          </h2>
          <button 
            @click="selectedBudget = null"
            class="text-gray-500 hover:text-gray-700"
          >
            关闭
          </button>
        </div>
        
        <div v-if="budgetTransactions.length === 0" class="text-center py-6 text-gray-500">
          <p>暂无相关交易记录</p>
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
                    分类
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    描述
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    账户
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    金额
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in budgetTransactions" :key="transaction.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(transaction.date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ transaction.category_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ transaction.description || '无' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ transaction.account_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-red-600">
                    ¥{{ formatNumber(transaction.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 添加/编辑预算弹窗 -->
    <div v-if="showAddBudgetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          {{ editMode ? '编辑预算' : '添加预算' }}
        </h3>
        
        <form @submit.prevent="handleSubmitBudget">
          <div class="space-y-4">
            <!-- 预算类型 -->
            <div>
              <label class="block text-sm font-medium text-gray-700">预算类型</label>
              <div class="mt-1 flex">
                <button 
                  type="button" 
                  @click="budgetForm.is_total = true"
                  :disabled="editMode"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-l-md focus:outline-none',
                    budgetForm.is_total
                      ? 'bg-indigo-100 text-indigo-800 border-indigo-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                    editMode ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  总预算
                </button>
                <button 
                  type="button" 
                  @click="budgetForm.is_total = false"
                  :disabled="editMode"
                  :class="[
                    'flex-1 py-2 px-4 text-center text-sm font-medium border rounded-r-md focus:outline-none',
                    !budgetForm.is_total
                      ? 'bg-indigo-100 text-indigo-800 border-indigo-300'
                      : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                    editMode ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  分类预算
                </button>
              </div>
            </div>
            
            <!-- 支出分类 (仅当不是总预算时显示) -->
            <div v-if="!budgetForm.is_total">
              <label for="category_id" class="block text-sm font-medium text-gray-700">支出分类</label>
              <select 
                id="category_id" 
                v-model="budgetForm.category_id"
                required
                :disabled="editMode"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': editMode }"
              >
                <option value="">请选择支出分类</option>
                <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
            
            <!-- 金额 -->
            <div>
              <label for="budget_amount" class="block text-sm font-medium text-gray-700">预算金额</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">¥</span>
                </div>
                <input 
                  type="number" 
                  step="0.01" 
                  id="budget_amount" 
                  v-model="budgetForm.amount"
                  required
                  min="0.01"
                  class="pl-7 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            
            <!-- 预算周期 -->
            <div>
              <label for="budget_period" class="block text-sm font-medium text-gray-700">预算周期</label>
              <select 
                id="budget_period" 
                v-model="budgetForm.period"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="monthly">月度预算</option>
                <option value="yearly">年度预算</option>
                <option value="custom">自定义时间段</option>
              </select>
            </div>
            
            <!-- 自定义时间段 (仅当选择自定义时显示) -->
            <div v-if="budgetForm.period === 'custom'" class="space-y-2">
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">开始日期</label>
                <input 
                  type="date" 
                  id="start_date" 
                  v-model="budgetForm.start_date"
                  required
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">结束日期</label>
                <input 
                  type="date" 
                  id="end_date" 
                  v-model="budgetForm.end_date"
                  required
                  :min="budgetForm.start_date"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            
            <!-- 提醒阈值 -->
            <div>
              <label for="notification_threshold" class="block text-sm font-medium text-gray-700">提醒阈值 (%)</label>
              <div class="flex items-center mt-1">
                <input 
                  type="range" 
                  id="notification_threshold" 
                  v-model.number="budgetForm.notification_threshold"
                  min="1" 
                  max="100" 
                  step="1"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <span class="ml-2 text-sm text-gray-700 min-w-[3rem]">{{ budgetForm.notification_threshold }}%</span>
              </div>
            </div>
            
            <!-- 描述 -->
            <div>
              <label for="budget_description" class="block text-sm font-medium text-gray-700">描述</label>
              <textarea 
                id="budget_description" 
                v-model="budgetForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showAddBudgetModal = false"
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
          确定要删除{{ currentBudget?.category_name ? `"${currentBudget.category_name}"的` : '总' }}预算吗？此操作不可恢复。
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            @click="showDeleteConfirm = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="handleDeleteBudget" 
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

interface Budget {
  id: number;
  category_id: number | null;
  category_name?: string;
  category_color?: string;
  amount: number;
  used_amount: number;
  usage_percentage: number;
  start_date: string;
  end_date: string;
  notification_threshold: number;
  description?: string;
}

interface Category {
  id: number;
  name: string;
  color?: string;
}

interface Transaction {
  id: number;
  type: string;
  amount: number;
  date: string;
  category_id: number;
  category_name: string;
  description?: string;
  account_id: number;
  account_name: string;
}

interface BudgetForm {
  is_total: boolean;
  category_id: number | string;
  amount: number | string;
  period: string;
  start_date: string;
  end_date: string;
  notification_threshold: number;
  description: string;
}

// 状态
const loading = ref<boolean>(true);
const isSubmitting = ref<boolean>(false);
const showAddBudgetModal = ref<boolean>(false);
const showDeleteConfirm = ref<boolean>(false);
const editMode = ref<boolean>(false);
const totalBudget = ref<Budget | null>(null);
const categoryBudgets = ref<Budget[]>([]);
const expenseCategories = ref<Category[]>([]);
const currentBudget = ref<Budget | null>(null);
const selectedBudget = ref<Budget | null>(null);
const budgetTransactions = ref<Transaction[]>([]);

// 预算周期选项
const budgetPeriods = [
  { label: '本月', value: 'current_month' },
  { label: '下月', value: 'next_month' },
  { label: '本年', value: 'current_year' },
];

// 当前选择的预算周期
const currentPeriod = ref<string>('current_month');

// 预算表单
const budgetForm = reactive<BudgetForm>({
  is_total: true,
  category_id: '',
  amount: '',
  period: 'monthly',
  start_date: '',
  end_date: '',
  notification_threshold: 80,
  description: ''
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

// 根据使用率获取进度条颜色
const getProgressColor = (percentage: number): string => {
  if (percentage <= 70) return '#4ade80'; // 绿色
  if (percentage <= 90) return '#facc15'; // 黄色
  return '#ef4444'; // 红色
};

// 根据使用率获取文字颜色
const getUsageTextColor = (percentage: number): string => {
  if (percentage <= 70) return 'text-green-600';
  if (percentage <= 90) return 'text-yellow-600';
  return 'text-red-600';
};

// 格式化预算周期显示
const formatPeriodDate = (period: string): string => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  
  switch (period) {
    case 'current_month':
      return `${year}年${month + 1}月`;
    case 'next_month':
      return `${month === 11 ? year + 1 : year}年${month === 11 ? 1 : month + 2}月`;
    case 'current_year':
      return `${year}年`;
    default:
      return '';
  }
};

// 加载预算数据
const loadBudgets = async () => {
  loading.value = true;
  
  try {
    // 构建请求参数
    const params = { period: currentPeriod.value };
    
    // 获取预算数据
    const response = await axios.get('/api/budgets', { params });
    
    // 分离总预算和分类预算
    totalBudget.value = response.data.find((b: Budget) => b.category_id === null) || null;
    categoryBudgets.value = response.data.filter((b: Budget) => b.category_id !== null);
  } catch (error) {
    console.error('加载预算数据失败:', error);
  } finally {
    loading.value = false;
  }
};

// 加载支出分类
const loadExpenseCategories = async () => {
  try {
    const response = await axios.get('/api/categories', { params: { type: 'expense' } });
    expenseCategories.value = response.data;
  } catch (error) {
    console.error('加载支出分类失败:', error);
  }
};

// 加载预算相关的交易记录
const loadBudgetTransactions = async (budget: Budget) => {
  try {
    const params: Record<string, any> = {
      start_date: budget.start_date,
      end_date: budget.end_date,
      type: 'expense'
    };
    
    // 如果是分类预算，添加分类过滤
    if (budget.category_id) {
      params.category_id = budget.category_id;
    }
    
    const response = await axios.get('/api/transactions', { params });
    budgetTransactions.value = response.data.data || [];
  } catch (error) {
    console.error('加载预算交易记录失败:', error);
    budgetTransactions.value = [];
  }
};

// 显示添加总预算弹窗
const showAddTotalBudgetModal = () => {
  editMode.value = false;
  budgetForm.is_total = true;
  resetBudgetForm();
  
  // 根据当前选择的周期设置默认开始和结束日期
  setDefaultDates();
  
  showAddBudgetModal.value = true;
};

// 显示添加分类预算弹窗
const showAddCategoryBudgetModal = () => {
  editMode.value = false;
  budgetForm.is_total = false;
  resetBudgetForm();
  
  // 根据当前选择的周期设置默认开始和结束日期
  setDefaultDates();
  
  showAddBudgetModal.value = true;
};

// 设置默认开始和结束日期
const setDefaultDates = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  
  switch (currentPeriod.value) {
    case 'current_month':
      budgetForm.start_date = new Date(year, month, 1).toISOString().split('T')[0];
      budgetForm.end_date = new Date(year, month + 1, 0).toISOString().split('T')[0];
      break;
    case 'next_month':
      budgetForm.start_date = new Date(year, month + 1, 1).toISOString().split('T')[0];
      budgetForm.end_date = new Date(year, month + 2, 0).toISOString().split('T')[0];
      break;
    case 'current_year':
      budgetForm.start_date = new Date(year, 0, 1).toISOString().split('T')[0];
      budgetForm.end_date = new Date(year, 11, 31).toISOString().split('T')[0];
      break;
  }
};

// 编辑预算
const editBudget = (budget: Budget) => {
  currentBudget.value = budget;
  editMode.value = true;
  
  // 填充表单
  budgetForm.is_total = budget.category_id === null;
  budgetForm.category_id = budget.category_id || '';
  budgetForm.amount = budget.amount;
  budgetForm.start_date = budget.start_date;
  budgetForm.end_date = budget.end_date;
  budgetForm.notification_threshold = budget.notification_threshold;
  budgetForm.description = budget.description || '';
  
  // 根据日期范围判断周期类型
  determinePeriodType(budget.start_date, budget.end_date);
  
  showAddBudgetModal.value = true;
};

// 根据开始和结束日期确定周期类型
const determinePeriodType = (startDate: string, endDate: string) => {
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  // 检查是否是一个月
  if (
    start.getDate() === 1 && 
    end.getDate() === new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate() &&
    end.getMonth() === (start.getMonth() + 1) % 12
  ) {
    budgetForm.period = 'monthly';
    return;
  }
  
  // 检查是否是一年
  if (
    start.getMonth() === 0 && 
    start.getDate() === 1 && 
    end.getMonth() === 11 && 
    end.getDate() === 31 &&
    end.getFullYear() === start.getFullYear()
  ) {
    budgetForm.period = 'yearly';
    return;
  }
  
  // 其他情况视为自定义
  budgetForm.period = 'custom';
};

// 确认删除预算
const confirmDeleteBudget = (budget: Budget) => {
  currentBudget.value = budget;
  showDeleteConfirm.value = true;
};

// 查看预算详情
const viewBudgetDetails = (budget: Budget) => {
  selectedBudget.value = budget;
  loadBudgetTransactions(budget);
};

// 处理提交预算表单
const handleSubmitBudget = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  try {
    // 根据周期类型设置日期
    if (budgetForm.period !== 'custom') {
      setDefaultDates();
    }
    
    const data: Record<string, any> = {
      amount: Number(budgetForm.amount),
      start_date: budgetForm.start_date,
      end_date: budgetForm.end_date,
      notification_threshold: budgetForm.notification_threshold,
      description: budgetForm.description
    };
    
    // 如果不是总预算，添加分类ID
    if (!budgetForm.is_total) {
      data.category_id = Number(budgetForm.category_id);
    }
    
    if (editMode.value && currentBudget.value) {
      // 更新预算
      await axios.put(`/api/budgets/${currentBudget.value.id}`, data);
    } else {
      // 添加新预算
      await axios.post('/api/budgets', data);
    }
    
    // 刷新预算列表
    await loadBudgets();
    
    // 重置表单和状态
    resetBudgetForm();
    showAddBudgetModal.value = false;
    editMode.value = false;
    currentBudget.value = null;
  } catch (error) {
    console.error('保存预算失败:', error);
    alert('保存预算失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理删除预算
const handleDeleteBudget = async () => {
  if (isSubmitting.value || !currentBudget.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.delete(`/api/budgets/${currentBudget.value.id}`);
    
    // 刷新预算列表
    await loadBudgets();
    
    // 如果删除的是当前正在查看的预算，关闭详情面板
    if (selectedBudget.value && selectedBudget.value.id === currentBudget.value.id) {
      selectedBudget.value = null;
    }
    
    // 重置状态
    showDeleteConfirm.value = false;
    currentBudget.value = null;
  } catch (error) {
    console.error('删除预算失败:', error);
    alert('删除预算失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 重置预算表单
const resetBudgetForm = () => {
  budgetForm.is_total = true;
  budgetForm.category_id = '';
  budgetForm.amount = '';
  budgetForm.period = 'monthly';
  budgetForm.start_date = '';
  budgetForm.end_date = '';
  budgetForm.notification_threshold = 80;
  budgetForm.description = '';
};

// 监听预算周期变化
watch([currentPeriod], () => {
  loadBudgets();
});

// 监听预算表单周期类型变化
watch(() => budgetForm.period, () => {
  if (budgetForm.period !== 'custom') {
    setDefaultDates();
  }
});

// 组件挂载时加载数据
onMounted(() => {
  loadBudgets();
  loadExpenseCategories();
});
</script>