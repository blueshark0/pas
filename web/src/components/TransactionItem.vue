<template>
  <div class="transaction-item" @click="$emit('click', transaction)">
    <div class="transaction-icon">
      <el-icon v-if="transaction.type === 1" class="income-icon">
        <ArrowDown />
      </el-icon>
      <el-icon v-else class="expense-icon">
        <ArrowUp />
      </el-icon>
    </div>
    
    <div class="transaction-content">
      <div class="transaction-title">
        {{ transaction.description || (transaction.type === 1 ? '收入' : '支出') }}
        <span v-if="transaction.is_recurring" class="recurring-tag">
          <el-tag size="small" type="info">{{ getRecurrenceText(transaction.recurrence_type || 0, transaction.recurrence_interval || 1) }}</el-tag>
        </span>
      </div>
      <div class="transaction-date">{{ formatDate(transaction.execution_date) }}</div>
    </div>
    
    <div class="transaction-amount" :class="{ 'income': transaction.type === 1, 'expense': transaction.type === 2 }">
      {{ transaction.type === 1 ? '+' : '-' }}{{ formatAmount(transaction.amount) }}
    </div>
    
    <div class="transaction-status">
      <el-tag 
        :type="statusTagType" 
        size="small"
      >
        {{ statusText }}
      </el-tag>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ArrowUp, ArrowDown } from '@element-plus/icons-vue';
import { formatAmount, formatDate, getRecurrenceText } from '@/services/dateUtils';
import type { Transaction } from '@/stores/transaction';

interface Props {
  transaction: Transaction;
}

const props = defineProps<Props>();

defineEmits<{
  (e: 'click', transaction: Transaction): void
}>();

// 交易状态文本
const statusText = computed(() => {
  switch (props.transaction.status) {
    case 1: return '待执行';
    case 2: return '执行中';
    case 3: return '已执行';
    case 4: return '终止';
    default: return '未知';
  }
});

// 交易状态标签类型
const statusTagType = computed(() => {
  switch (props.transaction.status) {
    case 1: return 'warning';
    case 2: return 'info';
    case 3: return 'success';
    case 4: return 'danger';
    default: return '';
  }
});
</script>

<style scoped>
.transaction-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
  background-color: #fff;
  transition: background-color 0.3s;
  cursor: pointer;
}

.transaction-item:hover {
  background-color: #f9f9f9;
}

.transaction-icon {
  margin-right: 16px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.income-icon {
  color: #67c23a;
  font-size: 20px;
}

.expense-icon {
  color: #f56c6c;
  font-size: 20px;
}

.transaction-content {
  flex: 1;
  min-width: 0;
}

.transaction-title {
  font-size: 16px;
  color: #303133;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.recurring-tag {
  margin-left: 8px;
}

.transaction-date {
  font-size: 14px;
  color: #909399;
}

.transaction-amount {
  font-size: 16px;
  font-weight: bold;
  margin: 0 16px;
  white-space: nowrap;
}

.income {
  color: #67c23a;
}

.expense {
  color: #f56c6c;
}

.transaction-status {
  width: 60px;
  text-align: center;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .transaction-item {
    padding: 12px;
  }
  
  .transaction-icon {
    margin-right: 12px;
    width: 32px;
    height: 32px;
  }
  
  .income-icon, .expense-icon {
    font-size: 18px;
  }
  
  .transaction-title {
    font-size: 14px;
  }
  
  .transaction-date {
    font-size: 12px;
  }
  
  .transaction-amount {
    font-size: 14px;
    margin: 0 8px;
  }
}
</style>
