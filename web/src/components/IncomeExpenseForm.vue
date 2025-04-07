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

      <!-- 账户 -->
      <div class="mb-4">
        <label for="account_id" class="block text-sm font-medium text-gray-700">账户</label>
        <select id="account_id" v-model="form.account_id" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
          <option value="">请选择账户</option>
          <option v-for="account in accounts" :key="account.id" :value="account.id">
            {{ account.name }} (¥{{ formatNumber(account.amount) }})
          </option>
        </select>
      </div>

      <!-- 分类 -->
      <div class="mb-4">
        <label for="category_id" class="block text-sm font-medium text-gray-700">分类</label>
        <div class="mt-1 flex flex-wrap gap-2">
          <button 
            type="button"
            v-for="category in filteredCategories" 
            :key="category.id"
            @click="selectCategory(category.id)"
            :class="[
              'px-3 py-2 rounded-md text-sm flex items-center',
              form.category_id === category.id 
                ? 'bg-indigo-100 text-indigo-800 border-2 border-indigo-500'
                : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'
            ]"
          >
            <div 
              v-if="category.color" 
              class="w-3 h-3 rounded-full mr-2" 
              :style="`background-color: ${category.color}`"
            ></div>
            {{ category.name }}
          </button>
          
          <button 
            type="button"
            @click="showAddCategoryModal = true"
            class="px-3 py-2 rounded-md text-sm bg-white border border-dashed border-gray-300 text-gray-500 hover:bg-gray-50"
          >
            + 添加分类
          </button>
        </div>
      </div>

      <!-- 额外字段（根据类型显示不同字段） -->
      <div v-if="form.type === 'income'">
        <!-- 收入来源 -->
        <div class="mb-4">
          <label for="source" class="block text-sm font-medium text-gray-700">收入来源</label>
          <input type="text" id="source" v-model="form.source" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>
      </div>

      <div v-if="form.type === 'expense'">
        <!-- 商家 -->
        <div class="mb-4">
          <label for="merchant" class="block text-sm font-medium text-gray-700">商家</label>
          <input type="text" id="merchant" v-model="form.merchant" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <!-- 支付方式 -->
        <div class="mb-4">
          <label for="payment_method" class="block text-sm font-medium text-gray-700">支付方式</label>
          <select id="payment_method" v-model="form.payment_method" 
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="现金">现金</option>
            <option value="支付宝">支付宝</option>
            <option value="微信">微信</option>
            <option value="储蓄卡">储蓄卡</option>
            <option value="信用卡">信用卡</option>
            <option value="其他">其他</option>
          </select>
        </div>
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
          <option value="periodic">周期性</option>
          <option value="installment">分期</option>
        </select>
      </div>

      <!-- 周期性交易设置 -->
      <div v-if="form.transaction_type === 'periodic'" class="mb-4">
        <label for="period" class="block text-sm font-medium text-gray-700">周期</label>
        <select id="period" v-model="form.period" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
          <option value="daily">每天</option>
          <option value="weekly">每周</option>
          <option value="monthly">每月</option>
          <option value="quarterly">每季度</option>
          <option value="yearly">每年</option>
        </select>
      </div>

      <!-- 分期设置 -->
      <div v-if="form.transaction_type === 'installment'" class="space-y-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label for="total_amount" class="block text-sm font-medium text-gray-700">总金额</label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">¥</span>
              </div>
              <input type="number" step="0.01" id="total_amount" v-model="form.installment_info.total_amount" 
                    class="pl-7 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
            </div>
          </div>
          <div>
            <label for="total_periods" class="block text-sm font-medium text-gray-700">总期数</label>
            <input type="number" min="1" id="total_periods" v-model="form.installment_info.total_periods" 
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
          </div>
          <div>
            <label for="current_period" class="block text-sm font-medium text-gray-700">当前期数</label>
            <input type="number" min="1" :max="form.installment_info.total_periods" id="current_period" v-model="form.installment_info.current_period" 
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
          </div>
        </div>
      </div>

      <!-- 按钮组 -->
      <div class="mt-6 flex gap-3">
        <button 
          type="button" 
          @click="cancel" 
          class="flex-1 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          取消
        </button>
        <button 
          type="submit" 
          :disabled="isSubmitting || !isFormValid" 
          class="flex-1 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300 disabled:cursor-not-allowed"
        >
          {{ isSubmitting ? '提交中...' : (editMode ? '更新' : '保存') }}
        </button>
      </div>
    </form>
    
    <!-- 添加分类弹窗 -->
    <div v-if="showAddCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">添加新分类</h3>
        
        <div class="space-y-4">
          <div>
            <label for="new_category_name" class="block text-sm font-medium text-gray-700">分类名称</label>
            <input type="text" id="new_category_name" v-model="newCategory.name" 
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
          </div>
          
          <div>
            <label for="new_category_color" class="block text-sm font-medium text-gray-700">颜色</label>
            <input type="color" id="new_category_color" v-model="newCategory.color" 
                  class="mt-1 block border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
          </div>
        </div>
        
        <div class="flex justify-end mt-6 space-x-3">
          <button 
            @click="showAddCategoryModal = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="addNewCategory" 
            :disabled="!newCategory.name.trim()"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:bg-indigo-300 disabled:cursor-not-allowed"
          >
            添加
          </button>
        </div>
      </div>
    </div>
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

interface Category {
  id: number;
  name: string;
  icon?: string;
  color?: string;
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
  category_id: number | null;
  description: string;
  source?: string;
  merchant?: string;
  payment_method?: string;
  tags: string[];
  transaction_type: string;
  period?: string;
  installment_info: InstallmentInfo;
  account_id: number | null;
  status?: string;
  attachment?: string;
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
const incomeCategories = ref<Category[]>([]);
const expenseCategories = ref<Category[]>([]);
const showAddCategoryModal = ref<boolean>(false);
const editMode = computed(() => !!props.editData);

// 新分类
const newCategory = reactive({
  name: '',
  color: '#1890ff',
});

// 表单数据
const form = reactive<FormData>({
  type: 'expense',
  amount: '',
  date: new Date().toISOString().substr(0, 10), // 默认今天
  category_id: null,
  description: '',
  source: '',
  merchant: '',
  payment_method: '现金',
  tags: [],
  transaction_type: 'single',
  period: 'monthly',
  installment_info: {
    total_amount: '',
    total_periods: '',
    current_period: 1
  },
  account_id: null,
  status: 'settled'
});

// 过滤得到当前类型的分类列表
const filteredCategories = computed(() => {
  return form.type === 'income' ? incomeCategories.value : expenseCategories.value;
});

// 表单是否有效
const isFormValid = computed(() => {
  return (
    form.amount !== '' && 
    form.amount > 0 && 
    form.date !== '' && 
    form.category_id !== null && 
    form.account_id !== null &&
    (form.transaction_type !== 'installment' || (
      !!form.installment_info.total_amount &&
      !!form.installment_info.total_periods
    )) &&
    (form.transaction_type !== 'periodic' || !!form.period)
  );
});

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

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

// 监听类型变化，重置分类
watch(() => form.type, (newType) => {
  // 当类型改变时，默认选择第一个分类
  const categories = newType === 'income' ? incomeCategories.value : expenseCategories.value;
  if (categories.length > 0) {
    form.category_id = categories[0].id;
  } else {
    form.category_id = null;
  }
});

// 选择分类
const selectCategory = (categoryId: number): void => {
  form.category_id = categoryId;
};

// 获取账户列表
const fetchAccounts = async (): Promise<void> => {
  try {
    const response = await axios.get('/api/balance');
    accounts.value = response.data || [];
    
    // 设置默认账户
    if (accounts.value.length > 0 && !form.account_id) {
      form.account_id = accounts.value[0].id;
    }
  } catch (error) {
    console.error('获取账户列表失败:', error);
  }
};

// 获取分类列表
const fetchCategories = async (): Promise<void> => {
  try {
    // 获取收入分类
    const incomeResponse = await axios.get('/api/income-categories');
    incomeCategories.value = incomeResponse.data || [];
    
    // 获取支出分类
    const expenseResponse = await axios.get('/api/expense-categories');
    expenseCategories.value = expenseResponse.data || [];
    
    // 如果当前没有选择分类，自动选择第一个
    if (!form.category_id) {
      const currentCategories = form.type === 'income' ? incomeCategories.value : expenseCategories.value;
      if (currentCategories.length > 0) {
        form.category_id = currentCategories[0].id;
      }
    }
  } catch (error) {
    console.error('获取分类列表失败:', error);
  }
};

// 添加新分类
const addNewCategory = async (): Promise<void> => {
  if (!newCategory.name.trim()) return;
  
  try {
    // 根据当前类型判断添加到哪个分类表
    const endpoint = form.type === 'income' ? '/api/income-categories' : '/api/expense-categories';
    
    const response = await axios.post(endpoint, {
      name: newCategory.name.trim(),
      color: newCategory.color
    });
    
    // 将新分类添加到列表
    if (form.type === 'income') {
      incomeCategories.value.push(response.data);
    } else {
      expenseCategories.value.push(response.data);
    }
    
    // 自动选择新创建的分类
    form.category_id = response.data.id;
    
    // 关闭弹窗并重置表单
    showAddCategoryModal.value = false;
    newCategory.name = '';
    newCategory.color = '#1890ff';
    
  } catch (error) {
    console.error('添加分类失败:', error);
    alert('添加分类失败: ' + (error as any).response?.data?.message || (error as Error).message);
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
    category_id: null,
    description: '',
    source: '',
    merchant: '',
    payment_method: '现金',
    tags: [],
    transaction_type: 'single',
    period: 'monthly',
    installment_info: {
      total_amount: '',
      total_periods: '',
      current_period: 1
    },
    account_id: accounts.value.length > 0 ? accounts.value[0].id : null,
    status: 'settled'
  });
  
  // 设置默认分类
  const currentCategories = form.type === 'income' ? incomeCategories.value : expenseCategories.value;
  if (currentCategories.length > 0) {
    form.category_id = currentCategories[0].id;
  }
};

// 取消
const cancel = (): void => {
  emit('cancel');
};

// 提交表单
const handleSubmit = async (): Promise<void> => {
  if (!isFormValid.value) return;
  
  try {
    isSubmitting.value = true;
    
    // 构建提交数据
    const submitData = { ...form };
    
    // 将标签转换为JSON字符串
    if (submitData.tags) {
      submitData.tags = JSON.stringify(submitData.tags);
    }
    
    // 将分期信息转换为JSON字符串
    if (submitData.transaction_type === 'installment' && submitData.installment_info) {
      submitData.installment_info = JSON.stringify(submitData.installment_info);
    }
    
    // API端点和方法
    let endpoint = '/api/income-expense';
    let method = 'post';
    
    // 如果是编辑模式
    if (props.editData && props.editData.id) {
      endpoint = `/api/income-expense/${props.editData.id}`;
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

// 组件挂载时获取账户列表和分类列表
onMounted(() => {
  Promise.all([
    fetchAccounts(),
    fetchCategories()
  ]);
});
</script>
