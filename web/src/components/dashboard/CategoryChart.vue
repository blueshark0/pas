<template>
  <div class="bg-white p-6 rounded-lg shadow-md h-full">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold text-gray-800">支出分类</h2>
      <div class="flex items-center">
        <select 
          v-model="period" 
          class="text-sm border rounded-md px-2 py-1 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="month">本月</option>
          <option value="year">今年</option>
        </select>
      </div>
    </div>
    
    <div v-if="loading" class="py-10 text-center text-gray-500">
      <p>加载中...</p>
    </div>
    
    <div v-else-if="categories.length === 0" class="py-10 text-center text-gray-500">
      <p>暂无数据</p>
    </div>
    
    <div v-else class="space-y-4">
      <!-- 饼图 -->
      <div class="w-full h-60" ref="chartContainer"></div>
      
      <!-- 分类列表 -->
      <div class="space-y-2 max-h-48 overflow-y-auto">
        <div 
          v-for="(item, index) in categories" 
          :key="index" 
          class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50"
        >
          <div class="flex items-center">
            <div 
              class="w-3 h-3 rounded-full mr-2" 
              :style="`background-color: ${item.category_color || getColorByIndex(index)}`"
            ></div>
            <span class="text-sm text-gray-700">{{ item.category_name }}</span>
          </div>
          <div class="flex flex-col items-end">
            <span class="text-sm font-medium text-gray-800">¥{{ formatNumber(item.total) }}</span>
            <span class="text-xs text-gray-500">{{ formatPercentage(item.percentage) }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import * as echarts from 'echarts/core';
import { PieChart } from 'echarts/charts';
import { TitleComponent, TooltipComponent, LegendComponent } from 'echarts/components';
import { LabelLayout, UniversalTransition } from 'echarts/features';
import { CanvasRenderer } from 'echarts/renderers';

// 注册 ECharts 组件
echarts.use([
  PieChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  LabelLayout,
  UniversalTransition,
  CanvasRenderer
]);

interface CategoryData {
  category_id: number;
  category_name: string;
  category_color: string;
  category_icon: string;
  total: number;
  percentage: number;
}

const chartContainer = ref<HTMLElement | null>(null);
const chart = ref<echarts.ECharts | null>(null);
const loading = ref<boolean>(false);
const period = ref<string>('month');
const categories = ref<CategoryData[]>([]);
const totalAmount = ref<number>(0);

// 预定义颜色列表
const colors = [
  '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
  '#FF9F40', '#8AC054', '#EA7CCC', '#5D9CEC', '#F06292'
];

// 获取颜色
const getColorByIndex = (index: number): string => {
  return colors[index % colors.length];
};

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 格式化百分比
const formatPercentage = (percentage: number): string => {
  return percentage.toFixed(1);
};

// 加载分类统计数据
const loadCategoryStats = async () => {
  loading.value = true;
  
  try {
    // 根据选择的周期计算起止日期
    let startDate, endDate;
    const now = new Date();
    
    if (period.value === 'month') {
      // 本月
      startDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
      endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    } else {
      // 今年
      startDate = `${now.getFullYear()}-01-01`;
      endDate = `${now.getFullYear()}-12-31`;
    }
    
    const response = await axios.get('/api/income-expense/category-stats', {
      params: {
        start_date: startDate,
        end_date: endDate,
        type: 'expense' // 只统计支出
      }
    });
    
    // 按金额降序排序
    const sortedData = response.data.sort((a: CategoryData, b: CategoryData) => b.total - a.total);
    
    // 计算总金额
    totalAmount.value = sortedData.reduce((sum: number, item: CategoryData) => sum + item.total, 0);
    
    // 计算百分比
    categories.value = sortedData.map((item: CategoryData) => ({
      ...item,
      percentage: totalAmount.value > 0 ? (item.total / totalAmount.value) * 100 : 0
    }));
    
    // 更新图表
    nextTick(() => {
      updateChart();
    });
  } catch (error) {
    console.error('加载分类统计数据失败:', error);
  } finally {
    loading.value = false;
  }
};

// 更新图表
const updateChart = () => {
  if (!chartContainer.value) return;
  
  // 如果图表实例不存在，则创建
  if (!chart.value) {
    chart.value = echarts.init(chartContainer.value);
  }
  
  // 准备图表数据
  const chartData = categories.value.map((item, index) => ({
    name: item.category_name,
    value: item.total,
    itemStyle: {
      color: item.category_color || getColorByIndex(index)
    }
  }));
  
  // 设置图表选项
  const option = {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    series: [
      {
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 6,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false
        },
        emphasis: {
          label: {
            show: true,
            fontSize: '14',
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: chartData
      }
    ]
  };
  
  // 应用选项
  chart.value.setOption(option);
};

// 监听窗口大小变化，调整图表大小
const handleResize = () => {
  if (chart.value) {
    chart.value.resize();
  }
};

// 监听周期变化
watch(period, () => {
  loadCategoryStats();
});

onMounted(() => {
  loadCategoryStats();
  window.addEventListener('resize', handleResize);
});

// 组件卸载时清理
const onBeforeUnmount = () => {
  window.removeEventListener('resize', handleResize);
  if (chart.value) {
    chart.value.dispose();
    chart.value = null;
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