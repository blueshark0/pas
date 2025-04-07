<template>
  <div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">财务概览</h2>
    
    <div class="space-y-4">
      <!-- 本月收支统计 -->
      <div>
        <p class="text-sm text-gray-500 mb-1">统计周期: {{ periodText }}</p>
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-green-50 p-3 rounded-md">
            <p class="text-sm text-gray-600">收入</p>
            <p class="text-lg font-bold text-green-600">¥{{ formatNumber(summary.income) }}</p>
          </div>
          <div class="bg-red-50 p-3 rounded-md">
            <p class="text-sm text-gray-600">支出</p>
            <p class="text-lg font-bold text-red-600">¥{{ formatNumber(summary.expense) }}</p>
          </div>
          <div class="bg-blue-50 p-3 rounded-md">
            <p class="text-sm text-gray-600">结余</p>
            <p :class="['text-lg font-bold', summary.balance >= 0 ? 'text-blue-600' : 'text-red-600']">
              ¥{{ formatNumber(summary.balance) }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- 周期选择 -->
      <div class="flex border rounded-md overflow-hidden">
        <button 
          v-for="(item, index) in periods" 
          :key="index"
          @click="changePeriod(item.value)"
          :class="[
            'flex-1 py-2 text-center text-sm transition-colors',
            period === item.value 
              ? 'bg-indigo-600 text-white' 
              : 'bg-white text-gray-600 hover:bg-gray-100'
          ]"
        >
          {{ item.label }}
        </button>
      </div>
      
      <!-- 账户统计 -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold text-gray-700">账户资产</h3>
          <button @click="refreshData" class="text-indigo-600 text-sm hover:text-indigo-800">
            刷新
          </button>
        </div>
        
        <div v-if="loading" class="py-6 text-center text-gray-500">
          <p>加载中...</p>
        </div>
        
        <div v-else-if="accounts.length === 0" class="bg-gray-50 p-4 rounded-md text-center">
          <p class="text-gray-500">暂无账户信息</p>
          <button 
            @click="goToAccounts"
            class="mt-2 text-sm text-indigo-600 hover:text-indigo-800"
          >
            添加账户
          </button>
        </div>
        
        <div v-else class="space-y-2">
          <div 
            v-for="account in accounts" 
            :key="account.id" 
            class="bg-gray-50 p-3 rounded-md flex justify-between items-center"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2" :style="`background-color: ${account.color || '#e5e7eb'}`">
                <span class="text-white text-xs">{{ account.name.substring(0, 1) }}</span>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-700">{{ account.name }}</p>
                <p class="text-xs text-gray-500">{{ accountTypeText[account.type] || account.type }}</p>
              </div>
            </div>
            <p :class="['font-medium', account.amount >= 0 ? 'text-gray-800' : 'text-red-600']">
              ¥{{ formatNumber(account.amount) }}
            </p>
          </div>
          
          <div class="bg-gray-100 p-3 rounded-md flex justify-between items-center">
            <p class="text-sm font-medium text-gray-700">总资产</p>
            <p class="font-medium text-gray-800">
              ¥{{ formatNumber(totalBalance) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

interface Account {
  id: number;
  name: string;
  type: string;
  amount: number;
  color?: string;
}

interface Summary {
  income: number;
  expense: number;
  balance: number;
  period: {
    start_date: string;
    end_date: string;
  };
}

const loading = ref<boolean>(false);
const accounts = ref<Account[]>([]);
const totalBalance = ref<number>(0);
const summary = ref<Summary>({
  income: 0,
  expense: 0,
  balance: 0,
  period: {
    start_date: '',
    end_date: ''
  }
});

// 周期设置
const period = ref<string>('month');
const periods = [
  { label: '本月', value: 'month' },
  { label: '本年', value: 'year' },
  { label: '自定义', value: 'custom' }
];

// 账户类型文本映射
const accountTypeText = {
  'cash': '现金',
  'card': '银行卡',
  'credit': '信用卡',
  'virtual': '虚拟账户',
  'other': '其他'
};

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 统计周期文本
const periodText = computed(() => {
  if (!summary.value.period.start_date || !summary.value.period.end_date) {
    return '本月';
  }
  
  const startDate = new Date(summary.value.period.start_date);
  const endDate = new Date(summary.value.period.end_date);
  
  const formatDate = (date: Date) => {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  };
  
  // 如果是本月
  if (period.value === 'month') {
    const now = new Date();
    return `${now.getFullYear()}年${now.getMonth() + 1}月`;
  }
  
  // 如果是本年
  if (period.value === 'year') {
    const now = new Date();
    return `${now.getFullYear()}年`;
  }
  
  // 自定义周期
  return `${formatDate(startDate)} 至 ${formatDate(endDate)}`;
});

// 更改统计周期
const changePeriod = (newPeriod: string) => {
  period.value = newPeriod;
  loadSummary();
};

// 加载收支概览
const loadSummary = async () => {
  try {
    const response = await axios.get('/api/income-expense/summary', {
      params: {
        period: period.value,
      }
    });
    summary.value = response.data;
  } catch (error) {
    console.error('加载收支概览失败:', error);
  }
};

// 加载账户列表
const loadAccounts = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/balance');
    accounts.value = response.data;
    
    const balanceResponse = await axios.get('/api/balance/by-type');
    totalBalance.value = balanceResponse.data.total || 0;
  } catch (error) {
    console.error('加载账户信息失败:', error);
  } finally {
    loading.value = false;
  }
};

// 刷新数据
const refreshData = () => {
  loadSummary();
  loadAccounts();
};

// 跳转到账户管理页面
const goToAccounts = () => {
  router.push('/accounts');
};

onMounted(() => {
  refreshData();
});
</script>