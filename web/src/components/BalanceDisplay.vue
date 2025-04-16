<template>
  <div class="balance-display">
    <div class="balance-title">{{ title }}</div>
    <div class="balance-amount" :class="{ 'loading': loading }">
      <template v-if="loading">
        <el-skeleton animated :rows="1">
          <template #template>
            <el-skeleton-item variant="text" style="width: 100%; height: 60px;" />
          </template>
        </el-skeleton>
      </template>
      <template v-else-if="amount !== null">
        <span class="amount-symbol">¥</span>
        <span class="amount-value">{{ formatAmount(amount) }}</span>
      </template>
      <template v-else>
        <span class="amount-empty">暂无数据</span>
      </template>
    </div>
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatAmount as formatAmountUtil } from '@/services/dateUtils';

interface Props {
  amount: number | null;
  title?: string;
  loading?: boolean;
  showSymbol?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: '当前余额',
  loading: false,
  showSymbol: true
});

const formatAmount = (value: number) => {
  return formatAmountUtil(value);
};
</script>

<style scoped>
.balance-display {
  text-align: center;
  padding: 20px;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

.balance-title {
  font-size: 18px;
  color: #606266;
  margin-bottom: 10px;
}

.balance-amount {
  font-size: 48px;
  color: #303133;
  font-weight: bold;
  line-height: 1.2;
}

.amount-symbol {
  font-size: 30px;
  margin-right: 5px;
}

.amount-empty {
  color: #909399;
  font-size: 20px;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .balance-amount {
    font-size: 36px;
  }
  
  .amount-symbol {
    font-size: 24px;
  }
}
</style>
