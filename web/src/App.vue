<script setup lang="ts">
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Wallet, TrendCharts, Document } from '@element-plus/icons-vue';
</script>

<template>
  <el-config-provider namespace="ep">
    <div class="app-container">
      <router-view v-slot="{ Component }">
        <keep-alive>
          <component :is="Component" />
        </keep-alive>
      </router-view>

      <!-- 底部导航栏 -->
      <div class="nav-bar">
        <router-link to="/" class="nav-item" active-class="active">
          <el-icon><Wallet /></el-icon>
          <span>首页</span>
        </router-link>

        <router-link to="/transaction/list" class="nav-item" active-class="active">
          <el-icon><TrendCharts /></el-icon>
          <span>交易</span>
        </router-link>

        <router-link to="/history" class="nav-item" active-class="active">
          <el-icon><Document /></el-icon>
          <span>历史</span>
        </router-link>
      </div>
    </div>
  </el-config-provider>
</template>

<style>
/* 全局样式 */
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  background-color: #f5f7fa;
}

#app {
  height: 100%;
}

.app-container {
  min-height: 100vh;
  padding-bottom: 70px;
  box-sizing: border-box;
  position: relative;
}

/* 底部导航栏 */
.nav-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 60px;
  background: #fff;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
  z-index: 1000;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: #909399;
  font-size: 12px;
  flex: 1;
  height: 100%;
}

.nav-item .el-icon {
  font-size: 22px;
  margin-bottom: 4px;
}

.nav-item.active {
  color: #409eff;
}

/* 响应式样式 */
@media (min-width: 768px) {
  .app-container {
    padding-bottom: 0;
  }

  .nav-bar {
    position: fixed;
    top: 0;
    bottom: auto;
    height: 60px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  }

  .app-container {
    padding-top: 70px;
  }

  .nav-item {
    flex-direction: row;
    font-size: 14px;
  }

  .nav-item .el-icon {
    margin-bottom: 0;
    margin-right: 8px;
  }
}
</style>
