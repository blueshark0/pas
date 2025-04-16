<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage } from 'element-plus';
import { ArrowLeft } from '@element-plus/icons-vue';
import { useTransactionStore } from '@/stores/transaction';
import TransactionForm from '@/components/TransactionForm.vue';

const router = useRouter();
const route = useRoute();
const transactionStore = useTransactionStore();

const loading = ref(false);
const formSubmitting = ref(false);
const transaction = ref(null);
const isEditing = computed(() => route.params.id !== undefined);
const pageTitle = computed(() => isEditing.value ? '编辑交易' : '添加交易');

// 获取交易详情
const fetchTransactionDetail = async () => {
  const id = Number(route.params.id);
  if (!id) return;
  
  loading.value = true;
  try {
    // 如果交易列表为空，先获取列表
    if (transactionStore.transactions.length === 0) {
      await transactionStore.fetchTransactions();
    }
    
    // 从列表中找到对应的交易
    const found = transactionStore.transactions.find(t => t.id === id);
    if (found) {
      transaction.value = found;
    } else {
      ElMessage.error('未找到指定交易');
      router.push('/transaction/list');
    }
  } catch (error) {
    ElMessage.error('获取交易详情失败');
  } finally {
    loading.value = false;
  }
};

// 提交表单
const handleFormSubmit = async (formData) => {
  formSubmitting.value = true;
  try {
    if (isEditing.value) {
      // 更新交易
      const id = Number(route.params.id);
      const success = await transactionStore.updateTransaction(id, formData);
      if (success) {
        ElMessage.success('更新成功');
        router.push('/transaction/list');
      }
    } else {
      // 添加交易
      const id = await transactionStore.addTransaction(formData);
      if (id) {
        ElMessage.success('添加成功');
        router.push('/transaction/list');
      }
    }
  } catch (error) {
    ElMessage.error(isEditing.value ? '更新失败' : '添加失败');
  } finally {
    formSubmitting.value = false;
  }
};

// 取消编辑
const handleCancel = () => {
  router.push('/transaction/list');
};

// 页面加载时获取数据
onMounted(async () => {
  if (isEditing.value) {
    await fetchTransactionDetail();
  }
});
</script>

<template>
  <div class="transaction-edit-view">
    <div class="page-header">
      <el-button icon="ArrowLeft" text @click="handleCancel">返回</el-button>
      <h1>{{ pageTitle }}</h1>
    </div>
    
    <el-card shadow="never" v-loading="loading">
      <transaction-form 
        :transaction="transaction" 
        :loading="formSubmitting"
        :disabled="loading"
        @submit="handleFormSubmit"
        @cancel="handleCancel"
      />
    </el-card>
  </div>
</template>

<style scoped>
.transaction-edit-view {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  font-size: 24px;
  margin: 0;
  color: #303133;
  margin-left: 10px;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .transaction-edit-view {
    padding: 16px;
  }
  
  .page-header h1 {
    font-size: 20px;
  }
}
</style>
