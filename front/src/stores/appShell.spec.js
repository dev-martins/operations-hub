import { beforeEach, describe, expect, it, vi } from 'vitest'
import { loadApiStatus, appShellState, currentRoleLabel, currentTenantName, currentUserName, resetAppShellState, visibleNavigationItems } from './appShell'
import { authState } from './authSession'

const attendanceServiceMocks = vi.hoisted(() => ({
  fetchApiStatus: vi.fn(),
}))

const authSessionMock = vi.hoisted(() => ({
  hasPermission: vi.fn(),
}))

vi.mock('../services/attendanceService', () => attendanceServiceMocks)
vi.mock('./authSession', async () => {
  const { reactive } = await import('vue')

  return {
    authState: reactive({
      user: null,
      tenant: null,
    }),
    hasPermission: authSessionMock.hasPermission,
  }
})

describe('appShell', () => {
  beforeEach(() => {
    resetAppShellState()
    authState.user = null
    authState.tenant = null
    authSessionMock.hasPermission.mockReset()
    attendanceServiceMocks.fetchApiStatus.mockReset()
  })

  it('deriva dados do usuário e filtra a navegação por permissão', () => {
    authState.user = {
      name: 'Sofia Supervisor',
      role_context: {
        label: 'Supervisora',
      },
    }
    authState.tenant = {
      name: 'Tenant Montreal',
    }

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['attendances.view', 'queues.view'].includes(permission)
    })

    expect(currentUserName.value).toBe('Sofia Supervisor')
    expect(currentRoleLabel.value).toBe('Supervisora')
    expect(currentTenantName.value).toBe('Tenant Montreal')
    expect(visibleNavigationItems.value.map((item) => item.label)).toEqual([
      'Fila operacional',
      'Atendimentos',
      'Filas',
    ])
  })

  it('carrega o status da api com sucesso', async () => {
    attendanceServiceMocks.fetchApiStatus.mockResolvedValue({ status: 'ok' })

    await loadApiStatus()

    expect(appShellState.apiStatus).toBe('ok')
  })

  it('marca api como indisponível quando a consulta falha', async () => {
    attendanceServiceMocks.fetchApiStatus.mockRejectedValue(new Error('boom'))

    await loadApiStatus()

    expect(appShellState.apiStatus).toBe('indisponível')
  })
})
