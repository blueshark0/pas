<!-- 收支记录列表组件 -->
<template>
  <div class="transactions-list bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-800 mb-4">收支记录</h3>
    
    <!-- 筛选器 -->
    <div class="mb-6 space-y-3 md:space-y-0 md:flex md:justify-between md:items-center">
      <!-- 类型筛选 -->
      <div class="flex space-x-2">
        <button 
          v-for="option in typeOptions" 
          :key="option.value"
          @click="filters.type = option.value"
          :class="[
            'px-3 py-1 text-sm rounded-md',
            filters.type === option.value 
              ? 'bg-indigo-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ option.label }}
        </button>
      </div>
      
      <!-- 日期范围筛选 -->
      <div class="flex space-x-2 items-center text-sm">
        <input 
          type="date" 
          v-model="filters.startDate"
          class="border border-gray-300 rounded-md px-2 py-1"
        />
        <span class="text-gray-500">至</span>
        <input 
          type="date" 
          v-model="filters.endDate"
          class="border border-gray-300 rounded-md px-2 py-1"
        />
        
        <!-- 分类筛选 -->
        <select 
          v-model="filters.category"
          class="ml-2 border border-gray-300 rounded-md px-2 py-1 text-sm"
        >
          <option value="">全部分类</option>
          <option v-for="category in categories[filters.type === 'income' ? 'income' : 'expense']" 
                  :key="category" 
                  :value="category">
            {{ category }}
          </option>
        </select>
        
        <button 
          @click="fetchTransactions"
          class="bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700 ml-2"
        >
          筛选
        </button>
      </div>
    </div>
    
    <!-- 加载状态 -->
    <div v-if="loading" class="flex justify-center items-center py-10">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-500"></div>
    </div>
    
    <!-- 数据表格 -->
    <div v-else class="overflow-x-auto">
      <!-- 无数据提示 -->
      <div v-if="!transactions.length" class="text-center py-10 text-gray-500">
        暂无记录
      </div>
      
      <!-- 数据表格 -->
      <table v-else class="min-w-full divide-y divide-gray-200">
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
              描述
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              金额
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              交易方式
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              操作
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="transaction in transactions" :key="transaction.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(transaction.date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                transaction.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
              ]">
                {{ transaction.type === 'income' ? '收入' : '支出' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ transaction.category }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
              {{ transaction.description || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm" 
                :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
              ¥{{ formatNumber(transaction.amount) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatTransactionType(transaction.transaction_type) }}
              <span v-if="transaction.transaction_type === 'installment'" class="text-xs text-gray-400 ml-1">
                ({{ transaction.installment_info?.current_period || '1' }}/{{ transaction.installment_info?.total_periods || '1' }})
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="editTransaction(transaction)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                编辑
              </button>
              <button @click="confirmDelete(transaction)" class="text-red-600 hover:text-red-900">
                删除
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- 分页 -->
      <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-500">
          总计 {{ pagination.total }} 条记录
        </div>
        <div class="flex space-x-1">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            :class="[
              'px-3 py-1 text-sm rounded-md',
              pagination.current_page <= 1
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            上一页
          </button>
          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            :class="[
              'px-3 py-1 text-sm rounded-md',
              pagination.current_page >= pagination.last_page
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            下一页
          </button>
        </div>
      </div>
    </div>
    
    <!-- 删除确认对话框 -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg max-w-md mx-auto">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">确认删除</h4>
        <p class="text-gray-600 mb-6">您确定要删除这条记录吗？此操作无法撤销。</p>
        <div class="flex justify-end space-x-3">
          <button @click="showDeleteConfirm = false" 
                  class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            取消
          </button>
          <button @click="deleteTransaction" 
                  class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
            删除
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

// 定义类型
interface TypeOption {
  label: string;
  value: string;
}

interface Pagination {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface InstallmentInfo {
  total_amount: number;
  total_periods: number;
  current_period: number;
}

interface Transaction {
  id: number;
  type: string;
  amount: number;
  date: string;
  category: string;
  description?: string;
  tags?: string[];
  transaction_type: string;
  period?: string;
  installment_info?: InstallmentInfo;
  account_id: number;
  status?: string;
}

interface FilterOptions {
  type: string;
  startDate: string;
  endDate: string;
  category: string;
}

// 事件
const emit = defineEmits<{
  (e: 'edit-transaction', transaction: Transaction): void
}>();

// 状态
const loading = ref<boolean>(false);
const transactions = ref<Transaction[]>([]);
const selectedTransaction = ref<Transaction | null>(null);
const showDeleteConfirm = ref<boolean>(false);

// 分页信息
const pagination = reactive<Pagination>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
});

// 类型筛选选项
const typeOptions: TypeOption[] = [
  { label: '全部', value: '' },
  { label: '收入', value: 'income' },
  { label: '支出', value: 'expense' }
];

// 默认分类
const categories: Record<string, string[]> = {
  income: ['工资', '奖金', '投资收益', '兼职', '礼金', '其他收入'],
  expense: ['餐饮', '购物', '交通', '住房', '娱乐', '医疗', '教育', '旅行', '日用品', '其他支出']
};

// 筛选条件
const filters = reactive<FilterOptions>({
  type: '',
  startDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substr(0, 10),
  endDate: new Date().toISOString().substr(0, 10),
  category: ''
});

// 格式化日期
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('zh-CN');
};

// 格式化数字
const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('zh-CN', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num);
};

// 格式化交易类型
const formatTransactionType = (type: string): string => {
  switch (type) {
    case 'single': return '一次性';
    case 'periodic': return '周期性';
    case 'installment': return '分期付款';
    default: return type;
  }
};

// 获取交易记录
const fetchTransactions = async (page: number = 1): Promise<void> => {
  loading.value = true;
  
  try {
    const params: Record<string, string | number> = {
      page: page,
      page_size: pagination.per_page,
      start_date: filters.startDate,
      end_date: filters.endDate
    };
    
    if (filters.type) {
      params.type = filters.type;
    }
    
    if (filters.category) {
      params.category = filters.category;
    }
    
    const response = await axios.get('/api/income-expense/list', { params });
    
    if (response.data) {
      transactions.value = response.data.data || [];
      
      // 更新分页信息
      pagination.current_page = response.data.current_page || 1;
      pagination.per_page = response.data.per_page || 10;
      pagination.total = response.data.total || 0;
      pagination.last_page = response.data.last_page || 1;
    }
  } catch (error) {
    console.error('获取交易记录失败:', error);
  } finally {
    loading.value = false;
  }
};

// 切换页码
const changePage = (page: number): void => {
  if (page < 1 || page > pagination.last_page) return;
  fetchTransactions(page);
};

// 编辑交易记录
const editTransaction = (transaction: Transaction): void => {
  emit('edit-transaction', transaction);
};

// 确认删除
const confirmDelete = (transaction: Transaction): void => {
  selectedTransaction.value = transaction;
  showDeleteConfirm.value = true;
};

// 删除交易记录
const deleteTransaction = async (): Promise<void> => {
  if (!selectedTransaction.value) return;
  
  try {
    await axios.delete(`/api/income-expense/delete/${selectedTransaction.value.id}`);
    showDeleteConfirm.value = false;
    fetchTransactions(pagination.current_page);
  } catch (error) {
    console.error('删除交易记录失败:', error);
    alert('删除失败: ' + (error as any).response?.data?.message || (error as Error).message);
  }
};

// 组件挂载时获取数据
onMounted(() => {
  fetchTransactions();
});
</script>

<style scoped>
@media (max-width: 640px) {
  .transactions-list {
    padding: 1rem;
  }
  
  table {
    display: block;
    overflow-x: auto;
  }
}
</style>