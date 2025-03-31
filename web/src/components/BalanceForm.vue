<template>
  <div class="balance-form">
    <form @submit.prevent="handleSubmit">
      <!-- 账户名称 -->
      <div class="mb-4">
        <label for="name" class="block text-sm font-medium text-gray-700">账户名称</label>
        <input 
          type="text" 
          id="name" 
          v-model="form.name" 
          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          required
        />
      </div>
      
      <!-- 账户类型 -->
      <div class="mb-4">
        <label for="type" class="block text-sm font-medium text-gray-700">账户类型</label>
        <select 
          id="type" 
          v-model="form.type" 
          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          required
        >
          <option v-for="option in accountTypes" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
      
      <!-- 账户余额 -->
      <div class="mb-4">
        <label for="amount" class="block text-sm font-medium text-gray-700">账户余额</label>
        <div class="mt-1 relative rounded-md shadow-sm">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-gray-500 sm:text-sm">¥</span>
          </div>
          <input 
            type="number" 
            step="0.01" 
            id="amount" 
            v-model="form.amount" 
            class="pl-7 mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            required
          />
        </div>
      </div>
      
      <!-- 描述 -->
      <div class="mb-4">
        <label for="description" class="block text-sm font-medium text-gray-700">描述</label>
        <textarea 
          id="description" 
          v-model="form.description" 
          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
          rows="2"
        ></textarea>
      </div>
      
      <!-- 按钮 -->
      <div class="flex justify-end space-x-3 mt-6">
        <button 
          type="button" 
          @click="cancel" 
          class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          取消
        </button>
        <button 
          type="submit" 
          class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? '提交中...' : '保存' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';

interface AccountTypeOption {
  label: string;
  value: string;
}

interface BalanceFormData {
  name: string;
  type: string;
  amount: number | string;
  description: string;
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

// 账户类型选项
const accountTypes: AccountTypeOption[] = [
  { label: '现金', value: 'cash' },
  { label: '银行卡', value: 'bank' },
  { label: '支付宝', value: 'alipay' },
  { label: '微信', value: 'wechat' },
  { label: '信用卡', value: 'credit' },
  { label: '其他', value: 'other' }
];

// 状态
const isSubmitting = ref<boolean>(false);

// 表单数据
const form = reactive<BalanceFormData>({
  name: '',
  type: accountTypes[0].value,
  amount: '',
  description: ''
});

// 加载编辑数据
const loadEditData = (): void => {
  if (props.editData) {
    // 复制数据到表单
    Object.keys(form).forEach(key => {
      if (key in props.editData) {
        // @ts-ignore - 动态赋值
        form[key] = props.editData[key];
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

// 重置表单
const resetForm = (): void => {
  Object.assign(form, {
    name: '',
    type: accountTypes[0].value,
    amount: '',
    description: ''
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
    let endpoint = '/api/balance/add';
    let method = 'post';
    
    // 如果是编辑模式
    if (props.editData && props.editData.id) {
      endpoint = `/api/balance/update/${props.editData.id}`;
      method = 'put';
    }
    
    // 提交到API
    const response = await axios[method](endpoint, submitData);
    
    // 成功处理
    console.log('账户保存成功:', response.data);
    emit('save-success', response.data);
    
    // 如果不是编辑模式，重置表单
    if (!props.editData) {
      resetForm();
    }
  } catch (error) {
    console.error('保存账户失败:', error);
    alert('保存账户失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
/* 移动端适配 */
@media (max-width: 640px) {
  .balance-form {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
}
</style>
