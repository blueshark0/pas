import axios from 'axios';

// 创建 axios 实例
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  }
});

// 响应拦截器
api.interceptors.response.use(
  response => {
    const res = response.data;
    if (res.code !== 0) {
      // 根据业务状态码处理错误
      console.error(`API 错误: ${res.msg || '未知错误'}`);
      return Promise.reject(new Error(res.msg || '未知错误'));
    }
    return res.data;
  },
  error => {
    console.error(`请求错误: ${error}`);
    return Promise.reject(error);
  }
);

// 账户相关接口
export const accountApi = {
  // 获取当前余额
  getBalance: () => api.get('/account/balance'),

  // 设置初始余额
  initBalance: (initialBalance: number) => api.post('/account/init', { initial_balance: initialBalance }),

  // 手动编辑余额
  editBalance: (newBalance: number) => api.post('/account/edit', { new_balance: newBalance })
};

// 交易相关接口
export const transactionApi = {
  // 获取交易列表
  getList: (status?: number, page: number = 1, pageSize: number = 20) => 
    api.get('/transaction/list', { params: { status, page, page_size: pageSize } }),

  // 添加预设交易
  add: (data: any) => api.post('/transaction/add', data),

  // 修改预设交易
  update: (id: number, data: any) => api.put(`/transaction/${id}`, data),
  
  // 删除预设交易
  delete: (id: number) => api.delete(`/transaction/${id}`),

  // 执行待处理的交易
  execute: (date?: string) => api.post('/transaction/execute', { date })
};

// 历史记录相关接口
export const historyApi = {
  // 获取历史记录列表
  getList: (page: number = 1, pageSize: number = 20) => 
    api.get('/history/list', { params: { page, page_size: pageSize } }),

  // 获取特定变更类型的历史记录
  getByType: (type: number, page: number = 1, pageSize: number = 20) => 
    api.get(`/history/type/${type}`, { params: { page, page_size: pageSize } }),

  // 按日期范围获取历史记录
  getByDateRange: (startDate: string, endDate: string, page: number = 1, pageSize: number = 20) => 
    api.get('/history/date-range', { params: { start_date: startDate, end_date: endDate, page, page_size: pageSize } }),

  // 获取特定交易的历史记录
  getByTransaction: (transactionId: number) => 
    api.get(`/history/transaction/${transactionId}`)
};

export default api;
