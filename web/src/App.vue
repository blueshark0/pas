<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppNavigation from './components/AppNavigation.vue'
</script>

<template>
  <div class="app-container">
    <AppNavigation />
    <main class="py-4">
      <RouterView />
    </main>
    <footer class="py-4 border-t mt-8 text-center text-gray-500 text-sm">
      <p>个人收支管理系统 © {{ new Date().getFullYear() }}</p>
    </footer>
  </div>
</template>

<style>
/* 全局样式 */
body {
  background-color: #f9fafb;
  color: #111827;
  min-height: 100vh;
}

.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  flex: 1;
}
</style>
