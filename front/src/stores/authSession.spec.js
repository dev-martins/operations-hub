import { beforeEach, describe, expect, it, vi } from 'vitest'

const authServiceMocks = vi.hoisted(() => ({
  fetchCurrentUser: vi.fn(),
  loginRequest: vi.fn(),
  logoutRequest: vi.fn(),
}))

const storageState = vi.hoisted(() => ({
  accessToken: null,
  user: null,
  persistedToken: null,
  persistedUser: null,
}))

const tokenStorageMocks = vi.hoisted(() => ({
  readAccessToken: vi.fn(() => storageState.accessToken),
  readStoredAuthUser: vi.fn(() => storageState.user),
  writeAccessToken: vi.fn((token) => {
    storageState.persistedToken = token
  }),
  writeStoredAuthUser: vi.fn((user) => {
    storageState.persistedUser = user
  }),
}))

vi.mock('../services/authService', () => authServiceMocks)
vi.mock('../services/authTokenStorage', () => tokenStorageMocks)

const loadModule = async () => {
  vi.resetModules()
  return import('./authSession.js')
}

describe('authSession', () => {
  beforeEach(() => {
    storageState.accessToken = null
    storageState.user = null
    storageState.persistedToken = null
    storageState.persistedUser = null

    authServiceMocks.fetchCurrentUser.mockReset()
    authServiceMocks.loginRequest.mockReset()
    authServiceMocks.logoutRequest.mockReset()

    tokenStorageMocks.writeAccessToken.mockClear()
    tokenStorageMocks.writeStoredAuthUser.mockClear()
  })

  it('hidrata sessão no bootstrap quando há token salvo e auth/me responde com sucesso', async () => {
    storageState.accessToken = 'token-salvo'
    authServiceMocks.fetchCurrentUser.mockResolvedValue({
      id: 9,
      name: 'Sofia',
      tenant: { id: 1, name: 'Tenant Demo' },
      permissions: [{ key: 'attendances.view' }],
    })

    const session = await loadModule()

    await session.initializeAuthSession()

    expect(authServiceMocks.fetchCurrentUser).toHaveBeenCalled()
    expect(session.authState.user?.name).toBe('Sofia')
    expect(session.authState.tenant?.name).toBe('Tenant Demo')
    expect(session.isAuthenticated.value).toBe(true)
    expect(storageState.persistedUser?.name).toBe('Sofia')
  })

  it('limpa sessão quando auth/me retorna 401 durante o bootstrap', async () => {
    storageState.accessToken = 'token-expirado'
    authServiceMocks.fetchCurrentUser.mockRejectedValue({
      response: {
        status: 401,
      },
    })

    const session = await loadModule()

    await session.initializeAuthSession()

    expect(session.authState.user).toBeNull()
    expect(session.authState.tenant).toBeNull()
    expect(session.authState.initialized).toBe(true)
    expect(storageState.persistedToken).toBeNull()
    expect(storageState.persistedUser).toBeNull()
  })

  it('faz login persistindo token e usuário autenticado', async () => {
    authServiceMocks.loginRequest.mockResolvedValue({
      access_token: 'novo-token',
      user: {
        id: 4,
        name: 'Otavio',
        tenant: { id: 2, name: 'Operacao Norte' },
        permissions: [{ key: 'attendances.view' }],
      },
    })

    const session = await loadModule()

    await session.login({
      email: 'otavio@example.com',
      password: 'password',
    })

    expect(storageState.persistedToken).toBe('novo-token')
    expect(storageState.persistedUser?.name).toBe('Otavio')
    expect(session.authState.tenant?.name).toBe('Operacao Norte')
  })

  it('faz logout chamando a API e limpando o estado local', async () => {
    storageState.accessToken = 'token-ativo'
    storageState.user = {
      id: 1,
      name: 'Alice',
      tenant: { id: 1, name: 'Tenant Demo' },
    }
    authServiceMocks.logoutRequest.mockResolvedValue({})

    const session = await loadModule()

    await session.logout()

    expect(authServiceMocks.logoutRequest).toHaveBeenCalled()
    expect(session.authState.user).toBeNull()
    expect(session.authState.tenant).toBeNull()
    expect(storageState.persistedToken).toBeNull()
    expect(storageState.persistedUser).toBeNull()
  })

  it('reaproveita permissões carregadas para checagem de acesso', async () => {
    storageState.accessToken = 'token-salvo'
    storageState.user = {
      id: 1,
      name: 'Alice',
      tenant: { id: 1, name: 'Tenant Demo' },
      permissions: [{ key: 'acl.view' }, { key: 'queues.view' }],
    }

    const session = await loadModule()

    expect(session.hasPermission('acl.view')).toBe(true)
    expect(session.hasPermission('attendances.assign')).toBe(false)
  })
})
