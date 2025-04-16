import { defineStore } from 'pinia';
import { transactionApi } from '@/services/api';

export interface Transaction {
  id: number;
  type: number; // 1-收入，2-支出
  amount: number;
  execution_date: string;
  description: string;
  status: number; // 1-待执行，2-执行中，3-已执行，4-终止
  is_recurring: number; // 0-否, 1-是
  recurrence_type?: number; // NULL/0-无, 1-每日, 2-每周, 3-每月, 4-每年
  recurrence_interval?: number;
  recurrence_end_date?: string;
  created_at: string;
  updated_at: string;
}

export interface TransactionState {
  transactions: Transaction[];
  pendingTransactions: Transaction[];
  totalCount: number;
  loading: boolean;
  error: string | null;
}

export const useTransactionStore = defineStore('transaction', {
  state: (): TransactionState => ({
    transactions: [],
    pendingTransactions: [],
    totalCount: 0,
    loading: false,
    error: null
  }),
  
  getters: {
    // 获取即将执行的交易（最近5条）
    upcomingTransactions: (state) => {
      return state.pendingTransactions
        .sort((a, b) => new Date(a.execution_date).getTime() - new Date(b.execution_date).getTime())
        .slice(0, 5);
    },
    
    // 按类型获取交易
    transactionsByType: (state) => (type: number) => {
      return state.transactions.filter(t => t.type === type);
    },
    
    // 按状态获取交易
    transactionsByStatus: (state) => (status: number) => {
      return state.transactions.filter(t => t.status === status);
    }
  },
  
  actions: {
    // 获取交易列表
    async fetchTransactions(status?: number, page: number = 1, pageSize: number = 20) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await transactionApi.getList(status, page, pageSize);
        this.transactions = data.list;
        this.totalCount = data.total;
      } catch (error: any) {
        this.error = error.message || '获取交易列表失败';
        console.error('获取交易列表失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 获取待执行的交易
    async fetchPendingTransactions() {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await transactionApi.getList(1); // 状态1为待执行
        this.pendingTransactions = data.list;
      } catch (error: any) {
        this.error = error.message || '获取待执行交易失败';
        console.error('获取待执行交易失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 添加交易
    async addTransaction(transaction: Partial<Transaction>) {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await transactionApi.add(transaction);
        // 重新获取交易列表
        await this.fetchTransactions();
        await this.fetchPendingTransactions();
        return data.id;
      } catch (error: any) {
        this.error = error.message || '添加交易失败';
        console.error('添加交易失败:', error);
        return null;
      } finally {
        this.loading = false;
      }
    },
    
    // 更新交易
    async updateTransaction(id: number, transaction: Partial<Transaction>) {
      this.loading = true;
      this.error = null;
      
      try {
        await transactionApi.update(id, transaction);
        // 重新获取交易列表
        await this.fetchTransactions();
        await this.fetchPendingTransactions();
        return true;
      } catch (error: any) {
        this.error = error.message || '更新交易失败';
        console.error('更新交易失败:', error);
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    // 删除交易
    async deleteTransaction(id: number) {
      this.loading = true;
      this.error = null;
      
      try {
        await transactionApi.delete(id);
        // 重新获取交易列表
        await this.fetchTransactions();
        await this.fetchPendingTransactions();
        return true;
      } catch (error: any) {
        this.error = error.message || '删除交易失败';
        console.error('删除交易失败:', error);
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    // 执行待处理的交易
    async executeTransactions(date?: string) {
      this.loading = true;
      this.error = null;
      
      try {
        const result = await transactionApi.execute(date);
        // 重新获取交易列表和余额
        await this.fetchTransactions();
        await this.fetchPendingTransactions();
        return result;
      } catch (error: any) {
        this.error = error.message || '执行交易失败';
        console.error('执行交易失败:', error);
        return null;
      } finally {
        this.loading = false;
      }
    }
  }
});
