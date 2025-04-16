import { defineStore } from 'pinia';
import { accountApi } from '@/services/api';

export interface AccountState {
  currentBalance: number | null;
  loading: boolean;
  error: string | null;
}

export const useAccountStore = defineStore('account', {
  state: (): AccountState => ({
    currentBalance: null,
    loading: false,
    error: null
  }),
  
  actions: {
    // 获取当前余额
    async fetchBalance() {
      this.loading = true;
      this.error = null;
      
      try {
        const data = await accountApi.getBalance();
        this.currentBalance = data.current_balance;
      } catch (error: any) {
        this.error = error.message || '获取余额失败';
        console.error('获取余额失败:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // 初始化余额
    async initBalance(initialBalance: number) {
      if (initialBalance < 0) {
        this.error = '初始余额不能为负数';
        return false;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        const data = await accountApi.initBalance(initialBalance);
        this.currentBalance = data.current_balance;
        return true;
      } catch (error: any) {
        this.error = error.message || '初始化余额失败';
        console.error('初始化余额失败:', error);
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    // 编辑余额
    async editBalance(newBalance: number) {
      if (newBalance < 0) {
        this.error = '余额不能为负数';
        return false;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        const data = await accountApi.editBalance(newBalance);
        this.currentBalance = data.current_balance;
        return true;
      } catch (error: any) {
        this.error = error.message || '编辑余额失败';
        console.error('编辑余额失败:', error);
        return false;
      } finally {
        this.loading = false;
      }
    }
  }
});
