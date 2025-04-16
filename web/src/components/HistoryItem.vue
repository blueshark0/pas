<template>
  <div class="history-item">
    <div class="history-icon" :class="[changeTypeClass]">
      <el-icon v-if="history.change_type === 1"><Setting /></el-icon>
      <el-icon v-else-if="history.change_type === 2"><ArrowDown /></el-icon>
      <el-icon v-else-if="history.change_type === 3"><ArrowUp /></el-icon>
      <el-icon v-else><Edit /></el-icon>
    </div>
    
    <div class="history-content">
      <div class="history-time">{{ formatDateTime(history.timestamp) }}</div>
      <div class="history-type">{{ changeTypeText }}</div>
      <div class="history-detail">
        <div class="amount-change" :class="{ 'positive': history.amount_change > 0, 'negative': history.amount_change < 0 }">
          {{ history.amount_change > 0 ? '+' : '' }}{{ formatAmount(history.amount_change) }}
        </div>
        <div class="balance-after">
          余额: {{ formatAmount(history.balance_after) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Setting, ArrowUp, ArrowDown, Edit } from '@element-plus/icons-vue';
import { formatAmount, formatDateTime } from '@/services/dateUtils';
import type { BalanceHistory } from '@/stores/history';

interface Props {
  history: BalanceHistory;
}

const props = defineProps<Props>();

// 变更类型文本
const changeTypeText = computed(() => {
  switch (props.history.change_type) {
    case 1: return '初始设置';
    case 2: return '收入执行';
    case 3: return '支出执行';
    case 4: return '手动编辑';
    default: return '未知变更';
  }
});

// 变更类型样式类
const changeTypeClass = computed(() => {
  switch (props.history.change_type) {
    case 1: return 'icon-init';
    case 2: return 'icon-income';
    case 3: return 'icon-expense';
    case 4: return 'icon-manual';
    default: return '';
  }
});
</script>

<style scoped>
.history-item {
  display: flex;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
  background-color: #fff;
}

.history-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
  flex-shrink: 0;
}

.icon-init {
  background-color: #909399;
  color: #fff;
}

.icon-income {
  background-color: #67c23a;
  color: #fff;
}

.icon-expense {
  background-color: #f56c6c;
  color: #fff;
}

.icon-manual {
  background-color: #409eff;
  color: #fff;
}

.history-content {
  flex: 1;
  min-width: 0;
}

.history-time {
  font-size: 14px;
  color: #909399;
  margin-bottom: 4px;
}

.history-type {
  font-size: 16px;
  color: #303133;
  font-weight: bold;
  margin-bottom: 8px;
}

.history-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-change {
  font-size: 16px;
  font-weight: bold;
}

.positive {
  color: #67c23a;
}

.negative {
  color: #f56c6c;
}

.balance-after {
  font-size: 14px;
  color: #606266;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .history-item {
    padding: 12px;
  }
  
  .history-icon {
    width: 32px;
    height: 32px;
    margin-right: 12px;
  }
  
  .history-time {
    font-size: 12px;
  }
  
  .history-type {
    font-size: 14px;
    margin-bottom: 6px;
  }
  
  .amount-change {
    font-size: 14px;
  }
  
  .balance-after {
    font-size: 12px;
  }
}
</style>
