import { beforeEach, describe, expect, it, vi } from 'vitest'

const authSessionMock = vi.hoisted(() => ({
  hasPermission: vi.fn(),
  initializeAuthSession: vi.fn(),
  isAuthenticated: { value: false },
}))

vi.mock('../stores/authSession', () => authSessionMock)
vi.mock('../views/AclView.vue', () => ({ default: { template: '<div>ACL</div>' } }))
vi.mock('../views/AttendancesView.vue', () => ({ default: { template: '<div>Attendances</div>' } }))
vi.mock('../views/LoginView.vue', () => ({ default: { template: '<div>Login</div>' } }))
vi.mock('../views/OperationalQueueView.vue', () => ({ default: { template: '<div>Queue</div>' } }))
vi.mock('../views/QueuesView.vue', () => ({ default: { template: '<div>Queues</div>' } }))

const loadRouter = async () => {
  vi.resetModules()
  const module = await import('./index.js')
  return module.default
}

describe('router guards', () => {
  beforeEach(() => {
    authSessionMock.initializeAuthSession.mockReset()
    authSessionMock.initializeAuthSession.mockResolvedValue(undefined)
    authSessionMock.hasPermission.mockReset()
    authSessionMock.hasPermission.mockReturnValue(true)
    authSessionMock.isAuthenticated.value = false
    window.history.replaceState({}, '', '/')
  })

  it('redireciona rota protegida para login com query de retorno', async () => {
    const router = await loadRouter()

    await router.push('/atendimentos')

    expect(authSessionMock.initializeAuthSession).toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/atendimentos')
  })

  it('redireciona visitante autenticado para a fila operacional ao abrir login', async () => {
    authSessionMock.isAuthenticated.value = true

    const router = await loadRouter()

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('operational-queue')
  })

  it('redireciona para a fila operacional quando falta permissão na rota', async () => {
    authSessionMock.isAuthenticated.value = true
    authSessionMock.hasPermission.mockImplementation((permission) => permission === 'attendances.view')

    const router = await loadRouter()

    await router.push('/acl')

    expect(router.currentRoute.value.name).toBe('operational-queue')
  })
})
