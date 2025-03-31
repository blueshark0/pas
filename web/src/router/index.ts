import { createRouter, createWebHistory } from 'vue-router'
import Overview from '../views/Overview.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'overview',
      component: Overview,
    },
  ],
})

export default router
