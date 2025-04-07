<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">统计分析</h1>
    
    <!-- 时间范围选择 -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
          <button
            v-for="(range, index) in timeRanges"
            :key="index"
            @click="selectTimeRange(range.value)"
            :class="[
              'px-4 py-2 rounded-md text-sm font-medium transition-colors',
              currentTimeRange === range.value
                ? 'bg-indigo-100 text-indigo-700 border border-indigo-300'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-transparent'
            ]"
          >
            {{ range.label }}
          </button>
        </div>
        
        <div class="flex items-center space-x-2">
          <div class="text-sm text-gray-600">自定义:</div>
          <input 
            type="date" 
            v-model="customDateRange.start"
            class="px-2 py-1 border rounded-md text-sm text-gray-700"
            :max="customDateRange.end"
          />
          <span class="text-gray-500">至</span>
          <input 
            type="date" 
            v-model="customDateRange.end"
            class="px-2 py-1 border rounded-md text-sm text-gray-700"
            :min="customDateRange.start"
          />
          <button
            @click="applyCustomRange"
            class="bg-indigo-600 text-white px-3 py-1 rounded-md text-sm hover:bg-indigo-700"
          >
            应用
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="loading" class="py-20 text-center text-gray-500">
      <p class="text-lg">正在加载数据...</p>
    </div>
    
    <div v-else>
      <!-- 收支概览卡片 -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-500 mb-1">总收入</p>
          <p class="text-2xl font-semibold text-green-600">¥{{ formatNumber(summary.totalIncome) }}</p>
          <p class="text-xs text-gray-500 mt-2">较上期
            <span 
              :class="incomeChangePercent >= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ incomeChangePercent >= 0 ? '+' : '' }}{{ incomeChangePercent }}%
            </span>
          </p>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-500 mb-1">总支出</p>
          <p class="text-2xl font-semibold text-red-600">¥{{ formatNumber(summary.totalExpense) }}</p>
          <p class="text-xs text-gray-500 mt-2">较上期
            <span 
              :class="expenseChangePercent <= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ expenseChangePercent >= 0 ? '+' : '' }}{{ expenseChangePercent }}%
            </span>
          </p>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-500 mb-1">结余</p>
          <p 
            class="text-2xl font-semibold"
            :class="summary.balance >= 0 ? 'text-green-600' : 'text-red-600'"
          >
            ¥{{ formatNumber(summary.balance) }}
          </p>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-500 mb-1">平均日支出</p>
          <p class="text-2xl font-semibold text-gray-800">¥{{ formatNumber(summary.avgDailyExpense) }}</p>
        </div>
      </div>
      
      <!-- 收支趋势图 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">收支趋势</h2>
          <div class="h-60">
            <canvas ref="trendChart"></canvas>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">收支比例</h2>
          <div class="h-60">
            <canvas ref="ratioChart"></canvas>
          </div>
        </div>
      </div>
      
      <!-- 分类统计 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">支出分类</h2>
            <select 
              v-model="expenseCategoryViewType" 
              class="text-sm border rounded-md px-2 py-1"
            >
              <option value="amount">按金额</option>
              <option value="percent">按百分比</option>
            </select>
          </div>
          <div class="h-64">
            <canvas ref="expenseCategoryChart"></canvas>
          </div>
          <div class="mt-4 space-y-2">
            <div 
              v-for="(category, index) in topExpenseCategories" 
              :key="index"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <div 
                  class="w-3 h-3 rounded-full mr-2" 
                  :style="`background-color: ${getCategoryColor(index)}`"
                ></div>
                <span class="text-sm">{{ category.name }}</span>
              </div>
              <div class="text-sm">
                <span class="font-medium">¥{{ formatNumber(category.amount) }}</span>
                <span class="text-gray-500 ml-1">({{ Math.round(category.percentage) }}%)</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">收入来源</h2>
            <select 
              v-model="incomeCategoryViewType" 
              class="text-sm border rounded-md px-2 py-1"
            >
              <option value="amount">按金额</option>
              <option value="percent">按百分比</option>
            </select>
          </div>
          <div class="h-64">
            <canvas ref="incomeCategoryChart"></canvas>
          </div>
          <div class="mt-4 space-y-2">
            <div 
              v-for="(category, index) in topIncomeCategories" 
              :key="index"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <div 
                  class="w-3 h-3 rounded-full mr-2" 
                  :style="`background-color: ${getCategoryColor(index)}`"
                ></div>
                <span class="text-sm">{{ category.name }}</span>
              </div>
              <div class="text-sm">
                <span class="font-medium">¥{{ formatNumber(category.amount) }}</span>
                <span class="text-gray-500 ml-1">({{ Math.round(category.percentage) }}%)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 账户分布 -->
      <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">账户资产分布</h2>
        <div class="h-60">
          <canvas ref="accountDistributionChart"></canvas>
        </div>
      </div>
      
      <!-- 支出排行 -->
      <div class="bg-white p-4 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">支出排行</h2>
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
              <tr v-for="transaction in topExpenseTransactions" :key="transaction.id" class="hover:bg-gray-50">
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
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

interface SummaryData {
  totalIncome: number;
  totalExpense: number;
  balance: number;
  avgDailyExpense: number;
  prevTotalIncome: number;
  prevTotalExpense: number;
}

interface CategoryAmount {
  id: number;
  name: string;
  amount: number;
  percentage: number;
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

interface TrendData {
  labels: string[];
  income: number[];
  expense: number[];
}

interface AccountDistribution {
  name: string;
  amount: number;
  color: string;
}

// 图表实例
const trendChart = ref<Chart | null>(null);
const ratioChart = ref<Chart | null>(null);
const expenseCategoryChart = ref<Chart | null>(null);
const incomeCategoryChart = ref<Chart | null>(null);
const accountDistributionChart = ref<Chart | null>(null);

// canvas 元素引用
const trendChartRef = ref<HTMLCanvasElement | null>(null);
const ratioChartRef = ref<HTMLCanvasElement | null>(null);
const expenseCategoryChartRef = ref<HTMLCanvasElement | null>(null);
const incomeCategoryChartRef = ref<HTMLCanvasElement | null>(null);
const accountDistributionChartRef = ref<HTMLCanvasElement | null>(null);

// 状态
const loading = ref<boolean>(true);
const summary = reactive<SummaryData>({
  totalIncome: 0,
  totalExpense: 0,
  balance: 0,
  avgDailyExpense: 0,
  prevTotalIncome: 0,
  prevTotalExpense: 0
});
const expenseCategories = ref<CategoryAmount[]>([]);
const incomeCategories = ref<CategoryAmount[]>([]);
const topExpenseTransactions = ref<Transaction[]>([]);
const accountsDistribution = ref<AccountDistribution[]>([]);
const trendData = ref<TrendData>({
  labels: [],
  income: [],
  expense: []
});

// 预定义图表颜色
const chartColors = [
  '#4299e1', // 蓝色
  '#48bb78', // 绿色
  '#ed8936', // 橙色
  '#9f7aea', // 紫色
  '#f56565', // 红色
  '#38b2ac', // 青色
  '#667eea', // 靛蓝色
  '#d53f8c', // 粉色
  '#805ad5', // 深紫色
  '#dd6b20', // 深橙色
  '#3182ce', // 深蓝色
  '#2c7a7b', // 深青色
];

// 切片选项
const expenseCategoryViewType = ref<string>('amount');
const incomeCategoryViewType = ref<string>('amount');

// 时间范围设置
const timeRanges = [
  { label: '本月', value: 'current_month' },
  { label: '上月', value: 'last_month' },
  { label: '过去7天', value: 'last_7_days' },
  { label: '过去30天', value: 'last_30_days' },
  { label: '过去90天', value: 'last_90_days' },
  { label: '今年', value: 'current_year' },
];

const currentTimeRange = ref<string>('current_month');
const customDateRange = reactive({
  start: '',
  end: ''
});

// 计算收入变化百分比
const incomeChangePercent = computed(() => {
  if (summary.prevTotalIncome === 0) return 0;
  const change = ((summary.totalIncome - summary.prevTotalIncome) / summary.prevTotalIncome) * 100;
  return change.toFixed(1);
});

// 计算支出变化百分比
const expenseChangePercent = computed(() => {
  if (summary.prevTotalExpense === 0) return 0;
  const change = ((summary.totalExpense - summary.prevTotalExpense) / summary.prevTotalExpense) * 100;
  return change.toFixed(1);
});

// 前5个支出分类
const topExpenseCategories = computed(() => {
  return expenseCategories.value.slice(0, 5);
});

// 前5个收入分类
const topIncomeCategories = computed(() => {
  return incomeCategories.value.slice(0, 5);
});

// 获取分类颜色
const getCategoryColor = (index: number): string => {
  return chartColors[index % chartColors.length];
};

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 格式化日期
const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

// 选择时间范围
const selectTimeRange = (range: string) => {
  currentTimeRange.value = range;
  loadStatistics();
};

// 应用自定义时间范围
const applyCustomRange = () => {
  if (customDateRange.start && customDateRange.end) {
    currentTimeRange.value = 'custom';
    loadStatistics();
  }
};

// 加载统计数据
const loadStatistics = async () => {
  loading.value = true;
  
  try {
    // 构建请求参数
    let params: Record<string, string> = { range: currentTimeRange.value };
    
    // 如果是自定义范围
    if (currentTimeRange.value === 'custom') {
      params.start_date = customDateRange.start;
      params.end_date = customDateRange.end;
    }
    
    // 获取统计总览
    const summaryResponse = await axios.get('/api/statistics/summary', { params });
    Object.assign(summary, summaryResponse.data);
    
    // 获取支出分类统计
    const expenseCategoriesResponse = await axios.get('/api/statistics/expense-categories', { params });
    expenseCategories.value = expenseCategoriesResponse.data;
    
    // 获取收入分类统计
    const incomeCategoriesResponse = await axios.get('/api/statistics/income-categories', { params });
    incomeCategories.value = incomeCategoriesResponse.data;
    
    // 获取支出排行
    const topExpensesResponse = await axios.get('/api/statistics/top-expenses', { params });
    topExpenseTransactions.value = topExpensesResponse.data;
    
    // 获取账户分布
    const accountsResponse = await axios.get('/api/statistics/accounts-distribution');
    accountsDistribution.value = accountsResponse.data;
    
    // 获取收支趋势
    const trendResponse = await axios.get('/api/statistics/trend', { params });
    trendData.value = trendResponse.data;
    
    // 更新图表
    updateCharts();
  } catch (error) {
    console.error('加载统计数据失败:', error);
  } finally {
    loading.value = false;
  }
};

// 更新所有图表
const updateCharts = () => {
  updateTrendChart();
  updateRatioChart();
  updateExpenseCategoryChart();
  updateIncomeCategoryChart();
  updateAccountDistributionChart();
};

// 更新收支趋势图
const updateTrendChart = () => {
  if (!trendChartRef.value) return;
  
  if (trendChart.value) {
    trendChart.value.destroy();
  }
  
  trendChart.value = new Chart(trendChartRef.value, {
    type: 'line',
    data: {
      labels: trendData.value.labels,
      datasets: [
        {
          label: '收入',
          data: trendData.value.income,
          borderColor: '#48bb78',
          backgroundColor: 'rgba(72, 187, 120, 0.1)',
          tension: 0.3,
          fill: true
        },
        {
          label: '支出',
          data: trendData.value.expense,
          borderColor: '#f56565',
          backgroundColor: 'rgba(245, 101, 101, 0.1)',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              label += '¥' + formatNumber(Number(context.parsed.y));
              return label;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '¥' + value;
            }
          }
        }
      }
    }
  });
};

// 更新收支比例图
const updateRatioChart = () => {
  if (!ratioChartRef.value) return;
  
  if (ratioChart.value) {
    ratioChart.value.destroy();
  }
  
  ratioChart.value = new Chart(ratioChartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['收入', '支出'],
      datasets: [{
        data: [summary.totalIncome, summary.totalExpense],
        backgroundColor: ['#48bb78', '#f56565'],
        hoverBackgroundColor: ['#38a169', '#e53e3e']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = '¥' + formatNumber(Number(context.parsed));
              const percentage = Math.round((Number(context.parsed) / (summary.totalIncome + summary.totalExpense)) * 100);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
};

// 更新支出分类图
const updateExpenseCategoryChart = () => {
  if (!expenseCategoryChartRef.value) return;
  
  if (expenseCategoryChart.value) {
    expenseCategoryChart.value.destroy();
  }
  
  const topCategories = topExpenseCategories.value;
  const data = expenseCategoryViewType.value === 'amount' 
    ? topCategories.map(c => c.amount)
    : topCategories.map(c => c.percentage);
  
  expenseCategoryChart.value = new Chart(expenseCategoryChartRef.value, {
    type: 'pie',
    data: {
      labels: topCategories.map(c => c.name),
      datasets: [{
        data: data,
        backgroundColor: topCategories.map((_, i) => getCategoryColor(i)),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              if (expenseCategoryViewType.value === 'amount') {
                return `${label}: ¥${formatNumber(context.parsed)}`;
              } else {
                return `${label}: ${Math.round(context.parsed)}%`;
              }
            }
          }
        }
      }
    }
  });
};

// 更新收入分类图
const updateIncomeCategoryChart = () => {
  if (!incomeCategoryChartRef.value) return;
  
  if (incomeCategoryChart.value) {
    incomeCategoryChart.value.destroy();
  }
  
  const topCategories = topIncomeCategories.value;
  const data = incomeCategoryViewType.value === 'amount' 
    ? topCategories.map(c => c.amount)
    : topCategories.map(c => c.percentage);
  
  incomeCategoryChart.value = new Chart(incomeCategoryChartRef.value, {
    type: 'pie',
    data: {
      labels: topCategories.map(c => c.name),
      datasets: [{
        data: data,
        backgroundColor: topCategories.map((_, i) => getCategoryColor(i)),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              if (incomeCategoryViewType.value === 'amount') {
                return `${label}: ¥${formatNumber(context.parsed)}`;
              } else {
                return `${label}: ${Math.round(context.parsed)}%`;
              }
            }
          }
        }
      }
    }
  });
};

// 更新账户分布图
const updateAccountDistributionChart = () => {
  if (!accountDistributionChartRef.value) return;
  
  if (accountDistributionChart.value) {
    accountDistributionChart.value.destroy();
  }
  
  accountDistributionChart.value = new Chart(accountDistributionChartRef.value, {
    type: 'bar',
    data: {
      labels: accountsDistribution.value.map(a => a.name),
      datasets: [{
        label: '账户余额',
        data: accountsDistribution.value.map(a => a.amount),
        backgroundColor: accountsDistribution.value.map(a => a.color),
        barThickness: 30,
        maxBarThickness: 40
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              return `余额: ¥${formatNumber(context.parsed.y)}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '¥' + value;
            }
          }
        }
      }
    }
  });
};

// 监听分类视图类型
watch([expenseCategoryViewType], () => {
  updateExpenseCategoryChart();
});

watch([incomeCategoryViewType], () => {
  updateIncomeCategoryChart();
});

// 组件挂载时加载数据
onMounted(() => {
  // 设置默认日期范围
  const now = new Date();
  const lastMonth = new Date(now);
  lastMonth.setMonth(lastMonth.getMonth() - 1);
  
  customDateRange.end = now.toISOString().split('T')[0];
  customDateRange.start = lastMonth.toISOString().split('T')[0];
  
  // 获取canvas引用
  trendChartRef.value = document.querySelector('canvas[ref="trendChart"]');
  ratioChartRef.value = document.querySelector('canvas[ref="ratioChart"]');
  expenseCategoryChartRef.value = document.querySelector('canvas[ref="expenseCategoryChart"]');
  incomeCategoryChartRef.value = document.querySelector('canvas[ref="incomeCategoryChart"]');
  accountDistributionChartRef.value = document.querySelector('canvas[ref="accountDistributionChart"]');
  
  // 加载数据
  loadStatistics();
});

// 在组件卸载时销毁图表实例
onUnmounted(() => {
  if (trendChart.value) trendChart.value.destroy();
  if (ratioChart.value) ratioChart.value.destroy();
  if (expenseCategoryChart.value) expenseCategoryChart.value.destroy();
  if (incomeCategoryChart.value) incomeCategoryChart.value.destroy();
  if (accountDistributionChart.value) accountDistributionChart.value.destroy();
});
</script>