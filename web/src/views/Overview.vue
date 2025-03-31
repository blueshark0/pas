<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">个人记账系统</h1>
    
    <!-- 主要内容区域 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 左侧：快速添加和统计信息 -->
      <div class="lg:col-span-1 space-y-6">
        <!-- 快速添加按钮 -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <button 
            @click="showAddForm = true"
            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 flex items-center justify-center"
          >
            <span class="text-xl mr-2">+</span> 快速记账
          </button>
        </div>
        
        <!-- 财务概览组件 -->
        <FinanceSummary />
      </div>
      
      <!-- 右侧：图表和记录 -->
      <div class="lg:col-span-2 space-y-6">
        <!-- 统计图表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <CategoryChart />
          <TrendChart />
        </div>
        
        <!-- 收支记录表 -->
        <TransactionsList @edit-transaction="handleEditTransaction" />
      </div>
    </div>
    
    <!-- 添加/编辑记录表单弹窗 -->
    <div v-if="showAddForm" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="relative bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
        <div class="sticky top-0 bg-white p-4 border-b flex justify-between items-center">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ editMode ? '编辑收支记录' : '添加收支记录' }}
          </h2>
          <button @click="closeForm" class="text-gray-500 hover:text-gray-700">
            <span class="text-2xl">&times;</span>
          </button>
        </div>
        
        <div class="p-6">
          <IncomeExpenseForm 
            :edit-data="currentTransaction" 
            @save-success="handleSaveSuccess" 
            @cancel="closeForm"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import FinanceSummary from '@/components/dashboard/FinanceSummary.vue';
import CategoryChart from '@/components/dashboard/CategoryChart.vue';
import TrendChart from '@/components/dashboard/TrendChart.vue';
import TransactionsList from '@/components/dashboard/TransactionsList.vue';
import IncomeExpenseForm from '@/components/IncomeExpenseForm.vue';

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
  installment_info?: Record<string, any>;
  account_id: number;
  status?: string;
}

// 表单显示状态
const showAddForm = ref<boolean>(false);
const editMode = ref<boolean>(false);
const currentTransaction = ref<Transaction | null>(null);

// 处理编辑交易记录
const handleEditTransaction = (transaction: Transaction): void => {
  currentTransaction.value = transaction;
  editMode.value = true;
  showAddForm.value = true;
};

// 处理保存成功
const handleSaveSuccess = (): void => {
  closeForm();
  // 如果需要，这里可以添加刷新数据的逻辑
};

// 关闭表单
const closeForm = (): void => {
  showAddForm.value = false;
  editMode.value = false;
  currentTransaction.value = null;
};
</script>

<style scoped>
/* 媒体查询，确保在移动设备上有良好体验 */
@media (max-width: 768px) {
  .container {
    padding: 0.5rem;
  }
}
</style>
