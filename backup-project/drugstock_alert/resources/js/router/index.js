import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

import LoginPage from '@/pages/auth/LoginPage.vue'
import RegisterPage from '@/pages/auth/RegisterPage.vue'
import DashboardPage from '@/pages/drugstock/DashboardPage.vue'
import WarehousesPage from '@/pages/drugstock/WarehousesPage.vue'
import WarehouseDetailPage from '@/pages/drugstock/WarehouseDetailPage.vue'
import DrugsPage from '@/pages/drugstock/DrugsPage.vue'
import LocationsPage from '@/pages/drugstock/LocationsPage.vue'
import AlertsPage from '@/pages/drugstock/AlertsPage.vue'
import RolesPage from '@/pages/drugstock/RolesPage.vue'
import ReportsPage from '@/pages/drugstock/ReportsPage.vue'
import AboutPage from '@/pages/drugstock/AboutPage.vue'
import HelpPage from '@/pages/drugstock/HelpPage.vue'
import SettingsPage from '@/pages/drugstock/SettingsPage.vue'

const routes = [
  {
    path: '/login',
    component: AuthLayout,
    children: [{ path: '', component: LoginPage }],
  },
  {
    path: '/register',
    component: AuthLayout,
    children: [{ path: '', component: RegisterPage }],
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      { path: '', component: DashboardPage },
      { path: 'warehouses', component: WarehousesPage },
      { path: 'warehouses/:id', component: WarehouseDetailPage, props: true },
      { path: 'drugs', component: DrugsPage },
      { path: 'locations', component: LocationsPage },
      { path: 'alerts', component: AlertsPage },
      { path: 'roles', component: RolesPage },
      { path: 'reports', component: ReportsPage },
      { path: 'about', component: AboutPage },
      { path: 'help', component: HelpPage },
      { path: 'settings', component: SettingsPage },
    ],
    meta: { requiresAuth: true },
  },
]

function resolveHistoryBase() {
  if (typeof window !== 'undefined' && window.__APP_BASE_PATH__) {
    const basePath = String(window.__APP_BASE_PATH__).trim()
    if (basePath && basePath !== '/') {
      return basePath.endsWith('/') ? basePath : `${basePath}/`
    }
  }

  return import.meta.env.BASE_URL || '/'
}

const router = createRouter({
  history: createWebHistory(resolveHistoryBase()),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = window.localStorage.getItem('medstock_token')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)

  if (requiresAuth && !token) {
    next('/login')
  } else if (!requiresAuth && token && (to.path === '/login' || to.path === '/register')) {
    next('/')
  } else {
    next()
  }
})

export default router
