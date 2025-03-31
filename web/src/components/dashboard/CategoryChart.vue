<!-- 收支类别分析图表组件 -->
<template>
  <div class="category-chart bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-bold text-gray-800 mb-4">支出分类分析</h3>
    
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
          <option value="month">本月</option>
          <option value="quarter">本季度</option>
          <option value="year">本年</option>
        </select>
      </div>
      <div class="text-gray-500">
        <span>类型: </span>
        <select v-model="type" @change="fetchData" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md px-2 py-1">
          <option value="expense">支出</option>
          <option value="income">收入</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

// 注册 Chart.js 组件
Chart.register(...registerables);

interface CategoryData {
  category: string;
  amount: number;
}

// 状态
const loading = ref<boolean>(false);
const chartRef = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart | null>(null);
const period = ref<string>('month');
const type = ref<string>('expense');
const chartData = ref<CategoryData[]>([]);
const noData = ref<boolean>(false);

// 获取图表数据
const fetchData = async (): Promise<void> => {
  loading.value = true;
  noData.value = false;
  
  try {
    // 计算日期范围
    const dates = getDateRangeByPeriod(period.value);
    
    const response = await axios.get('/api/income-expense/category-statistics', {
      params: {
        start_date: dates.start,
        end_date: dates.end,
        type: type.value
      }
    });
    
    chartData.value = response.data || [];
    noData.value = chartData.value.length === 0;
    
    if (!noData.value) {
      renderChart();
    }
  } catch (error) {
    console.error('获取分类统计数据失败:', error);
    noData.value = true;
  } finally {
    loading.value = false;
  }
};

// 根据周期获取日期范围
const getDateRangeByPeriod = (periodValue: string): { start: string; end: string } => {
  const today = new Date();
  let startDate = new Date();
  const endDate = new Date(today);
  
  if (periodValue === 'month') {
    // 本月
    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
  } else if (periodValue === 'quarter') {
    // 本季度
    const quarterMonth = Math.floor(today.getMonth() / 3) * 3;
    startDate = new Date(today.getFullYear(), quarterMonth, 1);
  } else if (periodValue === 'year') {
    // 本年
    startDate = new Date(today.getFullYear(), 0, 1);
  }
  
  return {
    start: startDate.toISOString().substr(0, 10),
    end: endDate.toISOString().substr(0, 10)
  };
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
  const labels = chartData.value.map(item => item.category);
  const data = chartData.value.map(item => item.amount);
  
  // 颜色设置
  const backgroundColors = [
    'rgba(54, 162, 235, 0.8)',
    'rgba(255, 99, 132, 0.8)',
    'rgba(255, 206, 86, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(153, 102, 255, 0.8)',
    'rgba(255, 159, 64, 0.8)',
    'rgba(199, 199, 199, 0.8)',
  ];
  
  // 创建图表
  chart.value = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: backgroundColors.slice(0, data.length),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            boxWidth: 15,
            padding: 10,
            font: {
              size: 12
            }
          }
        },
        tooltip: {
          callbacks: {
            label: (tooltipItem: any) => {
              const value = tooltipItem.raw;
              const totalValue = data.reduce((a, b) => a + b, 0);
              const percentage = ((value / totalValue) * 100).toFixed(1);
              return `${tooltipItem.label}: ¥${value.toLocaleString()} (${percentage}%)`;
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
const onUnmounted = (): void => {
  window.removeEventListener('resize', resizeChart);
  if (chart.value) {
    chart.value.destroy();
  }
};
</script>

<style scoped>
/* 适配移动设备 */
@media (max-width: 640px) {
  .category-chart {
    padding: 1rem;
  }
}
</style>