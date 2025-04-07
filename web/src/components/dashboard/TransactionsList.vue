<template>
  <div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <h2 class="text-xl font-semibold text-gray-800">收支记录</h2>
      
      <!-- 筛选控件 -->
      <div class="flex flex-wrap gap-2">
        <select 
          v-model="filterType" 
          class="text-sm border rounded-md px-2 py-1 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">所有类型</option>
          <option value="income">收入</option>
          <option value="expense">支出</option>
        </select>
        
        <input 
          v-model="searchKeyword" 
          type="text" 
          placeholder="搜索描述..." 
          class="text-sm border rounded-md px-2 py-1 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @keyup.enter="loadTransactions"
        />
        
        <button 
          @click="loadTransactions"
          class="text-sm bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700"
        >
          搜索
        </button>
      </div>
    </div>
    
    <div v-if="loading" class="py-10 text-center text-gray-500">
      <p>加载中...</p>
    </div>
    
    <div v-else-if="transactions.length === 0" class="py-10 text-center text-gray-500">
      <p>暂无记录</p>
      <button 
        @click="$emit('add-transaction')" 
        class="mt-2 text-sm text-indigo-600 hover:text-indigo-800"
      >
        添加新记录
      </button>
    </div>
    
    <div v-else>
      <!-- 交易记录表格 -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                日期
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                类型
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                分类
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                账户
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                描述
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                金额
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                操作
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in transactions" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(item.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 
                    item.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ item.type === 'income' ? '收入' : '支出' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ getCategoryName(item) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ getAccountName(item.account_id) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                {{ item.description || '无' }}
              </td>
              <td 
                :class="[
                  'px-6 py-4 whitespace-nowrap text-sm font-medium text-right', 
                  item.type === 'income' ? 'text-green-600' : 'text-red-600'
                ]"
              >
                {{ item.type === 'income' ? '+' : '-' }}¥{{ formatNumber(item.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                <button 
                  @click="handleEdit(item)" 
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  编辑
                </button>
                <button 
                  @click="confirmDelete(item)" 
                  class="text-red-600 hover:text-red-900"
                >
                  删除
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- 分页控件 -->
      <div class="flex justify-between items-center mt-6">
        <div class="text-sm text-gray-500">
          共 {{ pagination.total }} 条记录
        </div>
        
        <div class="flex items-center space-x-2">
          <button 
            @click="changePage(pagination.current_page - 1)" 
            :disabled="pagination.current_page <= 1"
            :class="[
              'px-3 py-1 rounded-md', 
              pagination.current_page <= 1 
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            ]"
          >
            上一页
          </button>
          
          <span class="text-sm text-gray-600">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          
          <button 
            @click="changePage(pagination.current_page + 1)" 
            :disabled="pagination.current_page >= pagination.last_page"
            :class="[
              'px-3 py-1 rounded-md', 
              pagination.current_page >= pagination.last_page
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            ]"
          >
            下一页
          </button>
        </div>
      </div>
    </div>
    
    <!-- 确认删除弹窗 -->
    <div 
      v-if="showDeleteConfirm" 
      class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">确认删除</h3>
        <p class="text-gray-600">确定要删除这条记录吗？此操作不可恢复。</p>
        <div class="flex justify-end mt-6 space-x-3">
          <button 
            @click="showDeleteConfirm = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="handleDelete" 
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            删除
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

interface Transaction {
  id: number;
  type: string;
  amount: number;
  date: string;
  category_id: number;
  description?: string;
  account_id: number;
  source?: string;
  payment_method?: string;
  transaction_type: string;
  status?: string;
  category_name?: string;
}

interface Category {
  id: number;
  name: string;
  icon?: string;
  color?: string;
}

interface Account {
  id: number;
  name: string;
  type: string;
  amount: number;
}

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const transactions = ref<Transaction[]>([]);
const loading = ref<boolean>(false);
const filterType = ref<string>('');
const searchKeyword = ref<string>('');
const currentPage = ref<number>(1);
const categories = ref<{[key: string]: Category[]}>({
  income: [],
  expense: []
});
const accounts = ref<Account[]>([]);
const showDeleteConfirm = ref<boolean>(false);
const currentTransaction = ref<Transaction | null>(null);
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
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

// 获取分类名称
const getCategoryName = (transaction: Transaction): string => {
  if (transaction.category_name) {
    return transaction.category_name;
  }
  
  const categoryList = transaction.type === 'income' ? categories.value.income : categories.value.expense;
  const category = categoryList.find(c => c.id === transaction.category_id);
  return category ? category.name : '未分类';
};

// 获取账户名称
const getAccountName = (accountId: number): string => {
  const account = accounts.value.find(a => a.id === accountId);
  return account ? account.name : '未知账户';
};

// 加载所有分类
const loadCategories = async () => {
  try {
    // 加载收入分类
    const incomeResponse = await axios.get('/api/income-categories');
    categories.value.income = incomeResponse.data;
    
    // 加载支出分类
    const expenseResponse = await axios.get('/api/expense-categories');
    categories.value.expense = expenseResponse.data;
  } catch (error) {
    console.error('加载分类数据失败:', error);
  }
};

// 加载所有账户
const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/balance');
    accounts.value = response.data;
  } catch (error) {
    console.error('加载账户数据失败:', error);
  }
};

// 加载交易记录
const loadTransactions = async () => {
  loading.value = true;
  
  try {
    const response = await axios.get('/api/income-expense/list', {
      params: {
        page: currentPage.value,
        page_size: 10,
        type: filterType.value || undefined,
        keyword: searchKeyword.value || undefined
      }
    });
    
    transactions.value = response.data.data;
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total
    };
  } catch (error) {
    console.error('加载交易记录失败:', error);
  } finally {
    loading.value = false;
  }
};

// 切换分页
const changePage = (page: number) => {
  if (page < 1 || page > pagination.value.last_page) return;
  currentPage.value = page;
  loadTransactions();
};

// 编辑记录
const handleEdit = (transaction: Transaction) => {
  emit('edit-transaction', transaction);
};

// 确认删除
const confirmDelete = (transaction: Transaction) => {
  currentTransaction.value = transaction;
  showDeleteConfirm.value = true;
};

// 执行删除
const handleDelete = async () => {
  if (!currentTransaction.value) return;
  
  try {
    await axios.delete(`/api/income-expense/${currentTransaction.value.id}`);
    showDeleteConfirm.value = false;
    
    // 刷新列表
    loadTransactions();
  } catch (error) {
    console.error('删除记录失败:', error);
    alert('删除失败，请稍后重试');
  }
};

// 定义组件的事件
const emit = defineEmits<{
  (e: 'edit-transaction', transaction: Transaction): void;
  (e: 'add-transaction'): void;
}>();

// 监听筛选条件变化
watch([filterType], () => {
  currentPage.value = 1; // 重置到第一页
  loadTransactions();
});

onMounted(() => {
  loadCategories();
  loadAccounts();
  loadTransactions();
});
</script>