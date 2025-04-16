<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { useHistoryStore } from '@/stores/history';
import HistoryItem from '@/components/HistoryItem.vue';
import { formatDate } from '@/services/dateUtils';

const historyStore = useHistoryStore();

// 筛选条件
const filterForm = reactive({
  changeType: 0, // 0-全部, 1-初始设置, 2-收入执行, 3-支出执行, 4-手动编辑
  dateRange: null, // 日期范围
});

// 分页
const currentPage = ref(1);
const pageSize = ref(10);

// 加载数据
const loadData = async () => {
  if (filterForm.changeType > 0) {
    // 按变更类型筛选
    await historyStore.fetchHistoryByType(filterForm.changeType, currentPage.value, pageSize.value);
  } else if (filterForm.dateRange && filterForm.dateRange.length === 2) {
    // 按日期范围筛选
    const startDate = formatDate(filterForm.dateRange[0]);
    const endDate = formatDate(filterForm.dateRange[1]);
    await historyStore.fetchHistoryByDateRange(startDate, endDate, currentPage.value, pageSize.value);
  } else {
    // 获取全部历史记录
    await historyStore.fetchHistoryList(currentPage.value, pageSize.value);
  }
};

// 重置筛选条件
const resetFilters = () => {
  filterForm.changeType = 0;
  filterForm.dateRange = null;
  currentPage.value = 1;
  loadData();
};

// 应用筛选条件
const applyFilters = () => {
  currentPage.value = 1;
  loadData();
};

// 页面加载时获取数据
onMounted(async () => {
  await loadData();
});
</script>

<template>
  <div class="history-view">
    <div class="page-header">
      <h1>余额历史</h1>
    </div>

    <el-card shadow="never" class="filter-card">
      <div class="filters">
        <div class="filter-item">
          <span class="filter-label">变更类型：</span>
          <el-select v-model="filterForm.changeType" placeholder="全部类型" size="default">
            <el-option :value="0" label="全部" />
            <el-option :value="1" label="初始设置" />
            <el-option :value="2" label="收入执行" />
            <el-option :value="3" label="支出执行" />
            <el-option :value="4" label="手动编辑" />
          </el-select>
        </div>
        
        <div class="filter-item">
          <span class="filter-label">日期范围：</span>
          <el-date-picker
            v-model="filterForm.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
          />
        </div>
        
        <div class="filter-actions">
          <el-button @click="resetFilters">重置</el-button>
          <el-button type="primary" @click="applyFilters">查询</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="history-list-card" v-loading="historyStore.loading">
      <div v-if="historyStore.historyList.length === 0" class="empty-list">
        <el-empty description="暂无历史记录" />
      </div>
      
      <template v-else>
        <history-item 
          v-for="history in historyStore.historyList" 
          :key="history.id" 
          :history="history"
        />
      </template>
      
      <div class="pagination-container" v-if="historyStore.historyList.length > 0">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="currentPage"
          :page-size="pageSize"
          :total="historyStore.totalCount"
          @current-change="newPage => { currentPage = newPage; loadData(); }"
        />
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.history-view {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h1 {
  font-size: 24px;
  margin: 0;
  color: #303133;
}

.filter-card {
  margin-bottom: 20px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  align-items: flex-end;
}

.filter-item {
  display: flex;
  flex-direction: column;
}

.filter-label {
  margin-bottom: 8px;
  color: #606266;
}

.filter-actions {
  display: flex;
  gap: 10px;
  margin-left: auto;
}

.history-list-card {
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
  .history-view {
    padding: 16px;
  }
  
  .page-header h1 {
    font-size: 20px;
  }
  
  .filters {
    flex-direction: column;
    gap: 16px;
  }
  
  .filter-item {
    width: 100%;
  }
  
  .filter-item .el-select,
  .filter-item .el-date-picker {
    width: 100%;
  }
  
  .filter-actions {
    width: 100%;
    margin-left: 0;
    justify-content: flex-end;
  }
}
</style>
