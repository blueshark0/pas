<!-- 收支概览组件 - 展示基本统计信息 -->
<template>
  <div class="finance-summary bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-800 mb-4">财务概览</h3>
    
    <div v-if="loading" class="flex justify-center items-center py-10">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-500"></div>
    </div>
    
    <div v-else>
      <!-- 总资产 -->
      <div class="mb-6">
        <div class="text-gray-600 text-sm mb-1">总资产</div>
        <div class="text-2xl font-bold text-gray-800">¥{{ formatNumber(summary.totalAssets) }}</div>
      </div>
      
      <!-- 本月收支情况 -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
          <div class="text-gray-600 text-sm mb-1">本月收入</div>
          <div class="text-xl font-semibold text-green-600">¥{{ formatNumber(summary.monthlyIncome) }}</div>
        </div>
        <div>
          <div class="text-gray-600 text-sm mb-1">本月支出</div>
          <div class="text-xl font-semibold text-red-600">¥{{ formatNumber(summary.monthlyExpenses) }}</div>
        </div>
      </div>
      
      <!-- 账户列表 -->
      <div class="mb-4">
        <div class="flex justify-between items-center mb-3">
          <div class="text-gray-600 text-sm">账户列表</div>
          <button @click="showAddAccount = true" class="text-indigo-600 text-sm hover:text-indigo-800">
            + 添加账户
          </button>
        </div>
        
        <div class="space-y-2">
          <div v-for="account in accounts" :key="account.id" 
               class="flex justify-between items-center p-3 bg-gray-50 rounded-md hover:bg-gray-100">
            <div>
              <div class="flex items-center">
                <span class="text-gray-800">{{ account.name }}</span>
                <span class="ml-2 text-xs px-2 py-0.5 bg-gray-200 text-gray-700 rounded-full">{{ formatAccountType(account.type) }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ account.description || '无描述' }}</div>
            </div>
            <div class="flex items-center">
              <div class="text-gray-800 font-medium">¥{{ formatNumber(account.amount) }}</div>
              <button @click="editAccount(account)" class="ml-3 text-indigo-600 hover:text-indigo-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
          </div>
          
          <div v-if="accounts.length === 0" class="text-center py-4 text-gray-500">
            暂无账户，请添加一个账户
          </div>
        </div>
      </div>
    </div>
    
    <!-- 添加/编辑账户弹窗 -->
    <div v-if="showAddAccount" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ editMode ? '编辑账户' : '添加账户' }}
          </h2>
          <button @click="closeForm" class="text-gray-500 hover:text-gray-700">
            <span class="text-2xl">&times;</span>
          </button>
        </div>
        
        <div class="p-6">
          <BalanceForm 
            :edit-data="currentAccount" 
            @save-success="handleSaveSuccess" 
            @cancel="closeForm"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import BalanceForm from '@/components/BalanceForm.vue';

interface Summary {
  totalAssets: number;
  monthlyIncome: number;
  monthlyExpenses: number;
}

interface Account {
  id: number;
  name: string;
  type: string;
  amount: number;
  description?: string;
}

// 状态
const loading = ref<boolean>(false);
const accounts = ref<Account[]>([]);
const summary = reactive<Summary>({
  totalAssets: 0,
  monthlyIncome: 0,
  monthlyExpenses: 0
});

// 表单状态
const showAddAccount = ref<boolean>(false);
const editMode = ref<boolean>(false);
const currentAccount = ref<Account | null>(null);

// 获取账户列表
const fetchAccounts = async (): Promise<void> => {
  loading.value = true;
  
  try {
    const response = await axios.get('/api/balance/list');
    accounts.value = response.data.data || [];
    
    // 计算总资产
    summary.totalAssets = accounts.value.reduce((total, account) => total + account.amount, 0);
  } catch (error) {
    console.error('获取账户列表失败:', error);
  } finally {
    loading.value = false;
  }
};

// 获取本月收支统计
const fetchMonthlySummary = async (): Promise<void> => {
  try {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().substr(0, 10);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().substr(0, 10);
    
    const response = await axios.get('/api/income-expense/summary', {
      params: {
        start_date: firstDay,
        end_date: lastDay
      }
    });
    
    if (response.data) {
      summary.monthlyIncome = response.data.income || 0;
      summary.monthlyExpenses = response.data.expense || 0;
    }
  } catch (error) {
    console.error('获取月度统计失败:', error);
  }
};

// 格式化账户类型
const formatAccountType = (type: string): string => {
  const typeMap: Record<string, string> = {
    'cash': '现金',
    'bank': '银行卡',
    'alipay': '支付宝',
    'wechat': '微信',
    'credit': '信用卡',
    'other': '其他'
  };
  
  return typeMap[type] || type;
};

// 格式化数字
const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('zh-CN', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num);
};

// 编辑账户
const editAccount = (account: Account): void => {
  currentAccount.value = account;
  editMode.value = true;
  showAddAccount.value = true;
};

// 处理保存成功
const handleSaveSuccess = (): void => {
  closeForm();
  fetchAccounts();
};

// 关闭表单
const closeForm = (): void => {
  showAddAccount.value = false;
  editMode.value = false;
  currentAccount.value = null;
};

// 组件挂载时获取数据
onMounted(() => {
  fetchAccounts();
  fetchMonthlySummary();
});
</script>

<style scoped>
/* 媒体查询，确保在移动设备上有良好体验 */
@media (max-width: 640px) {
  .finance-summary {
    padding: 1rem;
  }
}
</style>