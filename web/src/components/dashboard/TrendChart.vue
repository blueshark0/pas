<!-- 收支趋势图表组件 -->
<template>
  <div class="trend-chart bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-bold text-gray-800 mb-4">收支趋势分析</h3>
    
    <div v-if="loading" class="flex justify-center items-center py-10">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-500"></div>
    </div>
    
    <div v-else-if="noData" class="flex justify-center items-center py-10 text-gray-500">
      暂无数据
    </div>
    
    <div v-else class="relative" style="height: 250px;">
      <canvas ref="chartRef"></canvas>
    </div>
    
    <div class="mt-4 flex justify-between text-sm">
      <div class="text-gray-500">
        <span>统计周期: </span>
        <select v-model="period" @change="fetchData" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md px-2 py-1">
          <option value="week">近一周</option>
          <option value="month">近一月</option>
          <option value="quarter">近一季</option>
          <option value="year">近一年</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

// 注册 Chart.js 组件
Chart.register(...registerables);

interface TrendData {
  date: string;
  income: number;
  expense: number;
}

// 状态
const loading = ref<boolean>(false);
const chartRef = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart | null>(null);
const period = ref<string>('month');
const trendData = ref<TrendData[]>([]);
const noData = ref<boolean>(false);

// 获取图表数据
const fetchData = async (): Promise<void> => {
  loading.value = true;
  noData.value = false;
  
  try {
    // 计算日期范围
    const dates = getDateRangeByPeriod(period.value);
    
    const response = await axios.get('/api/income-expense/trend-statistics', {
      params: {
        start_date: dates.start,
        end_date: dates.end,
        interval: getIntervalByPeriod(period.value)
      }
    });
    
    trendData.value = response.data || [];
    noData.value = trendData.value.length === 0;
    
    if (!noData.value) {
      renderChart();
    }
  } catch (error) {
    console.error('获取趋势统计数据失败:', error);
    noData.value = true;
  } finally {
    loading.value = false;
  }
};

// 根据周期获取日期范围
const getDateRangeByPeriod = (periodValue: string): { start: string; end: string } => {
  const today = new Date();
  let startDate = new Date();
  
  if (periodValue === 'week') {
    // 近一周
    startDate = new Date(today);
    startDate.setDate(today.getDate() - 7);
  } else if (periodValue === 'month') {
    // 近一月
    startDate = new Date(today);
    startDate.setMonth(today.getMonth() - 1);
  } else if (periodValue === 'quarter') {
    // 近一季度
    startDate = new Date(today);
    startDate.setMonth(today.getMonth() - 3);
  } else if (periodValue === 'year') {
    // 近一年
    startDate = new Date(today);
    startDate.setFullYear(today.getFullYear() - 1);
  }
  
  return {
    start: startDate.toISOString().substr(0, 10),
    end: today.toISOString().substr(0, 10)
  };
};

// 根据周期获取日期间隔类型
const getIntervalByPeriod = (periodValue: string): string => {
  if (periodValue === 'week') {
    return 'day';
  } else if (periodValue === 'month') {
    return 'day';
  } else if (periodValue === 'quarter') {
    return 'week';
  } else { // year
    return 'month';
  }
};

// 格式化日期标签
const formatDateLabel = (date: string, interval: string): string => {
  const dateObj = new Date(date);
  
  if (interval === 'day') {
    return `${dateObj.getMonth() + 1}/${dateObj.getDate()}`;
  } else if (interval === 'week') {
    return `${dateObj.getMonth() + 1}月第${Math.ceil(dateObj.getDate() / 7)}周`;
  } else if (interval === 'month') {
    return `${dateObj.getMonth() + 1}月`;
  }
  
  return date;
};

// 渲染图表
const renderChart = (): void => {
  if (!chartRef.value) return;
  
  // 如果图表已存在，销毁它
  if (chart.value) {
    chart.value.destroy();
  }
  
  const ctx = chartRef.value.getContext('2d');
  if (!ctx) return;
  
  // 准备图表数据
  const interval = getIntervalByPeriod(period.value);
  const labels = trendData.value.map(item => formatDateLabel(item.date, interval));
  const incomeData = trendData.value.map(item => item.income);
  const expenseData = trendData.value.map(item => item.expense);
  
  // 创建图表
  chart.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: '收入',
          data: incomeData,
          borderColor: 'rgba(54, 162, 235, 1)',
          backgroundColor: 'rgba(54, 162, 235, 0.1)',
          borderWidth: 2,
          tension: 0.3,
          fill: true
        },
        {
          label: '支出',
          data: expenseData,
          borderColor: 'rgba(255, 99, 132, 1)',
          backgroundColor: 'rgba(255, 99, 132, 0.1)',
          borderWidth: 2,
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          grid: {
            display: false
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => {
              return '¥' + value;
            }
          }
        }
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.dataset.label || '';
              const value = context.parsed.y;
              return `${label}: ¥${value.toLocaleString()}`;
            }
          }
        }
      }
    }
  });
};

// 监听图表容器大小变化
const resizeChart = (): void => {
  if (chart.value) {
    chart.value.resize();
  }
};

// 组件挂载时获取数据
onMounted(() => {
  fetchData();
  window.addEventListener('resize', resizeChart);
});

// 组件卸载时移除事件监听
onUnmounted(() => {
  window.removeEventListener('resize', resizeChart);
  if (chart.value) {
    chart.value.destroy();
  }
});
</script>

<style scoped>
/* 适配移动设备 */
@media (max-width: 640px) {
  .trend-chart {
    padding: 1rem;
  }
}
</style>