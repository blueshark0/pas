<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElRate } from 'element-plus';
import { Plus, ArrowRight } from '@element-plus/icons-vue';
import { useAccountStore } from '@/stores/account';
import { useTransactionStore } from '@/stores/transaction';
import { useHistoryStore } from '@/stores/history';
import { formatAmount } from '@/services/dateUtils';
import BalanceDisplay from '@/components/BalanceDisplay.vue';
import TransactionItem from '@/components/TransactionItem.vue';
import HistoryItem from '@/components/HistoryItem.vue';

const router = useRouter();
const accountStore = useAccountStore();
const transactionStore = useTransactionStore();
const historyStore = useHistoryStore();

// 对话框状态
const initBalanceDialogVisible = ref(false);
const editBalanceDialogVisible = ref(false);
const submitLoading = ref(false);

// 表单数据
const initBalanceForm = reactive({
  amount: 0
});

const editBalanceForm = reactive({
  amount: 0
});

// 计算属性
const upcomingTransactions = computed(() => transactionStore.upcomingTransactions);
const recentHistory = computed(() => historyStore.recentHistory);

// 页面加载时获取数据
onMounted(async () => {
  // 检查是否已初始化余额
  await accountStore.fetchBalance();
  if (accountStore.currentBalance === null) {
    initBalanceDialogVisible.value = true;
  } else {
    // 自动执行待处理的交易
    await transactionStore.executeTransactions();
    // 获取待执行的交易
    await transactionStore.fetchPendingTransactions();
    // 获取历史记录
    await historyStore.fetchHistoryList();
  }
});

// 显示初始化余额对话框
const showInitBalanceDialog = () => {
  initBalanceDialogVisible.value = true;
};

// 显示编辑余额对话框
const showEditBalanceDialog = () => {
  editBalanceForm.amount = accountStore.currentBalance || 0;
  editBalanceDialogVisible.value = true;
};

// 处理对话框关闭
const handleDialogClose = () => {
  initBalanceDialogVisible.value = false;
  editBalanceDialogVisible.value = false;
};

// 处理初始化余额
const handleInitBalance = async () => {
  if (initBalanceForm.amount < 0) {
    ElMessage.warning('初始余额不能为负数');
    return;
  }

  submitLoading.value = true;
  try {
    const success = await accountStore.initBalance(initBalanceForm.amount);
    if (success) {
      ElMessage.success('初始余额设置成功');
      initBalanceDialogVisible.value = false;
      // 获取待执行的交易
      await transactionStore.fetchPendingTransactions();
      // 获取历史记录
      await historyStore.fetchHistoryList();
    }
  } finally {
    submitLoading.value = false;
  }
};

// 处理编辑余额
const handleEditBalance = async () => {
  if (editBalanceForm.amount < 0) {
    ElMessage.warning('余额不能为负数');
    return;
  }

  submitLoading.value = true;
  try {
    const success = await accountStore.editBalance(editBalanceForm.amount);
    if (success) {
      ElMessage.success('余额已更新');
      editBalanceDialogVisible.value = false;
      // 刷新历史记录
      await historyStore.fetchHistoryList();
    }
  } finally {
    submitLoading.value = false;
  }
};

// 导航方法
const goToAddTransaction = () => {
  router.push('/transaction/add');
};

const goToEditTransaction = (transaction) => {
  router.push(`/transaction/edit/${transaction.id}`);
};

const goToTransactionList = () => {
  router.push('/transaction/list');
};

const goToHistory = () => {
  router.push('/history');
};
</script>

<template>
  <div class="home-view">
    <el-card class="balance-card" shadow="never">
      <balance-display
        :amount="accountStore.currentBalance"
        :loading="accountStore.loading"
      >
        <div class="balance-actions" v-if="accountStore.currentBalance === null">
          <el-button type="primary" @click="showInitBalanceDialog">设置初始余额</el-button>
        </div>
        <div class="balance-actions" v-else>
          <el-button plain @click="showEditBalanceDialog">手动调整</el-button>
        </div>
      </balance-display>
    </el-card>

    <div class="section-header">
      <h2>即将执行的交易</h2>
      <el-button type="primary" plain size="small" @click="goToAddTransaction">
        <el-icon><Plus /></el-icon> 添加交易
      </el-button>
    </div>

    <el-card class="transactions-card" shadow="never" v-loading="transactionStore.loading">
      <div v-if="upcomingTransactions.length === 0" class="empty-transactions">
        <el-empty description="暂无待执行交易" />
      </div>
      <template v-else>
        <transaction-item
          v-for="transaction in upcomingTransactions"
          :key="transaction.id"
          :transaction="transaction"
          @click="goToEditTransaction(transaction)"
        />
      </template>

      <div class="view-all-link">
        <el-button text @click="goToTransactionList">
          查看全部交易 <el-icon><ArrowRight /></el-icon>
        </el-button>
      </div>
    </el-card>

    <div class="section-header">
      <h2>最近的余额变更</h2>
    </div>

    <el-card class="history-card" shadow="never" v-loading="historyStore.loading">
      <div v-if="recentHistory.length === 0" class="empty-history">
        <el-empty description="暂无余额变更记录" />
      </div>
      <template v-else>
        <history-item
          v-for="history in recentHistory"
          :key="history.id"
          :history="history"
        />
      </template>

      <div class="view-all-link">
        <el-button text @click="goToHistory">
          查看全部记录 <el-icon><ArrowRight /></el-icon>
        </el-button>
      </div>
    </el-card>

    <!-- 初始化余额对话框 -->
    <el-dialog
      v-model="initBalanceDialogVisible"
      title="设置初始余额"
      width="90%"
      :before-close="handleDialogClose"
      class="balance-dialog"
    >
      <el-form :model="initBalanceForm" label-position="top">
        <el-form-item label="初始余额">
          <el-input-number
            v-model="initBalanceForm.amount"
            :min="0"
            :precision="2"
            :step="1000"
            style="width: 100%"
            controls-position="right"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleDialogClose">取消</el-button>
          <el-button type="primary" @click="handleInitBalance" :loading="submitLoading">确认</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 编辑余额对话框 -->
    <el-dialog
      v-model="editBalanceDialogVisible"
      title="手动调整余额"
      width="90%"
      :before-close="handleDialogClose"
      class="balance-dialog"
    >
      <el-form :model="editBalanceForm" label-position="top">
        <el-form-item label="当前余额">
          <div class="current-balance">
            {{ accountStore.currentBalance ? formatAmount(accountStore.currentBalance) : '暂无数据' }}
          </div>
        </el-form-item>
        <el-form-item label="新余额">
          <el-input-number
            v-model="editBalanceForm.amount"
            :min="0"
            :precision="2"
            :step="100"
            style="width: 100%"
            controls-position="right"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleDialogClose">取消</el-button>
          <el-button type="primary" @click="handleEditBalance" :loading="submitLoading">确认</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.home-view {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.balance-card {
  margin-bottom: 20px;
}

.balance-actions {
  margin-top: 16px;
  display: flex;
  justify-content: center;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 20px 0 10px;
}

.section-header h2 {
  font-size: 18px;
  margin: 0;
  color: #303133;
}

.transactions-card,
.history-card {
  margin-bottom: 20px;
}

.empty-transactions,
.empty-history {
  padding: 30px 0;
}

.view-all-link {
  text-align: center;
  padding: 10px 0;
  border-top: 1px solid #f0f0f0;
}

.current-balance {
  font-size: 24px;
  color: #303133;
  font-weight: bold;
  text-align: center;
  padding: 10px 0;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .home-view {
    padding: 16px;
  }

  .section-header h2 {
    font-size: 16px;
  }

  .balance-dialog {
    width: 90%;
  }
}

@media (min-width: 768px) {
  .balance-dialog {
    width: 400px;
  }
}
</style>
