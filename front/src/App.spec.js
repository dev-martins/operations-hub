import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App.vue'

const themeMock = vi.hoisted(() => ({
  current: vi.fn(),
  init: vi.fn(),
  toggle: vi.fn(),
}))

const attendanceServiceMocks = vi.hoisted(() => ({
  fetchApiStatus: vi.fn(),
}))

const sessionBoundStateMock = vi.hoisted(() => ({
  resetSessionBoundState: vi.fn(),
}))

const authSessionMock = vi.hoisted(() => ({
  rawState: {
    user: null,
    tenant: null,
  },
  stateRef: null,
  clearAuthSession: vi.fn(),
  hasPermission: vi.fn(),
  initializeAuthSession: vi.fn(),
  logout: vi.fn(),
}))

const sidebarMock = vi.hoisted(() => {
  const state = {
    instances: [],
  }

  return {
    state,
    ctor: vi.fn().mockImplementation(() => {
      const instance = {}
      state.instances.push(instance)
      return instance
    }),
  }
})

vi.mock('./services/attendanceService', () => attendanceServiceMocks)
vi.mock('./stores/authSession', async () => {
  const { computed, reactive } = await import('vue')
  const authState = reactive(authSessionMock.rawState)
  authSessionMock.stateRef = authState

  return {
    authState,
    clearAuthSession: authSessionMock.clearAuthSession,
    hasPermission: authSessionMock.hasPermission,
    initializeAuthSession: authSessionMock.initializeAuthSession,
    isAuthenticated: computed(() => Boolean(authState.user)),
    logout: authSessionMock.logout,
  }
})
vi.mock('./adminator/scripts/utils/theme', () => ({ default: themeMock }))
vi.mock('./adminator/scripts/components/Sidebar', () => ({
  default: sidebarMock.ctor,
}))
vi.mock('./stores/sessionBoundState', () => sessionBoundStateMock)

const QueueView = { template: '<div>Fila carregada</div>' }
const LoginView = { template: '<div>Login carregado</div>' }
const AclView = { template: '<div>ACL carregada</div>' }
const QueuesView = { template: '<div>Filas carregadas</div>' }

const makeRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: {
          layout: 'auth',
        },
      },
      {
        path: '/operacional/fila',
        name: 'operational-queue',
        component: QueueView,
      },
      {
        path: '/atendimentos',
        name: 'attendances',
        component: QueueView,
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
}

const mountAppAt = async (path) => {
  const router = makeRouter()
  await router.push(path)
  await router.isReady()

  const wrapper = mount(App, {
    global: {
      plugins: [router],
    },
  })

  await flushPromises()

  return { router, wrapper }
}

describe('App', () => {
  beforeEach(() => {
    sidebarMock.state.instances.length = 0
    document.body.className = ''
    authSessionMock.stateRef.user = {
      name: 'Alice Admin',
      role_context: { label: 'Administrador' },
    }
    authSessionMock.stateRef.tenant = { name: 'Tenant Demo' }

    authSessionMock.clearAuthSession.mockReset()
    authSessionMock.logout.mockReset()
    authSessionMock.logout.mockResolvedValue(undefined)
    authSessionMock.initializeAuthSession.mockReset()
    authSessionMock.initializeAuthSession.mockResolvedValue(undefined)
    authSessionMock.hasPermission.mockReset()
    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['attendances.view', 'queues.view'].includes(permission)
    })

    attendanceServiceMocks.fetchApiStatus.mockReset()
    attendanceServiceMocks.fetchApiStatus.mockResolvedValue({ status: 'ok' })
    sessionBoundStateMock.resetSessionBoundState.mockReset()

    themeMock.current.mockReset()
    themeMock.current.mockReturnValue('light')
    themeMock.init.mockReset()
    themeMock.toggle.mockReset()
  })

  it('renderiza apenas navegação permitida e carrega status da API', async () => {
    const { wrapper } = await mountAppAt('/operacional/fila')

    expect(authSessionMock.initializeAuthSession).toHaveBeenCalled()
    expect(attendanceServiceMocks.fetchApiStatus).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Fila operacional')
    expect(wrapper.text()).toContain('Atendimentos')
    expect(wrapper.text()).toContain('Filas')
    expect(wrapper.text()).not.toContain('ACL')
    expect(wrapper.text()).toContain('Tenant Demo')
    expect(wrapper.text()).toContain('Administrador')
    expect(wrapper.text()).toContain('API ok')
    expect(sidebarMock.state.instances).toHaveLength(1)
  })

  it('usa layout de autenticação sem sidebar quando a rota marca layout auth', async () => {
    authSessionMock.stateRef.user = null
    authSessionMock.stateRef.tenant = null

    const { wrapper } = await mountAppAt('/login')

    expect(wrapper.text()).toContain('Login carregado')
    expect(wrapper.find('.sidebar').exists()).toBe(false)
    expect(attendanceServiceMocks.fetchApiStatus).not.toHaveBeenCalled()
  })

  it('redireciona para login ao receber evento de não autorizado', async () => {
    const { router } = await mountAppAt('/operacional/fila')

    window.dispatchEvent(new CustomEvent('app:unauthorized'))
    await flushPromises()

    expect(authSessionMock.clearAuthSession).toHaveBeenCalled()
    expect(sessionBoundStateMock.resetSessionBoundState).toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('faz logout e navega para login ao clicar em sair', async () => {
    const { router, wrapper } = await mountAppAt('/operacional/fila')

    await wrapper.get('.logout-button').trigger('click')
    await flushPromises()

    expect(authSessionMock.logout).toHaveBeenCalled()
    expect(sessionBoundStateMock.resetSessionBoundState).toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('reflete dados reidratados quando a sessão é recuperada no mount da aplicação', async () => {
    authSessionMock.stateRef.user = null
    authSessionMock.stateRef.tenant = null
    authSessionMock.initializeAuthSession.mockImplementation(async () => {
      authSessionMock.stateRef.user = {
        name: 'Sofia Supervisor',
        role_context: { label: 'Supervisora' },
      }
      authSessionMock.stateRef.tenant = {
        name: 'Tenant Reidratado',
      }
    })

    const { wrapper } = await mountAppAt('/operacional/fila')

    expect(wrapper.text()).toContain('Sofia Supervisor')
    expect(wrapper.text()).toContain('Supervisora')
    expect(wrapper.text()).toContain('Tenant Reidratado')
  })
})
