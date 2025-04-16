<template>
  <div class="transaction-form">
    <el-form 
      ref="formRef" 
      :model="form" 
      :rules="rules" 
      label-position="top"
      :disabled="disabled"
    >
      <el-form-item label="交易类型" prop="type">
        <el-radio-group v-model="form.type" class="type-radio-group">
          <el-radio-button :label="1">
            <el-icon><ArrowDown /></el-icon> 收入
          </el-radio-button>
          <el-radio-button :label="2">
            <el-icon><ArrowUp /></el-icon> 支出
          </el-radio-button>
        </el-radio-group>
      </el-form-item>
      
      <el-form-item label="金额" prop="amount">
        <el-input-number 
          v-model="form.amount" 
          :min="0.01" 
          :precision="2"
          :step="100"
          style="width: 100%"
          controls-position="right"
        />
      </el-form-item>
      
      <el-form-item label="执行日期" prop="execution_date">
        <el-date-picker 
          v-model="form.execution_date" 
          type="date" 
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          placeholder="选择日期"
          style="width: 100%"
        />
      </el-form-item>
      
      <el-form-item label="描述" prop="description">
        <el-input 
          v-model="form.description" 
          placeholder="请输入交易描述"
          :maxlength="255"
          show-word-limit
        />
      </el-form-item>
      
      <el-form-item label="周期性交易">
        <el-switch v-model="form.is_recurring" />
      </el-form-item>
      
      <template v-if="form.is_recurring">
        <el-form-item label="周期类型" prop="recurrence_type">
          <el-select v-model="form.recurrence_type" placeholder="请选择周期类型" style="width: 100%">
            <el-option :value="1" label="每日" />
            <el-option :value="2" label="每周" />
            <el-option :value="3" label="每月" />
            <el-option :value="4" label="每年" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="周期间隔" prop="recurrence_interval">
          <el-input-number 
            v-model="form.recurrence_interval" 
            :min="1" 
            :precision="0"
            controls-position="right"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="结束日期" prop="recurrence_end_date">
          <el-date-picker 
            v-model="form.recurrence_end_date" 
            type="date" 
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            placeholder="不填写则无限期"
            style="width: 100%"
          />
        </el-form-item>
      </template>
      
      <div class="form-actions">
        <el-button @click="$emit('cancel')">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="loading">保存</el-button>
      </div>
    </el-form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { ArrowUp, ArrowDown } from '@element-plus/icons-vue';
import type { FormInstance, FormRules } from 'element-plus';
import type { Transaction } from '@/stores/transaction';
import { formatDate } from '@/services/dateUtils';

interface Props {
  transaction?: Partial<Transaction>;
  loading?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  disabled: false
});

const emit = defineEmits<{
  (e: 'submit', formData: any): void;
  (e: 'cancel'): void;
}>();

// 表单实例
const formRef = ref<FormInstance>();

// 表单数据
const form = reactive({
  type: 1,
  amount: 0,
  execution_date: formatDate(new Date()),
  description: '',
  is_recurring: false,
  recurrence_type: 1,
  recurrence_interval: 1,
  recurrence_end_date: ''
});

// 验证规则
const rules = reactive<FormRules>({
  type: [
    { required: true, message: '请选择交易类型', trigger: 'change' }
  ],
  amount: [
    { required: true, message: '请输入金额', trigger: 'blur' },
    { type: 'number', min: 0.01, message: '金额必须大于0', trigger: 'blur' }
  ],
  execution_date: [
    { required: true, message: '请选择执行日期', trigger: 'change' }
  ],
  recurrence_type: [
    { required: true, message: '请选择周期类型', trigger: 'change' }
  ],
  recurrence_interval: [
    { required: true, message: '请输入周期间隔', trigger: 'blur' },
    { type: 'number', min: 1, message: '周期间隔必须大于0', trigger: 'blur' }
  ]
});

// 如果有传入交易数据，则填充表单
watch(() => props.transaction, (transaction) => {
  if (transaction) {
    Object.keys(form).forEach(key => {
      if (key in transaction && transaction[key as keyof Transaction] !== undefined) {
        form[key as keyof typeof form] = transaction[key as keyof Transaction] as any;
      }
    });
    
    // 如果没有周期性字段，设置默认值
    if (form.is_recurring && !form.recurrence_type) {
      form.recurrence_type = 1;
    }
    if (form.is_recurring && !form.recurrence_interval) {
      form.recurrence_interval = 1;
    }
  }
}, { immediate: true });

// 提交表单
const submitForm = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate((valid, fields) => {
    if (valid) {
      // 如果不是周期性交易，清空相关字段
      const formData = { ...form };
      if (!formData.is_recurring) {
        formData.recurrence_type = 0;
        formData.recurrence_interval = 0;
        formData.recurrence_end_date = '';
      }
      
      emit('submit', formData);
    } else {
      console.error('表单验证失败', fields);
      ElMessage.error('请完善表单信息');
    }
  });
};
</script>

<style scoped>
.transaction-form {
  padding: 20px 0;
}

.type-radio-group {
  width: 100%;
  display: flex;
}

.type-radio-group :deep(.el-radio-button) {
  flex: 1;
}

.type-radio-group :deep(.el-radio-button__inner) {
  width: 100%;
}

.form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .transaction-form {
    padding: 16px 0;
  }
  
  .form-actions {
    margin-top: 16px;
  }
  
  .form-actions .el-button {
    flex: 1;
  }
}
</style>
