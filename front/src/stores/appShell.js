import { computed, reactive } from 'vue'
import { authState, hasPermission } from './authSession'
import { fetchApiStatus } from '../services/attendanceService'

const navigationItems = [
  {
    label: 'Fila operacional',
    to: '/operacional/fila',
    iconClass: 'c-blue-500 ti-agenda',
    permission: 'attendances.view',
  },
  {
    label: 'Atendimentos',
    to: '/atendimentos',
    iconClass: 'c-orange-500 ti-layout-list-thumb',
    permission: 'attendances.view',
  },
  {
    label: 'Filas',
    to: '/filas',
    iconClass: 'c-green-500 ti-package',
    permission: 'queues.view',
  },
  {
    label: 'ACL',
    to: '/acl',
    iconClass: 'c-purple-500 ti-shield',
    permission: 'acl.view',
  },
]

const state = reactive({
  apiStatus: 'carregando',
  loadingApiStatus: false,
})

export const appShellState = state

export const currentUserName = computed(() => authState.user?.name ?? 'Operador')
export const currentTenantName = computed(() => authState.tenant?.name ?? 'Tenant nao identificado')
export const currentRoleLabel = computed(() => authState.user?.role_context?.label ?? 'Sem papel')
export const visibleNavigationItems = computed(() => {
  return navigationItems.filter((item) => !item.permission || hasPermission(item.permission))
})

export const loadApiStatus = async () => {
  if (state.loadingApiStatus) {
    return state.apiStatus
  }

  state.loadingApiStatus = true

  try {
    const data = await fetchApiStatus()
    state.apiStatus = data.status
  } catch (error) {
    state.apiStatus = 'indisponível'
  } finally {
    state.loadingApiStatus = false
  }

  return state.apiStatus
}

export const resetAppShellState = () => {
  state.apiStatus = 'carregando'
  state.loadingApiStatus = false
}
