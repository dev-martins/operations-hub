import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  activeQueuesCount,
  cancelQueueEditing,
  ensureAclOverview,
  ensureQueuesOverview,
  governanceState,
  inactiveQueuesCount,
  loadAclOverview,
  loadQueuesOverview,
  queueSubmitLabel,
  resetAclState,
  resetGovernanceState,
  resetQueuesState,
  startQueueEditing,
  submitQueue,
  submitRoleUpdate,
  totalWaitingCount,
} from './governanceContext'

const authServiceMocks = vi.hoisted(() => ({
  fetchAclOverview: vi.fn(),
}))

const governanceServiceMocks = vi.hoisted(() => ({
  createQueue: vi.fn(),
  fetchQueuesOverview: vi.fn(),
  updateQueue: vi.fn(),
  updateUserRole: vi.fn(),
}))

vi.mock('../services/authService', () => authServiceMocks)
vi.mock('../services/governanceService', () => governanceServiceMocks)

describe('governanceContext', () => {
  beforeEach(() => {
    resetGovernanceState()
    vi.clearAllMocks()
  })

  it('carrega a visão de filas e calcula indicadores agregados', async () => {
    governanceServiceMocks.fetchQueuesOverview.mockResolvedValue([
      { id: 1, active: true, waiting_count: 3 },
      { id: 2, active: false, waiting_count: 0 },
    ])

    await loadQueuesOverview()

    expect(governanceState.queues).toHaveLength(2)
    expect(activeQueuesCount.value).toBe(1)
    expect(inactiveQueuesCount.value).toBe(1)
    expect(totalWaitingCount.value).toBe(3)
  })

  it('preserva filas já carregadas ao usar ensureQueuesOverview', async () => {
    governanceState.queues = [{ id: 1, active: true, waiting_count: 2 }]

    await ensureQueuesOverview()

    expect(governanceServiceMocks.fetchQueuesOverview).not.toHaveBeenCalled()
  })

  it('preenche formulário de edição e pode cancelá-lo', () => {
    startQueueEditing({
      id: 7,
      name: 'Backoffice',
      code: 'BACK',
      description: 'Fila administrativa',
      active: false,
    })

    expect(governanceState.editingQueueId).toBe(7)
    expect(queueSubmitLabel.value).toBe('Salvar fila')
    expect(governanceState.queueForm.name).toBe('Backoffice')

    cancelQueueEditing()

    expect(governanceState.editingQueueId).toBeNull()
    expect(queueSubmitLabel.value).toBe('Criar fila')
    expect(governanceState.queueForm.name).toBe('')
  })

  it('cria fila e recarrega a listagem', async () => {
    governanceServiceMocks.createQueue.mockResolvedValue({ id: 3 })
    governanceServiceMocks.fetchQueuesOverview.mockResolvedValue([])

    governanceState.queueForm.name = 'Backoffice'
    governanceState.queueForm.code = 'BACK'
    governanceState.queueForm.description = 'Fila administrativa'
    governanceState.queueForm.active = true

    await submitQueue()

    expect(governanceServiceMocks.createQueue).toHaveBeenCalledWith({
      name: 'Backoffice',
      code: 'BACK',
      description: 'Fila administrativa',
      active: true,
    })
    expect(governanceState.queueFeedback).toBe('Fila criada com sucesso.')
  })

  it('carrega acl e atualiza papel de usuário', async () => {
    authServiceMocks.fetchAclOverview.mockResolvedValue({
      roles: [],
      tenant_users: [],
      manageable_roles: [],
      role_summary: [],
      current_user: {
        name: 'Alice',
        permissions: [],
        role: {
          label: 'Admin',
          description: 'Administra',
        },
      },
    })
    governanceServiceMocks.updateUserRole.mockResolvedValue({})

    await loadAclOverview()
    await submitRoleUpdate(9, 'viewer')

    expect(governanceServiceMocks.updateUserRole).toHaveBeenCalledWith(9, { role: 'viewer' })
    expect(governanceState.aclActionFeedback).toBe('Papel atualizado com sucesso.')
  })

  it('preserva acl já carregada ao usar ensureAclOverview', async () => {
    governanceState.aclData = {
      current_user: { name: 'Alice' },
      roles: [],
      tenant_users: [],
      manageable_roles: [],
      role_summary: [],
    }

    await ensureAclOverview()

    expect(authServiceMocks.fetchAclOverview).not.toHaveBeenCalled()
  })

  it('limpa apenas o domínio de filas sem apagar acl já carregada', () => {
    governanceState.queues = [{ id: 1, active: true, waiting_count: 1 }]
    governanceState.queueFeedback = 'ok'
    governanceState.editingQueueId = 5
    governanceState.aclData = {
      current_user: { name: 'Alice' },
      roles: [],
      tenant_users: [],
      manageable_roles: [],
      role_summary: [],
    }

    resetQueuesState()

    expect(governanceState.queues).toEqual([])
    expect(governanceState.queueFeedback).toBe('')
    expect(governanceState.editingQueueId).toBeNull()
    expect(governanceState.aclData).not.toBeNull()
  })

  it('limpa apenas o domínio de acl sem apagar filas já carregadas', () => {
    governanceState.queues = [{ id: 1, active: true, waiting_count: 1 }]
    governanceState.aclData = {
      current_user: { name: 'Alice' },
      roles: [],
      tenant_users: [],
      manageable_roles: [],
      role_summary: [],
    }
    governanceState.aclActionFeedback = 'ok'

    resetAclState()

    expect(governanceState.aclData).toBeNull()
    expect(governanceState.aclActionFeedback).toBe('')
    expect(governanceState.queues).toHaveLength(1)
  })
})
