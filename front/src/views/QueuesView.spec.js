import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import QueuesView from './QueuesView.vue'
import { governanceState, resetGovernanceState } from '../stores/governanceContext'

const governanceServiceMocks = vi.hoisted(() => ({
  createQueue: vi.fn(),
  fetchQueuesOverview: vi.fn(),
  updateQueue: vi.fn(),
}))

const authSessionMock = vi.hoisted(() => ({
  hasPermission: vi.fn(),
}))

vi.mock('../services/governanceService', () => governanceServiceMocks)
vi.mock('../stores/authSession', () => authSessionMock)

const baseQueues = [
  {
    id: 1,
    name: 'Suporte N1',
    code: 'SUP-N1',
    description: 'Fila principal',
    active: true,
    waiting_count: 3,
  },
]

const mountView = async () => {
  const wrapper = mount(QueuesView)
  await flushPromises()
  await nextTick()
  return wrapper
}

describe('QueuesView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    resetGovernanceState()

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['queues.view', 'queues.manage'].includes(permission)
    })

    governanceServiceMocks.fetchQueuesOverview.mockResolvedValue(baseQueues)
    governanceServiceMocks.createQueue.mockResolvedValue({ id: 2 })
    governanceServiceMocks.updateQueue.mockResolvedValue({})
  })

  it('renderiza estado bloqueado quando o papel não pode gerenciar filas', async () => {
    authSessionMock.hasPermission.mockImplementation((permission) => permission === 'queues.view')

    const wrapper = await mountView()

    expect(wrapper.get('[data-testid="queues-management-blocked"]').text()).toContain('administração operacional')
    expect(wrapper.find('[data-testid="queue-submit"]').exists()).toBe(false)
  })

  it('cria fila quando o formulário é enviado', async () => {
    const wrapper = await mountView()

    await wrapper.get('[data-testid="queue-name-input"]').setValue('Backoffice')
    await wrapper.get('[data-testid="queue-code-input"]').setValue('BACK')
    await wrapper.get('[data-testid="queue-description-input"]').setValue('Fila administrativa.')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(governanceServiceMocks.createQueue).toHaveBeenCalledWith({
      name: 'Backoffice',
      code: 'BACK',
      description: 'Fila administrativa.',
      active: true,
    })
    expect(wrapper.text()).toContain('Fila criada com sucesso.')
  })

  it('carrega fila na edição e envia atualização', async () => {
    const wrapper = await mountView()

    await wrapper.get('[data-testid="queue-edit-button"]').trigger('click')
    await wrapper.get('[data-testid="queue-name-input"]').setValue('Suporte N1 e N2')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(governanceServiceMocks.updateQueue).toHaveBeenCalledWith(1, {
      name: 'Suporte N1 e N2',
      code: 'SUP-N1',
      description: 'Fila principal',
      active: true,
    })
    expect(wrapper.text()).toContain('Fila atualizada com sucesso.')
  })

  it('reaproveita filas já carregadas ao montar novamente a visão', async () => {
    resetGovernanceState()
    governanceServiceMocks.fetchQueuesOverview.mockClear()
    governanceState.queues = baseQueues

    const wrapper = await mountView()

    expect(governanceServiceMocks.fetchQueuesOverview).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Suporte N1')
  })
})
