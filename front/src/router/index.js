import { createRouter, createWebHistory } from 'vue-router'
import AclView from '../views/AclView.vue'
import AttendancesView from '../views/AttendancesView.vue'
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
      path: '/operacional/fila',
      name: 'operational-queue',
      component: OperationalQueueView,
    },
    {
      path: '/atendimentos',
      name: 'attendances',
      component: AttendancesView,
    },
    {
      path: '/filas',
      name: 'queues',
      component: QueuesView,
    },
    {
      path: '/acl',
      name: 'acl',
      component: AclView,
    },
  ],
})

export default router
