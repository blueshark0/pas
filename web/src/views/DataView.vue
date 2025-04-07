<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">数据管理</h1>
    
    <!-- 功能卡片 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <!-- 数据备份 -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center mb-4">
          <div class="p-2 rounded-full bg-indigo-100 text-indigo-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-800 ml-3">数据备份</h2>
        </div>
        <p class="text-gray-600 mb-4">创建系统数据的完整备份，以防数据丢失。</p>
        <button 
          @click="createBackup" 
          :disabled="isCreatingBackup"
          class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:bg-indigo-300 flex items-center justify-center"
        >
          <span v-if="isCreatingBackup">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            备份中...
          </span>
          <span v-else>创建备份</span>
        </button>
      </div>
      
      <!-- 数据恢复 -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center mb-4">
          <div class="p-2 rounded-full bg-green-100 text-green-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-800 ml-3">数据恢复</h2>
        </div>
        <p class="text-gray-600 mb-4">从之前创建的备份中恢复系统数据。</p>
        <button 
          @click="showRestoreModal = true" 
          :disabled="backups.length === 0"
          class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 disabled:bg-green-300"
        >
          从备份恢复
        </button>
      </div>
      
      <!-- 数据导出/导入 -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center mb-4">
          <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-800 ml-3">数据导出/导入</h2>
        </div>
        <p class="text-gray-600 mb-4">导出数据为CSV/Excel格式，或从文件导入数据。</p>
        <div class="flex space-x-2">
          <button 
            @click="showExportModal = true" 
            class="flex-1 bg-yellow-600 text-white py-2 px-3 rounded-md hover:bg-yellow-700 text-sm"
          >
            导出数据
          </button>
          <button 
            @click="showImportModal = true" 
            class="flex-1 bg-yellow-600 text-white py-2 px-3 rounded-md hover:bg-yellow-700 text-sm"
          >
            导入数据
          </button>
        </div>
      </div>
    </div>
    
    <!-- 备份列表 -->
    <div class="bg-white p-6 rounded-lg shadow-md">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">备份历史</h2>
        <button 
          @click="loadBackups" 
          class="text-indigo-600 hover:text-indigo-800"
        >
          刷新
        </button>
      </div>
      
      <div v-if="loading" class="py-10 text-center text-gray-500">
        <p>加载中...</p>
      </div>
      
      <div v-else-if="backups.length === 0" class="py-10 text-center text-gray-500">
        <p>暂无备份记录</p>
      </div>
      
      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  备份名称
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  备份时间
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  文件大小
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  描述
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="backup in backups" :key="backup.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ backup.file_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDateTime(backup.backup_time) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatFileSize(backup.file_size) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                  {{ backup.description || '无' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button 
                    @click="downloadBackup(backup)" 
                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                    title="下载"
                  >
                    下载
                  </button>
                  <button 
                    @click="confirmRestore(backup)" 
                    class="text-green-600 hover:text-green-900 mr-3"
                    title="恢复"
                  >
                    恢复
                  </button>
                  <button 
                    @click="confirmDeleteBackup(backup)" 
                    class="text-red-600 hover:text-red-900"
                    title="删除"
                  >
                    删除
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- 创建备份弹窗 -->
    <div v-if="showBackupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">创建备份</h3>
        
        <form @submit.prevent="handleCreateBackup">
          <div class="space-y-4">
            <div>
              <label for="backup_description" class="block text-sm font-medium text-gray-700">备份描述</label>
              <textarea 
                id="backup_description" 
                v-model="backupForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="可选，用于标识此备份的用途或内容"
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showBackupModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '备份中...' : '确认备份' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 恢复备份弹窗 -->
    <div v-if="showRestoreModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">从备份恢复</h3>
        
        <div v-if="backups.length === 0" class="text-center py-6 text-gray-500">
          <p>暂无可用备份</p>
        </div>
        
        <form v-else @submit.prevent="handleRestore">
          <div class="space-y-4">
            <div>
              <label for="restore_backup" class="block text-sm font-medium text-gray-700">选择备份</label>
              <select 
                id="restore_backup" 
                v-model="restoreForm.backup_id"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">请选择备份</option>
                <option v-for="backup in backups" :key="backup.id" :value="backup.id">
                  {{ backup.file_name }} ({{ formatDateTime(backup.backup_time) }})
                </option>
              </select>
            </div>
            
            <div v-if="selectedBackup" class="bg-yellow-50 p-3 rounded-md border border-yellow-200">
              <p class="text-sm text-yellow-800">
                <strong>警告:</strong> 恢复数据将覆盖当前所有数据，此操作不可撤销。建议在恢复前先创建一个新的备份。
              </p>
              <div class="mt-2 text-sm text-gray-700">
                <p><strong>备份时间:</strong> {{ formatDateTime(selectedBackup.backup_time) }}</p>
                <p><strong>文件大小:</strong> {{ formatFileSize(selectedBackup.file_size) }}</p>
                <p v-if="selectedBackup.description"><strong>描述:</strong> {{ selectedBackup.description }}</p>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showRestoreModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting || !restoreForm.backup_id"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-green-300"
            >
              {{ isSubmitting ? '恢复中...' : '确认恢复' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 导出数据弹窗 -->
    <div v-if="showExportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">导出数据</h3>
        
        <form @submit.prevent="handleExport">
          <div class="space-y-4">
            <div>
              <label for="export_type" class="block text-sm font-medium text-gray-700">数据类型</label>
              <select 
                id="export_type" 
                v-model="exportForm.type"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="all">所有数据</option>
                <option value="incomes">收入记录</option>
                <option value="expenses">支出记录</option>
                <option value="accounts">账户信息</option>
                <option value="budgets">预算信息</option>
              </select>
            </div>
            
            <div>
              <label for="export_format" class="block text-sm font-medium text-gray-700">文件格式</label>
              <select 
                id="export_format" 
                v-model="exportForm.format"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="csv">CSV</option>
                <option value="excel">Excel</option>
                <option value="json">JSON</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">时间范围</label>
              <div class="mt-1 flex items-center gap-2">
                <input 
                  type="date" 
                  v-model="exportForm.start_date"
                  class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
                <span class="text-gray-500">至</span>
                <input 
                  type="date" 
                  v-model="exportForm.end_date"
                  class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showExportModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:bg-yellow-300"
            >
              {{ isSubmitting ? '导出中...' : '确认导出' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 导入数据弹窗 -->
    <div v-if="showImportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">导入数据</h3>
        
        <form @submit.prevent="handleImport">
          <div class="space-y-4">
            <div>
              <label for="import_type" class="block text-sm font-medium text-gray-700">数据类型</label>
              <select 
                id="import_type" 
                v-model="importForm.type"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="incomes">收入记录</option>
                <option value="expenses">支出记录</option>
                <option value="accounts">账户信息</option>
                <option value="budgets">预算信息</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">选择文件</label>
              <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                <div class="space-y-1 text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <div class="flex text-sm text-gray-600">
                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                      <span>上传文件</span>
                      <input id="file-upload" type="file" class="sr-only" @change="handleFileChange" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json" />
                    </label>
                    <p class="pl-1">或拖放文件到此处</p>
                  </div>
                  <p class="text-xs text-gray-500">
                    支持 CSV, Excel 或 JSON 文件
                  </p>
                </div>
              </div>
              <div v-if="importForm.file" class="mt-2 text-sm text-gray-600">
                已选择文件: {{ importForm.file.name }} ({{ formatFileSize(importForm.file.size) }})
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">导入选项</label>
              <div class="mt-2">
                <div class="flex items-center">
                  <input id="replace" type="radio" value="replace" v-model="importForm.mode" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                  <label for="replace" class="ml-2 text-sm text-gray-700">替换现有数据</label>
                </div>
                <div class="flex items-center mt-2">
                  <input id="append" type="radio" value="append" v-model="importForm.mode" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                  <label for="append" class="ml-2 text-sm text-gray-700">追加到现有数据</label>
                </div>
              </div>
            </div>
            
            <div class="bg-yellow-50 p-3 rounded-md border border-yellow-200">
              <p class="text-sm text-yellow-800">
                <strong>注意:</strong> 导入数据可能会修改或覆盖现有数据。建议在导入前先创建一个备份。
              </p>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showImportModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting || !importForm.file"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:bg-yellow-300"
            >
              {{ isSubmitting ? '导入中...' : '确认导入' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 确认删除备份弹窗 -->
    <div v-if="showDeleteBackupConfirm" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">确认删除备份</h3>
        <p class="text-gray-600">
          确定要删除备份 <span class="font-medium">{{ currentBackup?.file_name }}</span> 吗？此操作不可恢复。
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            @click="showDeleteBackupConfirm = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="handleDeleteBackup" 
            :disabled="isSubmitting"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-red-300"
          >
            {{ isSubmitting ? '删除中...' : '确认删除' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

interface Backup {
  id: number;
  file_name: string;
  file_path: string;
  file_size: number;
  description?: string;
  backup_time: string;
}

interface BackupForm {
  description: string;
}

interface RestoreForm {
  backup_id: number | string;
}

interface ExportForm {
  type: string;
  format: string;
  start_date: string;
  end_date: string;
}

interface ImportForm {
  type: string;
  file: File | null;
  mode: string;
}

// 状态
const loading = ref<boolean>(true);
const isSubmitting = ref<boolean>(false);
const isCreatingBackup = ref<boolean>(false);
const showBackupModal = ref<boolean>(false);
const showRestoreModal = ref<boolean>(false);
const showExportModal = ref<boolean>(false);
const showImportModal = ref<boolean>(false);
const showDeleteBackupConfirm = ref<boolean>(false);
const backups = ref<Backup[]>([]);
const currentBackup = ref<Backup | null>(null);

// 备份表单
const backupForm = reactive<BackupForm>({
  description: ''
});

// 恢复表单
const restoreForm = reactive<RestoreForm>({
  backup_id: ''
});

// 导出表单
const exportForm = reactive<ExportForm>({
  type: 'all',
  format: 'csv',
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0]
});

// 导入表单
const importForm = reactive<ImportForm>({
  type: 'expenses',
  file: null,
  mode: 'append'
});

// 选中的备份
const selectedBackup = computed(() => {
  if (!restoreForm.backup_id) return null;
  return backups.value.find(b => b.id === Number(restoreForm.backup_id)) || null;
});

// 格式化日期时间
const formatDateTime = (dateTimeStr: string): string => {
  const date = new Date(dateTimeStr);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

// 格式化文件大小
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// 加载备份列表
const loadBackups = async () => {
  loading.value = true;
  
  try {
    const response = await axios.get('/api/backups');
    backups.value = response.data;
  } catch (error) {
    console.error('加载备份列表失败:', error);
  } finally {
    loading.value = false;
  }
};

// 创建备份
const createBackup = () => {
  showBackupModal.value = true;
};

// 处理文件选择
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    importForm.file = target.files[0];
  }
};

// 确认删除备份
const confirmDeleteBackup = (backup: Backup) => {
  currentBackup.value = backup;
  showDeleteBackupConfirm.value = true;
};

// 确认恢复
const confirmRestore = (backup: Backup) => {
  restoreForm.backup_id = backup.id;
  showRestoreModal.value = true;
};

// 下载备份
const downloadBackup = async (backup: Backup) => {
  try {
    const response = await axios.get(`/api/backups/${backup.id}/download`, {
      responseType: 'blob'
    });
    
    // 创建下载链接
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', backup.file_name);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('下载备份失败:', error);
    alert('下载备份失败');
  }
};

// 处理创建备份
const handleCreateBackup = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  isCreatingBackup.value = true;
  
  try {
    await axios.post('/api/backups', {
      description: backupForm.description
    });
    
    // 刷新备份列表
    await loadBackups();
    
    // 重置表单和状态
    backupForm.description = '';
    showBackupModal.value = false;
    
    alert('备份创建成功');
  } catch (error) {
    console.error('创建备份失败:', error);
    alert('创建备份失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
    isCreatingBackup.value = false;
  }
};

// 处理恢复备份
const handleRestore = async () => {
  if (isSubmitting.value || !restoreForm.backup_id) return;
  
  if (!confirm('警告: 恢复操作将覆盖当前所有数据，确定要继续吗？')) {
    return;
  }
  
  isSubmitting.value = true;
  
  try {
    await axios.post(`/api/backups/${restoreForm.backup_id}/restore`);
    
    // 重置表单和状态
    restoreForm.backup_id = '';
    showRestoreModal.value = false;
    
    alert('数据恢复成功，页面将刷新以加载最新数据');
    window.location.reload();
  } catch (error) {
    console.error('恢复备份失败:', error);
    alert('恢复备份失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理导出数据
const handleExport = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  try {
    const response = await axios.get('/api/export', {
      params: {
        type: exportForm.type,
        format: exportForm.format,
        start_date: exportForm.start_date,
        end_date: exportForm.end_date
      },
      responseType: 'blob'
    });
    
    // 设置文件名
    const contentDisposition = response.headers['content-disposition'];
    let filename = `export_${exportForm.type}_${new Date().toISOString().slice(0,10)}.${exportForm.format}`;
    
    if (contentDisposition) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(contentDisposition);
      if (matches != null && matches[1]) { 
        filename = matches[1].replace(/['"]/g, '');
      }
    }
    
    // 创建下载链接
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // 重置状态
    showExportModal.value = false;
  } catch (error) {
    console.error('导出数据失败:', error);
    alert('导出数据失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理导入数据
const handleImport = async () => {
  if (isSubmitting.value || !importForm.file) return;
  
  isSubmitting.value = true;
  
  const formData = new FormData();
  formData.append('file', importForm.file);
  formData.append('type', importForm.type);
  formData.append('mode', importForm.mode);
  
  try {
    await axios.post('/api/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    // 重置表单和状态
    importForm.file = null;
    showImportModal.value = false;
    
    alert('数据导入成功');
  } catch (error) {
    console.error('导入数据失败:', error);
    alert('导入数据失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理删除备份
const handleDeleteBackup = async () => {
  if (isSubmitting.value || !currentBackup.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.delete(`/api/backups/${currentBackup.value.id}`);
    
    // 刷新备份列表
    await loadBackups();
    
    // 重置状态
    showDeleteBackupConfirm.value = false;
    currentBackup.value = null;
  } catch (error) {
    console.error('删除备份失败:', error);
    alert('删除备份失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 组件挂载时加载数据
onMounted(() => {
  loadBackups();
});
</script>