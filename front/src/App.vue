<script setup>
import axios from 'axios'
import { nextTick, onMounted, onUnmounted, ref } from 'vue'
import Sidebar from './adminator/scripts/components/Sidebar'
import Theme from './adminator/scripts/utils/theme'
import logoUrl from './adminator/static/images/logo.svg'

const apiStatus = ref('carregando')
const apiMessage = ref('Validando comunicação entre frontend e backend.')
const apiError = ref('')
const currentTheme = ref('light')
let sidebar = null

const metrics = [
  {
    title: 'Atendimentos abertos',
    value: '128',
    note: '22 com SLA menor que 30 min',
    iconKey: 'tickets',
    tone: 'is-blue',
  },
  {
    title: 'Filas monitoradas',
    value: '7',
    note: 'Suporte, financeiro, integrações e operações',
    iconKey: 'queues',
    tone: 'is-orange',
  },
  {
    title: 'Eventos processados',
    value: '1.842',
    note: 'Mensageria e automações das últimas 24h',
    iconKey: 'events',
    tone: 'is-green',
  },
  {
    title: 'Perfis com ACL',
    value: '5',
    note: 'Papéis separados por operação e governança',
    iconKey: 'acl',
    tone: 'is-purple',
  },
]

const queues = [
  { name: 'Suporte N1', waiting: 18, sla: '12 min', tenant: 'Operação Brasil' },
  { name: 'Financeiro', waiting: 7, sla: '28 min', tenant: 'Backoffice' },
  { name: 'Integrações', waiting: 4, sla: '42 min', tenant: 'Plataforma' },
  { name: 'Críticos', waiting: 2, sla: '6 min', tenant: 'Operação Brasil' },
]

const tickets = [
  { code: '#AT-2031', subject: 'Webhook de cobrança sem retorno', queue: 'Integrações', owner: 'Fernanda', priority: 'Crítica', tone: 'is-critical' },
  { code: '#AT-2028', subject: 'Fila de aprovação com atraso', queue: 'Financeiro', owner: 'Carlos', priority: 'Alta', tone: 'is-warning' },
  { code: '#AT-2017', subject: 'Atualização de SLA por contrato', queue: 'Suporte N1', owner: 'Aline', priority: 'Normal', tone: 'is-ok' },
  { code: '#AT-2009', subject: 'Reprocessamento de evento legado', queue: 'Críticos', owner: 'Renan', priority: 'Crítica', tone: 'is-critical' },
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

onMounted(async () => {
  document.body.classList.add('app')
  Theme.init()
  syncTheme()

  try {
    const { data } = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/v1/status`)
    apiStatus.value = data.status
    apiMessage.value = data.objetivo
  } catch (error) {
    apiStatus.value = 'indisponível'
    apiError.value = 'A API ainda não respondeu. Verifique se o container backend está ativo.'
  }

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
              <a class="sidebar-link td-n" href="/">
                <div class="peers ai-c fxw-nw">
                  <div class="peer">
                    <div class="logo">
                      <img :src="logoUrl" alt="Central de Atendimento Operacional" />
                    </div>
                  </div>
                  <div class="peer peer-greed">
                    <h5 class="lh-1 mB-0 logo-text">Operations Hub</h5>
                    <small class="brand-subtitle">Adminator + Vue</small>
                  </div>
                </div>
              </a>
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
          <li class="nav-item mT-30 actived">
            <a class="sidebar-link" href="/">
              <span class="icon-holder">
                <i class="c-blue-500 ti-home"></i>
              </span>
              <span class="title">Visão geral</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="sidebar-link" href="javascript:void(0);">
              <span class="icon-holder">
                <i class="c-brown-500 ti-agenda"></i>
              </span>
              <span class="title">Atendimentos</span>
            </a>
          </li>

          <li class="nav-item dropdown">
            <a class="dropdown-toggle" href="javascript:void(0);">
              <span class="icon-holder">
                <i class="c-orange-500 ti-layout-list-thumb"></i>
              </span>
              <span class="title">Filas operacionais</span>
              <span class="arrow">
                <i class="ti-angle-right"></i>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="sidebar-link" href="javascript:void(0);">Monitoramento</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Regras de SLA</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Escalonamentos</a></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
            <a class="dropdown-toggle" href="javascript:void(0);">
              <span class="icon-holder">
                <i class="c-green-500 ti-package"></i>
              </span>
              <span class="title">Catálogos</span>
              <span class="arrow">
                <i class="ti-angle-right"></i>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="sidebar-link" href="javascript:void(0);">Canais</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Equipes</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Motivos</a></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
            <a class="dropdown-toggle" href="javascript:void(0);">
              <span class="icon-holder">
                <i class="c-purple-500 ti-shield"></i>
              </span>
              <span class="title">Segurança e ACL</span>
              <span class="arrow">
                <i class="ti-angle-right"></i>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="sidebar-link" href="javascript:void(0);">Usuários</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Roles</a></li>
              <li><a class="sidebar-link" href="javascript:void(0);">Permissões</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="sidebar-link" href="javascript:void(0);">
              <span class="icon-holder">
                <i class="c-red-500 ti-layers-alt"></i>
              </span>
              <span class="title">Tenant e contexto</span>
            </a>
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
            <li class="topbar-title-item d-none d-md-flex">
              <h4 class="mB-0">Central de Atendimento Operacional</h4>
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
                  :aria-checked="currentTheme === 'dark' ? 'true' : 'false'"
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
          <div class="container-fluid">
            <div class="row gap-20">
              <div class="col-12">
                <div class="layers bd bgc-white p-30 hero-panel">
                  <div class="layer w-100">
                    <div class="layers quick-status p-20 bgc-white bd">
                      <div class="layer w-100">
                        <h6 class="mB-10">Status da API</h6>
                        <h2 class="mB-8 text-success text-capitalize">{{ apiStatus }}</h2>
                        <p class="mB-0 c-grey-700">{{ apiMessage }}</p>
                        <p v-if="apiError" class="mT-10 mB-0 c-red-500">{{ apiError }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-for="metric in metrics"
                :key="metric.title"
                class="col-md-6 col-xl-3"
              >
                <div class="layers bd bgc-white p-20 metric-card h-100">
                  <div class="layer w-100">
                    <span class="icon-holder metric-icon-anchor" :class="metric.tone">
                      <svg
                        v-if="metric.iconKey === 'tickets'"
                        class="metric-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                      >
                        <path d="M7 5.5h8l3 3V19a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-12.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M15 5.5V9h3" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M9 12h6M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                      </svg>
                      <svg
                        v-else-if="metric.iconKey === 'queues'"
                        class="metric-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                      >
                        <circle cx="7" cy="8" r="1.5" fill="currentColor"/>
                        <circle cx="7" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="7" cy="16" r="1.5" fill="currentColor"/>
                        <path d="M11 8h7M11 12h7M11 16h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                      </svg>
                      <svg
                        v-else-if="metric.iconKey === 'events'"
                        class="metric-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                      >
                        <path d="M6 18V11M11 18V7M16 18V13M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="m14 7 2-2 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <svg
                        v-else
                        class="metric-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                      >
                        <path d="M12 4 18 6.5V11c0 4.2-2.6 7.2-6 9-3.4-1.8-6-4.8-6-9V6.5L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 8v10M8.5 10.5c.8 1.1 2 1.7 3.5 1.7s2.7-.6 3.5-1.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                      </svg>
                    </span>
                    <h6>{{ metric.title }}</h6>
                    <h2>{{ metric.value }}</h2>
                    <small class="c-grey-600">{{ metric.note }}</small>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="bd bgc-white">
                  <div class="layers">
                    <div class="layer w-100 pX-20 pT-20">
                      <h5 class="mB-0">Tickets prioritários</h5>
                    </div>

                    <div class="layer w-100">
                      <div class="table-responsive">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>Código</th>
                              <th>Assunto</th>
                              <th>Fila</th>
                              <th>Responsável</th>
                              <th>Prioridade</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="ticket in tickets" :key="ticket.code">
                              <td class="fw-600">{{ ticket.code }}</td>
                              <td>{{ ticket.subject }}</td>
                              <td>{{ ticket.queue }}</td>
                              <td>{{ ticket.owner }}</td>
                              <td>
                                <span class="ticket-tag" :class="ticket.tone">
                                  {{ ticket.priority }}
                                </span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="bd bgc-white">
                  <div class="layers">
                    <div class="layer w-100 pX-20 pT-20">
                      <h5 class="mB-0">Filas monitoradas</h5>
                    </div>

                    <div class="layer w-100 p-20">
                      <div
                        v-for="queue in queues"
                        :key="queue.name"
                        class="layers quick-status queue-card p-15 bd mB-15"
                      >
                        <div class="layer w-100">
                          <div class="peers ai-c jc-sb">
                            <div class="peer">
                              <h6 class="mB-5">{{ queue.name }}</h6>
                              <small class="c-grey-600">{{ queue.tenant }}</small>
                            </div>
                            <div class="peer ta-r">
                              <strong class="d-b">{{ queue.waiting }} na fila</strong>
                              <small class="c-grey-600">SLA médio {{ queue.sla }}</small>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="layers p-15 next-phase-card">
                        <div class="layer w-100">
                          <h6 class="mB-10">Direcionadores da próxima fase</h6>
                          <ul class="mB-0 pL-20">
                            <li>Definir se tenant é por cliente, unidade ou contrato.</li>
                            <li>Modelar ACL com roles e permissões granulares.</li>
                            <li>Popular seeders com usuários, filas, tickets e tenants.</li>
                            <li>Mapear páginas do Adminator a serem convertidas para Vue.</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
