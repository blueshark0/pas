<template>
  <div class="bg-white p-6 rounded-lg shadow-md h-full">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold text-gray-800">收支趋势</h2>
      <div class="flex space-x-2">
        <select 
          v-model="period" 
          class="text-sm border rounded-md px-2 py-1 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="month">本月</option>
          <option value="year">今年</option>
        </select>
        <select 
          v-model="groupBy" 
          class="text-sm border rounded-md px-2 py-1 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="day">按日</option>
          <option value="month">按月</option>
        </select>
      </div>
    </div>
    
    <div v-if="loading" class="py-10 text-center text-gray-500">
      <p>加载中...</p>
    </div>
    
    <div v-else-if="incomeData.length === 0 && expenseData.length === 0" class="py-10 text-center text-gray-500">
      <p>暂无数据</p>
    </div>
    
    <div v-else>
      <!-- 趋势图 -->
      <div class="w-full h-64" ref="chartContainer"></div>
      
      <!-- 统计总额 -->
      <div class="flex justify-between mt-4 px-6 py-3 bg-gray-50 rounded-lg">
        <div>
          <p class="text-sm text-gray-500">总收入</p>
          <p class="text-lg font-semibold text-green-600">¥{{ formatNumber(totalIncome) }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">总支出</p>
          <p class="text-lg font-semibold text-red-600">¥{{ formatNumber(totalExpense) }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">结余</p>
          <p :class="['text-lg font-semibold', balance >= 0 ? 'text-blue-600' : 'text-red-600']">
            ¥{{ formatNumber(balance) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import * as echarts from 'echarts/core';
import { LineChart } from 'echarts/charts';
import { 
  TitleComponent, 
  TooltipComponent, 
  LegendComponent, 
  GridComponent, 
  DataZoomComponent 
} from 'echarts/components';
import { UniversalTransition } from 'echarts/features';
import { CanvasRenderer } from 'echarts/renderers';

// 注册 ECharts 组件
echarts.use([
  LineChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
  DataZoomComponent,
  UniversalTransition,
  CanvasRenderer
]);

interface TrendData {
  date_group: string;
  total: number;
}

const chartContainer = ref<HTMLElement | null>(null);
const chart = ref<echarts.ECharts | null>(null);
const loading = ref<boolean>(false);
const period = ref<string>('month');
const groupBy = ref<string>('day');
const incomeData = ref<TrendData[]>([]);
const expenseData = ref<TrendData[]>([]);

// 计算总收入、总支出和结余
const totalIncome = computed(() => 
  incomeData.value.reduce((sum, item) => sum + item.total, 0)
);

const totalExpense = computed(() => 
  expenseData.value.reduce((sum, item) => sum + item.total, 0)
);

const balance = computed(() => 
  totalIncome.value - totalExpense.value
);

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 加载趋势数据
const loadTrendData = async () => {
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
    
    // 加载收入趋势
    const incomeResponse = await axios.get('/api/income-expense/trend-stats', {
      params: {
        start_date: startDate,
        end_date: endDate,
        group_by: groupBy.value,
        type: 'income'
      }
    });
    incomeData.value = incomeResponse.data;
    
    // 加载支出趋势
    const expenseResponse = await axios.get('/api/income-expense/trend-stats', {
      params: {
        start_date: startDate,
        end_date: endDate,
        group_by: groupBy.value,
        type: 'expense'
      }
    });
    expenseData.value = expenseResponse.data;
    
    // 更新图表
    nextTick(() => {
      updateChart();
    });
  } catch (error) {
    console.error('加载趋势数据失败:', error);
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
  
  // 合并日期，确保日期连续
  const allDates = new Set();
  incomeData.value.forEach(item => allDates.add(item.date_group));
  expenseData.value.forEach(item => allDates.add(item.date_group));
  
  const sortedDates = Array.from(allDates).sort();
  
  // 准备收入数据
  const incomeMap = new Map();
  incomeData.value.forEach(item => {
    incomeMap.set(item.date_group, item.total);
  });
  
  const incomeValues = sortedDates.map(date => incomeMap.get(date) || 0);
  
  // 准备支出数据
  const expenseMap = new Map();
  expenseData.value.forEach(item => {
    expenseMap.set(item.date_group, item.total);
  });
  
  const expenseValues = sortedDates.map(date => expenseMap.get(date) || 0);
  
  // 设置图表选项
  const option = {
    tooltip: {
      trigger: 'axis',
      formatter: function (params: any) {
        let result = params[0].axisValue + '<br/>';
        params.forEach((item: any) => {
          const color = item.seriesName === '收入' ? '#10B981' : '#EF4444';
          const value = item.value.toLocaleString('zh-CN', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
          });
          result += `<span style="display:inline-block;margin-right:5px;border-radius:50%;width:10px;height:10px;background-color:${color};"></span>`;
          result += `${item.seriesName}: ¥${value}<br/>`;
        });
        return result;
      }
    },
    legend: {
      data: ['收入', '支出'],
      bottom: 0
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '12%',
      top: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: sortedDates,
      axisLabel: {
        formatter: (value: string) => {
          if (groupBy.value === 'day') {
            // 如果是按日分组，显示日期中的日
            return value.split('-')[2];
          } else {
            // 如果是按月分组，显示月份
            const parts = value.split('-');
            return `${parts[1]}月`;
          }
        }
      }
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        formatter: (value: number) => {
          if (value >= 1000) {
            return (value / 1000) + 'k';
          }
          return value;
        }
      }
    },
    series: [
      {
        name: '收入',
        type: 'line',
        data: incomeValues,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        itemStyle: {
          color: '#10B981' // 绿色
        },
        lineStyle: {
          width: 3
        },
        areaStyle: {
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(16, 185, 129, 0.3)' },
              { offset: 1, color: 'rgba(16, 185, 129, 0.1)' }
            ]
          }
        }
      },
      {
        name: '支出',
        type: 'line',
        data: expenseValues,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        itemStyle: {
          color: '#EF4444' // 红色
        },
        lineStyle: {
          width: 3
        },
        areaStyle: {
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(239, 68, 68, 0.3)' },
              { offset: 1, color: 'rgba(239, 68, 68, 0.1)' }
            ]
          }
        }
      }
    ]
  };
  
  // 如果数据点过多，添加缩放功能
  if (sortedDates.length > 10) {
    option.dataZoom = [
      {
        type: 'inside',
        start: 0,
        end: 100
      },
      {
        start: 0,
        end: 100
      }
    ];
  }
  
  // 应用选项
  chart.value.setOption(option);
};

// 监听窗口大小变化，调整图表大小
const handleResize = () => {
  if (chart.value) {
    chart.value.resize();
  }
};

// 监听周期和分组方式变化
watch([period, groupBy], () => {
  loadTrendData();
});

onMounted(() => {
  loadTrendData();
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