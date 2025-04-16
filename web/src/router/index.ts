import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: {
        title: '首页',
        keepAlive: true
      }
    },
    {
      path: '/transaction/list',
      name: 'transactionList',
      component: () => import('../views/TransactionListView.vue'),
      meta: {
        title: '交易列表',
        keepAlive: true
      }
    },
    {
      path: '/transaction/add',
      name: 'transactionAdd',
      component: () => import('../views/TransactionEditView.vue'),
      meta: {
        title: '添加交易'
      }
    },
    {
      path: '/transaction/edit/:id',
      name: 'transactionEdit',
      component: () => import('../views/TransactionEditView.vue'),
      meta: {
        title: '编辑交易'
      }
    },
    {
      path: '/history',
      name: 'history',
      component: () => import('../views/HistoryView.vue'),
      meta: {
        title: '余额历史',
        keepAlive: true
      }
    },
  ],
})

// 设置页面标题
router.beforeEach((to, from, next) => {
  if (to.meta.title) {
    document.title = `PAS - ${to.meta.title}`
  }
  next()
})

export default router
