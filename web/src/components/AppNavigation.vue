<template>
  <nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <h1 class="text-xl font-bold text-indigo-600">个人收支管理系统</h1>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <router-link 
              to="/" 
              class="nav-link"
              :class="{ 'active-link': isActive('/') }"
            >
              概览
            </router-link>
            <router-link 
              to="/accounts" 
              class="nav-link"
              :class="{ 'active-link': isActive('/accounts') }"
            >
              账户管理
            </router-link>
            <router-link 
              to="/transactions" 
              class="nav-link"
              :class="{ 'active-link': isActive('/transactions') }"
            >
              收支记录
            </router-link>
            <router-link 
              to="/statistics" 
              class="nav-link"
              :class="{ 'active-link': isActive('/statistics') }"
            >
              统计分析
            </router-link>
            <router-link 
              to="/budget" 
              class="nav-link"
              :class="{ 'active-link': isActive('/budget') }"
            >
              预算管理
            </router-link>
            <router-link 
              to="/data" 
              class="nav-link"
              :class="{ 'active-link': isActive('/data') }"
            >
              数据管理
            </router-link>
            <router-link 
              to="/settings" 
              class="nav-link"
              :class="{ 'active-link': isActive('/settings') }"
            >
              系统设置
            </router-link>
          </div>
        </div>
        
        <!-- 移动端菜单按钮 -->
        <div class="flex items-center sm:hidden">
          <button 
            @click="mobileMenuOpen = !mobileMenuOpen" 
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
          >
            <span class="sr-only">打开主菜单</span>
            <svg 
              :class="[mobileMenuOpen ? 'hidden' : 'block', 'h-6 w-6']" 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor" 
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg 
              :class="[mobileMenuOpen ? 'block' : 'hidden', 'h-6 w-6']" 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor" 
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- 移动端菜单 -->
    <div :class="[mobileMenuOpen ? 'block' : 'hidden', 'sm:hidden']">
      <div class="pt-2 pb-3 space-y-1">
        <router-link 
          to="/" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/') }"
        >
          概览
        </router-link>
        <router-link 
          to="/accounts" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/accounts') }"
        >
          账户管理
        </router-link>
        <router-link 
          to="/transactions" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/transactions') }"
        >
          收支记录
        </router-link>
        <router-link 
          to="/statistics" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/statistics') }"
        >
          统计分析
        </router-link>
        <router-link 
          to="/budget" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/budget') }"
        >
          预算管理
        </router-link>
        <router-link 
          to="/data" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/data') }"
        >
          数据管理
        </router-link>
        <router-link 
          to="/settings" 
          class="mobile-nav-link"
          :class="{ 'mobile-active-link': isActive('/settings') }"
        >
          系统设置
        </router-link>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const mobileMenuOpen = ref(false);

// 检查当前路由是否激活
const isActive = (path: string) => {
  if (path === '/') {
    return route.path === path;
  }
  return route.path.startsWith(path);
};

// 路由变化时关闭移动端菜单
watch(() => route.path, () => {
  mobileMenuOpen.value = false;
});

// 响应ESC键关闭移动端菜单
onMounted(() => {
  const handleEsc = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && mobileMenuOpen.value) {
      mobileMenuOpen.value = false;
    }
  };
  
  document.addEventListener('keydown', handleEsc);
  
  return () => {
    document.removeEventListener('keydown', handleEsc);
  };
});
</script>

<style scoped>
.nav-link {
  @apply inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300;
}

.active-link {
  @apply border-indigo-500 text-gray-900 !important;
}

.mobile-nav-link {
  @apply block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800;
}

.mobile-active-link {
  @apply bg-indigo-50 border-indigo-500 text-indigo-700 !important;
}
</style>