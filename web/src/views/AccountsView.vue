<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">账户管理</h1>
    
    <!-- 账户摘要统计 -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
      <div class="flex flex-wrap justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">账户总览</h2>
        <button 
          @click="showAddAccountModal = true"
          class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 flex items-center"
        >
          <span class="mr-1">+</span> 添加账户
        </button>
      </div>
      
      <div v-if="loading" class="py-6 text-center text-gray-500">
        <p>加载中...</p>
      </div>
      
      <div v-else>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-gray-50 p-4 rounded-md">
            <p class="text-sm text-gray-500 mb-1">总资产</p>
            <p class="text-2xl font-semibold text-gray-800">¥{{ formatNumber(totalAssets) }}</p>
          </div>
          <div v-for="(type, index) in accountTypesSummary" :key="index" class="bg-gray-50 p-4 rounded-md">
            <p class="text-sm text-gray-500 mb-1">{{ getAccountTypeText(type.type) }}</p>
            <p class="text-2xl font-semibold" :class="getTextColorByType(type.type)">
              ¥{{ formatNumber(type.amount) }}
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 账户列表 -->
    <div class="bg-white p-6 rounded-lg shadow-md">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">账户列表</h2>
        <button 
          @click="showTransferModal = true"
          class="text-indigo-600 py-2 px-4 rounded-md border border-indigo-600 hover:bg-indigo-50"
        >
          账户间转账
        </button>
      </div>
      
      <div v-if="loading" class="py-10 text-center text-gray-500">
        <p>加载中...</p>
      </div>
      
      <div v-else-if="accounts.length === 0" class="py-10 text-center text-gray-500">
        <p>暂无账户信息</p>
        <button 
          @click="showAddAccountModal = true" 
          class="mt-4 text-indigo-600 hover:text-indigo-800"
        >
          添加账户
        </button>
      </div>
      
      <div v-else>
        <!-- 账户卡片列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="account in accounts" 
            :key="account.id" 
            class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <div 
              class="p-4 text-white" 
              :style="`background-color: ${account.color || getColorByType(account.type)}`"
            >
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-bold text-lg">{{ account.name }}</h3>
                  <p class="text-sm opacity-90">{{ getAccountTypeText(account.type) }}</p>
                </div>
                <div class="flex space-x-2">
                  <button 
                    @click="editAccount(account)" 
                    class="text-white opacity-80 hover:opacity-100"
                    title="编辑"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                  </button>
                  <button 
                    @click="confirmDelete(account)" 
                    class="text-white opacity-80 hover:opacity-100"
                    title="删除"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <div class="p-4">
              <div class="flex justify-between items-center mb-3">
                <p class="text-gray-600 text-sm">当前余额</p>
                <p class="text-xl font-semibold">¥{{ formatNumber(account.amount) }}</p>
              </div>
              <div v-if="account.description" class="text-sm text-gray-500 mb-3">
                {{ account.description }}
              </div>
              <div class="flex justify-between">
                <button 
                  @click="showAccountDetail(account)" 
                  class="text-indigo-600 text-sm hover:text-indigo-800"
                >
                  查看明细
                </button>
                <button 
                  @click="adjustBalance(account)" 
                  class="text-indigo-600 text-sm hover:text-indigo-800"
                >
                  调整余额
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 添加/编辑账户弹窗 -->
    <div v-if="showAddAccountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          {{ editMode ? '编辑账户' : '添加账户' }}
        </h3>
        
        <form @submit.prevent="handleSubmitAccount">
          <div class="space-y-4">
            <!-- 账户名称 -->
            <div>
              <label for="account_name" class="block text-sm font-medium text-gray-700">账户名称</label>
              <input 
                type="text" 
                id="account_name" 
                v-model="accountForm.name"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <!-- 账户类型 -->
            <div>
              <label for="account_type" class="block text-sm font-medium text-gray-700">账户类型</label>
              <select 
                id="account_type" 
                v-model="accountForm.type"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="cash">现金</option>
                <option value="card">银行卡</option>
                <option value="credit">信用卡</option>
                <option value="virtual">虚拟账户</option>
                <option value="other">其他</option>
              </select>
            </div>
            
            <!-- 余额 -->
            <div>
              <label for="account_amount" class="block text-sm font-medium text-gray-700">余额</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">¥</span>
                </div>
                <input 
                  type="number" 
                  step="0.01" 
                  id="account_amount" 
                  v-model="accountForm.amount"
                  required
                  class="pl-7 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            
            <!-- 描述 -->
            <div>
              <label for="account_description" class="block text-sm font-medium text-gray-700">描述</label>
              <textarea 
                id="account_description" 
                v-model="accountForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              ></textarea>
            </div>
            
            <!-- 颜色 -->
            <div>
              <label for="account_color" class="block text-sm font-medium text-gray-700">颜色</label>
              <div class="mt-1 flex items-center space-x-2">
                <input 
                  type="color" 
                  id="account_color" 
                  v-model="accountForm.color"
                  class="h-8 w-8 border border-gray-300 rounded cursor-pointer"
                />
                <div 
                  v-for="color in predefinedColors" 
                  :key="color" 
                  :style="`background-color: ${color}`"
                  class="h-6 w-6 rounded-full cursor-pointer hover:opacity-80"
                  @click="accountForm.color = color"
                ></div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showAddAccountModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '提交中...' : '确定' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 转账弹窗 -->
    <div v-if="showTransferModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">账户间转账</h3>
        
        <form @submit.prevent="handleTransfer">
          <div class="space-y-4">
            <!-- 转出账户 -->
            <div>
              <label for="from_account" class="block text-sm font-medium text-gray-700">转出账户</label>
              <select 
                id="from_account" 
                v-model="transferForm.from_account_id"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">请选择账户</option>
                <option v-for="account in accounts" :key="`from-${account.id}`" :value="account.id">
                  {{ account.name }} (余额: ¥{{ formatNumber(account.amount) }})
                </option>
              </select>
            </div>
            
            <!-- 转入账户 -->
            <div>
              <label for="to_account" class="block text-sm font-medium text-gray-700">转入账户</label>
              <select 
                id="to_account" 
                v-model="transferForm.to_account_id"
                required
                :disabled="!transferForm.from_account_id"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="">请选择账户</option>
                <option 
                  v-for="account in accounts" 
                  :key="`to-${account.id}`" 
                  :value="account.id"
                  :disabled="account.id === transferForm.from_account_id"
                >
                  {{ account.name }} (余额: ¥{{ formatNumber(account.amount) }})
                </option>
              </select>
            </div>
            
            <!-- 转账金额 -->
            <div>
              <label for="transfer_amount" class="block text-sm font-medium text-gray-700">转账金额</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">¥</span>
                </div>
                <input 
                  type="number" 
                  step="0.01" 
                  id="transfer_amount" 
                  v-model="transferForm.amount"
                  required
                  min="0.01"
                  :max="getMaxTransferAmount"
                  class="pl-7 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <p v-if="transferForm.from_account_id" class="mt-1 text-sm text-gray-500">
                可转账金额: ¥{{ formatNumber(getMaxTransferAmount) }}
              </p>
            </div>
            
            <!-- 转账日期 -->
            <div>
              <label for="transfer_date" class="block text-sm font-medium text-gray-700">转账日期</label>
              <input 
                type="date" 
                id="transfer_date" 
                v-model="transferForm.date"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <!-- 转账说明 -->
            <div>
              <label for="transfer_description" class="block text-sm font-medium text-gray-700">转账说明</label>
              <textarea 
                id="transfer_description" 
                v-model="transferForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showTransferModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting || !isTransferValid"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '提交中...' : '确定转账' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 调整余额弹窗 -->
    <div v-if="showAdjustModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          调整账户余额 - {{ currentAccount ? currentAccount.name : '' }}
        </h3>
        
        <form v-if="currentAccount" @submit.prevent="handleAdjustBalance">
          <div class="space-y-4">
            <!-- 当前余额 -->
            <div>
              <label class="block text-sm font-medium text-gray-700">当前余额</label>
              <p class="mt-1 text-lg font-medium">¥{{ formatNumber(currentAccount.amount) }}</p>
            </div>
            
            <!-- 新余额 -->
            <div>
              <label for="new_amount" class="block text-sm font-medium text-gray-700">新余额</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500 sm:text-sm">¥</span>
                </div>
                <input 
                  type="number" 
                  step="0.01" 
                  id="new_amount" 
                  v-model="adjustForm.amount"
                  required
                  class="pl-7 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            
            <!-- 调整日期 -->
            <div>
              <label for="adjust_date" class="block text-sm font-medium text-gray-700">调整日期</label>
              <input 
                type="date" 
                id="adjust_date" 
                v-model="adjustForm.date"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            
            <!-- 调整说明 -->
            <div>
              <label for="adjust_description" class="block text-sm font-medium text-gray-700">调整说明</label>
              <textarea 
                id="adjust_description" 
                v-model="adjustForm.description"
                rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button" 
              @click="showAdjustModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              取消
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300"
            >
              {{ isSubmitting ? '提交中...' : '确定调整' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- 确认删除弹窗 -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">确认删除</h3>
        <p class="text-gray-600">
          确定要删除账户 <span class="font-medium">{{ currentAccount?.name }}</span> 吗？此操作不可恢复。
        </p>
        <p class="mt-2 text-sm text-red-500">注意：如果此账户有关联的收支记录，将无法删除。</p>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            @click="showDeleteConfirm = false" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            取消
          </button>
          <button 
            @click="handleDeleteAccount" 
            :disabled="isSubmitting"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:bg-red-300"
          >
            {{ isSubmitting ? '删除中...' : '确认删除' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- 账户详情弹窗 -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">
            账户明细 - {{ currentAccount?.name }}
          </h3>
          <button 
            @click="showDetailModal = false" 
            class="text-gray-500 hover:text-gray-700"
          >
            <span class="text-2xl">&times;</span>
          </button>
        </div>
        
        <div v-if="detailLoading" class="py-10 text-center text-gray-500">
          <p>加载中...</p>
        </div>
        
        <div v-else-if="transactions.length === 0" class="py-10 text-center text-gray-500">
          <p>暂无交易记录</p>
        </div>
        
        <div v-else>
          <!-- 交易记录列表 -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    日期
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    类型
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    分类
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    描述
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    金额
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(transaction.date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      :class="[
                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium', 
                        transaction.type === 'income' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ transaction.type === 'income' ? '收入' : '支出' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ transaction.category_name || '未分类' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ transaction.description || '无' }}
                  </td>
                  <td 
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm font-medium text-right', 
                      transaction.type === 'income' ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ transaction.type === 'income' ? '+' : '-' }}¥{{ formatNumber(transaction.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- 分页 -->
          <div class="mt-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">共 {{ totalTransactions }} 条记录</p>
            <div class="flex space-x-2">
              <button 
                @click="changePage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="px-3 py-1 border rounded text-sm"
                :class="currentPage <= 1 ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              >
                上一页
              </button>
              <span class="px-3 py-1 text-sm text-gray-600">
                {{ currentPage }} / {{ totalPages }}
              </span>
              <button 
                @click="changePage(currentPage + 1)"
                :disabled="currentPage >= totalPages"
                class="px-3 py-1 border rounded text-sm"
                :class="currentPage >= totalPages ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              >
                下一页
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

interface Account {
  id: number;
  name: string;
  type: string;
  amount: number;
  color?: string;
  description?: string;
  icon?: string;
  date: string;
  initial_balance: number;
}

interface Transaction {
  id: number;
  type: string;
  amount: number;
  date: string;
  category_id: number;
  category_name?: string;
  description?: string;
  account_id: number;
}

interface AccountForm {
  name: string;
  type: string;
  amount: number | string;
  description: string;
  color: string;
}

interface TransferForm {
  from_account_id: number | null;
  to_account_id: number | null;
  amount: number | string;
  date: string;
  description: string;
}

interface AdjustForm {
  amount: number | string;
  date: string;
  description: string;
}

const router = useRouter();
const accounts = ref<Account[]>([]);
const loading = ref<boolean>(true);
const isSubmitting = ref<boolean>(false);
const showAddAccountModal = ref<boolean>(false);
const showTransferModal = ref<boolean>(false);
const showAdjustModal = ref<boolean>(false);
const showDeleteConfirm = ref<boolean>(false);
const showDetailModal = ref<boolean>(false);
const editMode = ref<boolean>(false);
const currentAccount = ref<Account | null>(null);
const transactions = ref<Transaction[]>([]);
const detailLoading = ref<boolean>(false);
const currentPage = ref<number>(1);
const totalTransactions = ref<number>(0);
const pageSize = ref<number>(10);

// 预定义颜色
const predefinedColors = [
  '#4299e1', // 蓝色
  '#48bb78', // 绿色
  '#ed8936', // 橙色
  '#9f7aea', // 紫色
  '#f56565', // 红色
  '#38b2ac', // 青色
  '#667eea', // 靛蓝色
  '#d53f8c'  // 粉色
];

// 账户表单
const accountForm = reactive<AccountForm>({
  name: '',
  type: 'card',
  amount: '',
  description: '',
  color: '#4299e1'
});

// 转账表单
const transferForm = reactive<TransferForm>({
  from_account_id: null,
  to_account_id: null,
  amount: '',
  date: new Date().toISOString().substr(0, 10),
  description: ''
});

// 调整余额表单
const adjustForm = reactive<AdjustForm>({
  amount: '',
  date: new Date().toISOString().substr(0, 10),
  description: ''
});

// 计算总资产
const totalAssets = computed(() => {
  return accounts.value.reduce((sum, account) => sum + account.amount, 0);
});

// 计算总页数
const totalPages = computed(() => {
  return Math.ceil(totalTransactions.value / pageSize.value) || 1;
});

// 计算按账户类型的统计
const accountTypesSummary = computed(() => {
  const types: Record<string, { type: string, amount: number }> = {};
  
  accounts.value.forEach(account => {
    if (!types[account.type]) {
      types[account.type] = { type: account.type, amount: 0 };
    }
    types[account.type].amount += account.amount;
  });
  
  return Object.values(types).sort((a, b) => b.amount - a.amount).slice(0, 3);
});

// 转账表单是否有效
const isTransferValid = computed(() => {
  return (
    transferForm.from_account_id !== null &&
    transferForm.to_account_id !== null &&
    transferForm.amount !== '' &&
    Number(transferForm.amount) > 0 &&
    transferForm.date !== '' &&
    transferForm.from_account_id !== transferForm.to_account_id
  );
});

// 获取最大可转账金额
const getMaxTransferAmount = computed(() => {
  if (!transferForm.from_account_id) return 0;
  
  const account = accounts.value.find(a => a.id === transferForm.from_account_id);
  return account ? account.amount : 0;
});

// 格式化数字
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// 格式化日期
const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

// 获取账户类型文本
const getAccountTypeText = (type: string): string => {
  const types: Record<string, string> = {
    'cash': '现金',
    'card': '银行卡',
    'credit': '信用卡',
    'virtual': '虚拟账户',
    'other': '其他'
  };
  
  return types[type] || type;
};

// 根据账户类型获取颜色
const getColorByType = (type: string): string => {
  const colors: Record<string, string> = {
    'cash': '#48bb78',    // 绿色
    'card': '#4299e1',    // 蓝色
    'credit': '#f56565',  // 红色
    'virtual': '#9f7aea', // 紫色
    'other': '#a0aec0'    // 灰色
  };
  
  return colors[type] || '#4299e1';
};

// 获取文本颜色
const getTextColorByType = (type: string): string => {
  return {
    'cash': 'text-green-600',
    'card': 'text-blue-600',
    'credit': 'text-red-600',
    'virtual': 'text-purple-600',
    'other': 'text-gray-600'
  }[type] || 'text-gray-800';
};

// 加载账户列表
const loadAccounts = async () => {
  loading.value = true;
  
  try {
    const response = await axios.get('/api/balance');
    accounts.value = response.data;
  } catch (error) {
    console.error('加载账户列表失败:', error);
  } finally {
    loading.value = false;
  }
};

// 编辑账户
const editAccount = (account: Account) => {
  currentAccount.value = account;
  editMode.value = true;
  
  // 填充表单
  accountForm.name = account.name;
  accountForm.type = account.type;
  accountForm.amount = account.amount;
  accountForm.description = account.description || '';
  accountForm.color = account.color || getColorByType(account.type);
  
  showAddAccountModal.value = true;
};

// 调整余额
const adjustBalance = (account: Account) => {
  currentAccount.value = account;
  adjustForm.amount = account.amount;
  showAdjustModal.value = true;
};

// 确认删除
const confirmDelete = (account: Account) => {
  currentAccount.value = account;
  showDeleteConfirm.value = true;
};

// 查看账户详情
const showAccountDetail = async (account: Account) => {
  currentAccount.value = account;
  showDetailModal.value = true;
  currentPage.value = 1;
  await loadAccountTransactions(account.id);
};

// 加载账户交易记录
const loadAccountTransactions = async (accountId: number) => {
  detailLoading.value = true;
  
  try {
    const response = await axios.get(`/api/balance/${accountId}/transactions`, {
      params: {
        page: currentPage.value,
        page_size: pageSize.value
      }
    });
    
    transactions.value = response.data.data || [];
    totalTransactions.value = response.data.total || 0;
  } catch (error) {
    console.error('加载交易记录失败:', error);
  } finally {
    detailLoading.value = false;
  }
};

// 切换交易记录页码
const changePage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  
  currentPage.value = page;
  if (currentAccount.value) {
    await loadAccountTransactions(currentAccount.value.id);
  }
};

// 提交账户表单
const handleSubmitAccount = async () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  try {
    const data = {
      name: accountForm.name,
      type: accountForm.type,
      amount: Number(accountForm.amount),
      description: accountForm.description,
      color: accountForm.color,
      date: new Date().toISOString().substr(0, 10),
      initial_balance: Number(accountForm.amount)
    };
    
    if (editMode.value && currentAccount.value) {
      // 更新账户
      await axios.put(`/api/balance/${currentAccount.value.id}`, data);
    } else {
      // 添加新账户
      await axios.post('/api/balance', data);
    }
    
    // 刷新账户列表
    await loadAccounts();
    
    // 重置表单和状态
    resetAccountForm();
    showAddAccountModal.value = false;
    editMode.value = false;
    currentAccount.value = null;
  } catch (error) {
    console.error('保存账户失败:', error);
    alert('保存账户失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理转账
const handleTransfer = async () => {
  if (isSubmitting.value || !isTransferValid.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.post('/api/balance/transfer', {
      from_account_id: transferForm.from_account_id,
      to_account_id: transferForm.to_account_id,
      amount: Number(transferForm.amount),
      date: transferForm.date,
      description: transferForm.description
    });
    
    // 刷新账户列表
    await loadAccounts();
    
    // 重置表单和状态
    resetTransferForm();
    showTransferModal.value = false;
  } catch (error) {
    console.error('转账失败:', error);
    alert('转账失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理调整余额
const handleAdjustBalance = async () => {
  if (isSubmitting.value || !currentAccount.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.post(`/api/balance/${currentAccount.value.id}/adjust-balance`, {
      amount: Number(adjustForm.amount),
      date: adjustForm.date,
      description: adjustForm.description
    });
    
    // 刷新账户列表
    await loadAccounts();
    
    // 重置表单和状态
    resetAdjustForm();
    showAdjustModal.value = false;
    currentAccount.value = null;
  } catch (error) {
    console.error('调整余额失败:', error);
    alert('调整余额失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 处理删除账户
const handleDeleteAccount = async () => {
  if (isSubmitting.value || !currentAccount.value) return;
  
  isSubmitting.value = true;
  
  try {
    await axios.delete(`/api/balance/${currentAccount.value.id}`);
    
    // 刷新账户列表
    await loadAccounts();
    
    // 重置状态
    showDeleteConfirm.value = false;
    currentAccount.value = null;
  } catch (error) {
    console.error('删除账户失败:', error);
    alert('删除账户失败: ' + (error as any).response?.data?.message || (error as Error).message);
  } finally {
    isSubmitting.value = false;
  }
};

// 重置账户表单
const resetAccountForm = () => {
  accountForm.name = '';
  accountForm.type = 'card';
  accountForm.amount = '';
  accountForm.description = '';
  accountForm.color = '#4299e1';
};

// 重置转账表单
const resetTransferForm = () => {
  transferForm.from_account_id = null;
  transferForm.to_account_id = null;
  transferForm.amount = '';
  transferForm.date = new Date().toISOString().substr(0, 10);
  transferForm.description = '';
};

// 重置调整余额表单
const resetAdjustForm = () => {
  adjustForm.amount = '';
  adjustForm.date = new Date().toISOString().substr(0, 10);
  adjustForm.description = '';
};

// 组件挂载时加载数据
onMounted(() => {
  loadAccounts();
});
</script>