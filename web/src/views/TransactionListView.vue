<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search, RefreshRight } from '@element-plus/icons-vue';
import { useTransactionStore } from '@/stores/transaction';
import { useAccountStore } from '@/stores/account';
import TransactionItem from '@/components/TransactionItem.vue';

const router = useRouter();
const transactionStore = useTransactionStore();
const accountStore = useAccountStore();

// 状态筛选
const statusFilter = ref(0); // 0-全部, 1-待执行, 2-执行中, 3-已执行, 4-终止
const typeFilter = ref(0); // 0-全部, 1-收入, 2-支出

// 分页
const currentPage = ref(1);
const pageSize = ref(10);

// 计算筛选后的交易列表
const filteredTransactions = computed(() => {
  let result = [...transactionStore.transactions];
  
  // 按状态筛选
  if (statusFilter.value > 0) {
    result = result.filter(t => t.status === statusFilter.value);
  }
  
  // 按类型筛选
  if (typeFilter.value > 0) {
    result = result.filter(t => t.type === typeFilter.value);
  }
  
  return result;
});

// 加载数据
const loadData = async () => {
  await transactionStore.fetchTransactions();
  await accountStore.fetchBalance();
};

// 执行待处理的交易
const executeTransactions = async () => {
  try {
    const result = await transactionStore.executeTransactions();
    if (result && result.executed && result.executed.length > 0) {
      ElMessage.success(`成功执行了 ${result.executed.length} 笔交易`);
    } else {
      ElMessage.info('没有需要执行的交易');
    }
  } catch (error) {
    ElMessage.error('执行交易失败');
  }
};

// 前往添加交易页面
const goToAddTransaction = () => {
  router.push('/transaction/add');
};

// 前往编辑交易页面
const goToEditTransaction = (transaction) => {
  router.push(`/transaction/edit/${transaction.id}`);
};

// 删除交易
const deleteTransaction = async (transaction) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除这笔${transaction.type === 1 ? '收入' : '支出'}交易吗？`, 
      '删除确认', 
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    );
    
    const success = await transactionStore.deleteTransaction(transaction.id);
    if (success) {
      ElMessage.success('删除成功');
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败');
    }
  }
};

// 页面加载时获取数据
onMounted(async () => {
  await loadData();
});
</script>

<template>
  <div class="transaction-list-view">
    <div class="page-header">
      <h1>交易列表</h1>
      <div class="header-actions">
        <el-button type="primary" @click="goToAddTransaction">
          <el-icon><Plus /></el-icon> 添加
        </el-button>
        <el-button type="success" @click="executeTransactions">
          <el-icon><RefreshRight /></el-icon> 执行交易
        </el-button>
      </div>
    </div>
    
    <el-card shadow="never" class="filter-card">
      <div class="filters">
        <div class="filter-item">
          <span class="filter-label">交易类型：</span>
          <el-radio-group v-model="typeFilter" size="small">
            <el-radio-button :label="0">全部</el-radio-button>
            <el-radio-button :label="1">收入</el-radio-button>
            <el-radio-button :label="2">支出</el-radio-button>
          </el-radio-group>
        </div>
        
        <div class="filter-item">
          <span class="filter-label">交易状态：</span>
          <el-radio-group v-model="statusFilter" size="small">
            <el-radio-button :label="0">全部</el-radio-button>
            <el-radio-button :label="1">待执行</el-radio-button>
            <el-radio-button :label="2">执行中</el-radio-button>
            <el-radio-button :label="3">已执行</el-radio-button>
            <el-radio-button :label="4">终止</el-radio-button>
          </el-radio-group>
        </div>
      </div>
    </el-card>
    
    <el-card shadow="never" class="transaction-list-card" v-loading="transactionStore.loading">
      <div v-if="filteredTransactions.length === 0" class="empty-list">
        <el-empty description="暂无交易数据" />
      </div>
      
      <template v-else>
        <transaction-item 
          v-for="transaction in filteredTransactions" 
          :key="transaction.id" 
          :transaction="transaction"
          @click="goToEditTransaction(transaction)"
        >
          <template #actions>
            <el-dropdown trigger="click" @command="command => command === 'delete' && deleteTransaction(transaction)">
              <el-button type="text" size="small">
                <el-icon><More /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="delete" v-if="transaction.status !== 3">删除</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </transaction-item>
      </template>
      
      <div class="pagination-container" v-if="filteredTransactions.length > 0">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="currentPage"
          :page-size="pageSize"
          :total="transactionStore.totalCount"
          @current-change="newPage => { currentPage = newPage; loadData(); }"
        />
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.transaction-list-view {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  font-size: 24px;
  margin: 0;
  color: #303133;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.filter-card {
  margin-bottom: 20px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.filter-item {
  display: flex;
  align-items: center;
}

.filter-label {
  margin-right: 10px;
  color: #606266;
}

.transaction-list-card {
  min-height: 400px;
}

.empty-list {
  padding: 60px 0;
}

.pagination-container {
  margin-top: 20px;
  text-align: center;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .transaction-list-view {
    padding: 16px;
  }
  
  .page-header h1 {
    font-size: 20px;
  }
  
  .header-actions {
    gap: 5px;
  }
  
  .filters {
    flex-direction: column;
    gap: 10px;
  }
  
  .filter-item {
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
  }
  
  .filter-label {
    margin-bottom: 5px;
  }
  
  .filter-item .el-radio-group {
    width: 100%;
  }
  
  .filter-item .el-radio-button {
    width: 33.33%;
  }
  
  .filter-item .el-radio-button__inner {
    width: 100%;
  }
}
</style>
