<template>
  <div class="income-expense-form">
    <form @submit.prevent="handleSubmit">
      <!-- 收支类型 -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">类型</label>
        <div class="flex space-x-4">
          <div class="flex items-center">
            <input type="radio" id="income" v-model="form.type" value="income" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" />
            <label for="income" class="ml-2 block text-sm text-gray-700">收入</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="expense" v-model="form.type" value="expense" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" />
            <label for="expense" class="ml-2 block text-sm text-gray-700">支出</label>
          </div>
        </div>
      </div>

      <!-- 金额 -->
      <div class="mb-4">
        <label for="amount" class="block text-sm font-medium text-gray-700">金额</label>
        <div class="mt-1 relative rounded-md shadow-sm">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-gray-500 sm:text-sm">¥</span>
          </div>
          <input type="number" step="0.01" id="amount" v-model="form.amount" 
                 class="pl-7 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
        </div>
      </div>

      <!-- 日期 -->
      <div class="mb-4">
        <label for="date" class="block text-sm font-medium text-gray-700">日期</label>
        <input type="date" id="date" v-model="form.date" 
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
      </div>

      <!-- 分类 -->
      <div class="mb-4">
        <label for="category" class="block text-sm font-medium text-gray-700">分类</label>
        <select id="category" v-model="form.category" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
          <option v-for="category in categories[form.type]" :key="category" :value="category">{{ category }}</option>
        </select>
      </div>

      <!-- 描述 -->
      <div class="mb-4">
        <label for="description" class="block text-sm font-medium text-gray-700">描述</label>
        <textarea id="description" v-model="form.description" 
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2"></textarea>
      </div>

      <!-- 标签 -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">标签</label>
        <div class="flex flex-wrap gap-2">
          <div v-for="(tag, index) in form.tags" :key="index" 
               class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded flex items-center">
            {{ tag }}
            <button type="button" @click="removeTag(index)" class="ml-1.5 text-blue-600 hover:text-blue-800">
              &times;
            </button>
          </div>
          <div class="relative">
            <input type="text" v-model="newTag" @keydown.enter.prevent="addTag" placeholder="添加标签" 
                   class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-1 px-2" />
            <button type="button" @click="addTag" class="ml-1 text-indigo-600 hover:text-indigo-800 text-sm">
              +
            </button>
          </div>
        </div>
      </div>

      <!-- 交易类型 -->
      <div class="mb-4">
        <label for="transaction_type" class="block text-sm font-medium text-gray-700">交易类型</label>
        <select id="transaction_type" v-model="form.transaction_type" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
          <option value="single">单笔</option>
          <option value="installment">分期</option>
        </select>
      </div>

      <!-- 提交按钮 -->
      <div class="mt-6">
        <button type="submit" :disabled="isSubmitting" class="w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          {{ isSubmitting ? '提交中...' : '提交' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';

interface Account {
  id: number;
  name: string;
  amount: number;
}

interface InstallmentInfo {
  total_amount: number | string;
  total_periods: number | string;
  current_period: number | string;
}

interface FormData {
  type: string;
  amount: number | string;
  date: string;
  category: string;
  description: string;
  tags: string[];
  transaction_type: string;
  period: string;
  installment_info: InstallmentInfo;
  account_id: number | string;
}

interface Props {
  editData: Record<string, any> | null;
}

// 组件属性
const props = defineProps<Props>();

// 组件事件
const emit = defineEmits<{
  (e: 'save-success', data: any): void;
  (e: 'cancel'): void;
}>();

// 状态
const isSubmitting = ref<boolean>(false);
const newTag = ref<string>('');
const accounts = ref<Account[]>([]);

// 默认分类
const categories: Record<string, string[]> = {
  income: ['工资', '奖金', '投资收益', '兼职', '礼金', '其他收入'],
  expense: ['餐饮', '购物', '交通', '住房', '娱乐', '医疗', '教育', '旅行', '日用品', '其他支出']
};

// 表单数据
const form = reactive<FormData>({
  type: 'expense',
  amount: '',
  date: new Date().toISOString().substr(0, 10), // 默认今天
  category: '',
  description: '',
  tags: [],
  transaction_type: 'single',
  period: 'monthly',
  installment_info: {
    total_amount: '',
    total_periods: '',
    current_period: 1
  },
  account_id: ''
});

// 初始化默认分类
form.category = categories.expense[0];

// 加载编辑数据
const loadEditData = (): void => {
  if (props.editData) {
    // 复制数据到表单
    Object.keys(form).forEach(key => {
      if (key in props.editData) {
        if (key === 'installment_info' && props.editData[key]) {
          // 特殊处理JSON对象
          const infoData = typeof props.editData[key] === 'string' 
            ? JSON.parse(props.editData[key]) 
            : props.editData[key];
          Object.assign(form.installment_info, infoData);
        } else if (key === 'tags' && props.editData[key]) {
          // 特殊处理标签数组
          form.tags = Array.isArray(props.editData[key]) 
            ? [...props.editData[key]] 
            : (typeof props.editData[key] === 'string' 
              ? JSON.parse(props.editData[key]) 
              : []);
        } else {
          // @ts-ignore - 动态赋值
          form[key] = props.editData[key];
        }
      }
    });
  }
};

// 监听编辑数据变化
watch(() => props.editData, (newVal) => {
  if (newVal) {
    loadEditData();
  } else {
    resetForm();
  }
}, { immediate: true });

// 获取账户列表
const fetchAccounts = async (): Promise<void> => {
  try {
    const response = await axios.get('/api/balance/list');
    accounts.value = response.data.data || [];
    
    // 设置默认账户
    if (accounts.value.length > 0 && !form.account_id) {
      form.account_id = accounts.value[0].id;
    }
  } catch (error) {
    console.error('获取账户列表失败:', error);
  }
};

// 添加标签
const addTag = (): void => {
  if (newTag.value.trim() && !form.tags.includes(newTag.value.trim())) {
    form.tags.push(newTag.value.trim());
    newTag.value = '';
  }
};

// 移除标签
const removeTag = (index: number): void => {
  form.tags.splice(index, 1);
};

// 重置表单
const resetForm = (): void => {
  Object.assign(form, {
    type: 'expense',
    amount: '',
    date: new Date().toISOString().substr(0, 10),
    category: categories.expense[0],
    description: '',
    tags: [],
    transaction_type: 'single',
    period: 'monthly',
    installment_info: {
      total_amount: '',
      total_periods: '',
      current_period: 1
    },
    account_id: accounts.value.length > 0 ? accounts.value[0].id : ''
  });
};

// 取消
const cancel = (): void => {
  emit('cancel');
};

// 提交表单
const handleSubmit = async (): Promise<void> => {
  try {
    isSubmitting.value = true;
    
    // 构建提交数据
    const submitData = { ...form };
    
    // API端点和方法
    let endpoint = '/api/income-expense/add';
    let method = 'post';
    
    // 如果是编辑模式
    if (props.editData && props.editData.id) {
      endpoint = `/api/income-expense/update/${props.editData.id}`;
      method = 'put';
    }
    
    // 提交到API
    const response = await axios[method](endpoint, submitData);
    
    // 成功处理
    console.log('记录保存成功:', response.data);
    emit('save-success', response.data);
    
    // 如果不是编辑模式，重置表单
    if (!props.editData) {
      resetForm();
    }
  } catch (error) {
    console.error('保存记录失败:', error);
    alert('保存记录失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 组件挂载时获取账户列表
onMounted(() => {
  fetchAccounts();
});
</script>
