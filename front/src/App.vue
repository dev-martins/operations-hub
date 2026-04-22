<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import Sidebar from './adminator/scripts/components/Sidebar'
import Theme from './adminator/scripts/utils/theme'
import logoUrl from './adminator/static/images/logo.svg'
import { authState, clearAuthSession, hasPermission, initializeAuthSession, isAuthenticated, logout } from './stores/authSession'
import { fetchApiStatus } from './services/attendanceService'

const apiStatus = ref('carregando')
const currentTheme = ref('light')
const route = useRoute()
const router = useRouter()
let sidebar = null

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

const syncTheme = () => {
  currentTheme.value = Theme.current()
}

const isAuthLayout = computed(() => route.meta.layout === 'auth')
const currentUserName = computed(() => authState.user?.name ?? 'Operador')
const currentTenantName = computed(() => authState.tenant?.name ?? 'Tenant nao identificado')
const currentRoleLabel = computed(() => authState.user?.role_context?.label ?? 'Sem papel')
const visibleNavigationItems = computed(() => {
  return navigationItems.filter((item) => !item.permission || hasPermission(item.permission))
})

const toggleTheme = () => {
  Theme.toggle()
  syncTheme()
}

const handleLogout = async () => {
  await logout()
  await router.push({ name: 'login' })
}

const loadApiStatus = async () => {
  try {
    const data = await fetchApiStatus()
    apiStatus.value = data.status
  } catch (error) {
    apiStatus.value = 'indisponível'
  }
}

const handleUnauthorized = async () => {
  clearAuthSession()

  if (route.name !== 'login') {
    await router.push({ name: 'login' })
  }
}

onMounted(async () => {
  document.body.classList.add('app')
  Theme.init()
  syncTheme()
  await initializeAuthSession()
  await loadApiStatus()

  await nextTick()
  sidebar = new Sidebar()
  window.addEventListener('adminator:themeChanged', syncTheme)
  window.addEventListener('app:unauthorized', handleUnauthorized)
})

onUnmounted(() => {
  document.body.classList.remove('app')
  window.removeEventListener('adminator:themeChanged', syncTheme)
  window.removeEventListener('app:unauthorized', handleUnauthorized)
  sidebar = null
})
</script>

<template>
  <div v-if="isAuthLayout" class="app-shell">
    <RouterView />
  </div>

  <div v-else class="app-shell">
    <div class="sidebar">
      <div class="sidebar-inner">
        <div class="sidebar-logo">
          <div class="peers ai-c fxw-nw">
            <div class="peer peer-greed">
              <RouterLink class="sidebar-link td-n" to="/operacional/fila">
                <div class="peers ai-c fxw-nw">
                  <div class="peer">
                    <div class="logo">
                      <img :src="logoUrl" alt="Operations Hub" />
                    </div>
                  </div>
                  <div class="peer peer-greed">
                    <h5 class="lh-1 mB-0 logo-text">Operations Hub</h5>
                    <small class="brand-subtitle">Adminator + Vue</small>
                  </div>
                </div>
              </RouterLink>
            </div>

            <div class="peer">
              <div class="mobile-toggle sidebar-toggle">
                <a href="" class="td-n">
                  <i class="ti-arrow-circle-left"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

        <ul class="sidebar-menu scrollable pos-r">
          <li
            v-for="item in visibleNavigationItems"
            :key="item.to"
            class="nav-item mT-30"
          >
            <RouterLink
              :to="item.to"
              class="sidebar-link"
              active-class="router-link-active"
            >
              <span class="icon-holder">
                <i :class="item.iconClass"></i>
              </span>
              <span class="title">{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </div>
    </div>

    <div class="page-container">
      <div class="header navbar">
        <div class="header-container">
          <ul class="nav-left">
            <li class="topbar-menu-item">
              <a id="sidebar-toggle" class="sidebar-toggle" href="javascript:void(0);">
                <i class="ti-menu"></i>
              </a>
            </li>
          </ul>

          <ul class="nav-right">
            <li v-if="isAuthenticated">
              <span class="tenant-chip">
                <i class="ti-server"></i>
                {{ currentTenantName }}
              </span>
            </li>
            <li v-if="isAuthenticated">
              <span class="user-chip role-chip">
                <i class="ti-id-badge"></i>
                {{ currentRoleLabel }}
              </span>
            </li>
            <li v-if="isAuthenticated">
              <span class="user-chip">
                <i class="ti-user"></i>
                {{ currentUserName }}
              </span>
            </li>
            <li>
              <span class="status-chip">
                <i class="ti-pulse"></i>
                API {{ apiStatus }}
              </span>
            </li>
            <li class="theme-toggle d-flex ai-c">
              <div class="form-check form-switch d-flex ai-c mB-0">
                <label class="form-check-label me-2 text-nowrap c-grey-700" for="theme-toggle">
                  CLARO
                </label>
                <input
                  id="theme-toggle"
                  class="form-check-input"
                  type="checkbox"
                  role="switch"
                  :checked="currentTheme === 'dark'"
                  aria-label="Alternar entre tema claro e escuro"
                  @change="toggleTheme"
                >
                <label class="form-check-label ms-2 text-nowrap c-grey-700" for="theme-toggle">
                  ESCURO
                </label>
              </div>
            </li>
            <li v-if="isAuthenticated">
              <button type="button" class="logout-button" @click="handleLogout">
                <i class="ti-power-off"></i>
                <span>Sair</span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <main class="main-content bgc-grey-100">
        <div id="mainContent">
          <div class="container-fluid page-content">
            <RouterView />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
