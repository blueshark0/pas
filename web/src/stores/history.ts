import { defineStore } from 'pinia';
import { historyApi } from '@/services/api';

export interface BalanceHistory {
  id: number;
  timestamp: string;
  change_type: number; // 1-初始设置，2-收入执行，3-支出执行，4-手动编辑
  related_transaction_id: number | null;
  amount_change: number;
  balance_after: number;
  created_at: string;
}

export interface HistoryState {
  historyList: BalanceHistory[];
  totalCount: number;
  loading: boolean;
  error: string | null;
}

export const useHistoryStore = defineStore('history', {
  state: (): HistoryState => ({
    historyList: [],
    totalCount: 0,
    loading: false,
    error: null
  }),
  
  getters: {
    // 获取最近的历史记录（最近5条）
    recentHistory: (state) => {
      return [...state.historyList]
        .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
        .slice(0, 5);
    },
    
    // 按类型获取历史记录
    historyByType: (state) => (type: number) => {
      return state.historyList.filter(h => h.change_type === type);
    }
  },
  
  actions: {
    // 获取历史记录列表
    async fetchHistoryList(page: number = 1, pageSize: number = 20) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await historyApi.getList(page, pageSize);
        this.historyList = data.list;
        this.totalCount = data.total;
      } catch (error: any) {
        this.error = error.message || '获取历史记录失败';
        console.error('获取历史记录失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 获取特定变更类型的历史记录
    async fetchHistoryByType(type: number, page: number = 1, pageSize: number = 20) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await historyApi.getByType(type, page, pageSize);
        this.historyList = data.list;
        this.totalCount = data.total;
      } catch (error: any) {
        this.error = error.message || '获取历史记录失败';
        console.error('获取历史记录失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 按日期范围获取历史记录
    async fetchHistoryByDateRange(startDate: string, endDate: string, page: number = 1, pageSize: number = 20) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await historyApi.getByDateRange(startDate, endDate, page, pageSize);
        this.historyList = data.list;
        this.totalCount = data.total;
      } catch (error: any) {
        this.error = error.message || '获取历史记录失败';
        console.error('获取历史记录失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 获取特定交易的历史记录
    async fetchHistoryByTransaction(transactionId: number) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await historyApi.getByTransaction(transactionId);
        return data.list;
      } catch (error: any) {
        this.error = error.message || '获取历史记录失败';
        console.error('获取历史记录失败:', error);
        return [];
      } finally {
        this.loading = false;
      }
    }
  }
});
