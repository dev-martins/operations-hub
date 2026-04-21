<script setup>
import { nextTick, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import Sidebar from './adminator/scripts/components/Sidebar'
import Theme from './adminator/scripts/utils/theme'
import logoUrl from './adminator/static/images/logo.svg'
import { fetchApiStatus } from './services/attendanceService'

const apiStatus = ref('carregando')
const currentTheme = ref('light')
let sidebar = null

const navigationItems = [
  {
    label: 'Fila operacional',
    to: '/operacional/fila',
    iconClass: 'c-blue-500 ti-agenda',
  },
  {
    label: 'Atendimentos',
    to: '/atendimentos',
    iconClass: 'c-orange-500 ti-layout-list-thumb',
  },
  {
    label: 'Filas',
    to: '/filas',
    iconClass: 'c-green-500 ti-package',
  },
  {
    label: 'ACL',
    to: '/acl',
    iconClass: 'c-purple-500 ti-shield',
  },
]

const syncTheme = () => {
  currentTheme.value = Theme.current()
}

const toggleTheme = () => {
  Theme.toggle()
  syncTheme()
}

const handleLogout = () => {
  window.dispatchEvent(new CustomEvent('app:logout'))
  window.alert('Fluxo de logout será conectado quando a autenticação estiver implementada.')
}

const loadApiStatus = async () => {
  try {
    const data = await fetchApiStatus()
    apiStatus.value = data.status
  } catch (error) {
    apiStatus.value = 'indisponível'
  }
}

onMounted(async () => {
  document.body.classList.add('app')
  Theme.init()
  syncTheme()
  await loadApiStatus()

  await nextTick()
  sidebar = new Sidebar()
  window.addEventListener('adminator:themeChanged', syncTheme)
})

onUnmounted(() => {
  document.body.classList.remove('app')
  window.removeEventListener('adminator:themeChanged', syncTheme)
  sidebar = null
})
</script>

<template>
  <div class="app-shell">
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
            v-for="item in navigationItems"
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
            <li>
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
