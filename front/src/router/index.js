import { createRouter, createWebHistory } from 'vue-router'
import { initializeAuthSession, isAuthenticated } from '../stores/authSession'
import AclView from '../views/AclView.vue'
import AttendancesView from '../views/AttendancesView.vue'
import LoginView from '../views/LoginView.vue'
import OperationalQueueView from '../views/OperationalQueueView.vue'
import QueuesView from '../views/QueuesView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/operacional/fila',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: {
        guestOnly: true,
        layout: 'auth',
      },
    },
    {
      path: '/operacional/fila',
      name: 'operational-queue',
      component: OperationalQueueView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/atendimentos',
      name: 'attendances',
      component: AttendancesView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/filas',
      name: 'queues',
      component: QueuesView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/acl',
      name: 'acl',
      component: AclView,
      meta: {
        requiresAuth: true,
      },
    },
  ],
})

router.beforeEach(async (to) => {
  await initializeAuthSession()

  if (to.meta.guestOnly && isAuthenticated.value) {
    return { name: 'operational-queue' }
  }

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  return true
})

export default router
