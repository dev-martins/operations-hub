import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import OperationalQueueView from './OperationalQueueView.vue'

const attendanceServiceMocks = vi.hoisted(() => ({
  assignAttendance: vi.fn(),
  createAttendance: vi.fn(),
  fetchAssignableUsers: vi.fn(),
  fetchAttendance: vi.fn(),
  fetchAttendances: vi.fn(),
  fetchQueues: vi.fn(),
  updateAttendanceStatus: vi.fn(),
}))

const authSessionMock = vi.hoisted(() => ({
  authState: {
    user: null,
  },
  hasPermission: vi.fn(),
}))

vi.mock('../services/attendanceService', () => attendanceServiceMocks)
vi.mock('../stores/authSession', () => authSessionMock)

const queues = [
  {
    id: 1,
    name: 'Suporte N1',
    code: 'SUP-N1',
    description: 'Fila principal',
    waiting_count: 2,
  },
]

const attendancesResponse = {
  data: [
    {
      id: 301,
      protocol: 'AT-301',
      title: 'Integração sem retorno',
      origin_label: 'API',
      type_label: 'Integração',
      queue: { name: 'Suporte N1' },
      assignee: null,
      assigned_to: null,
      status: 'open',
      status_label: 'Aberto',
      priority: 'critical',
      priority_label: 'Crítica',
    },
  ],
}

const attendanceDetail = {
  id: 301,
  protocol: 'AT-301',
  title: 'Integração sem retorno',
  description: 'Endpoint legado indisponível.',
  queue: { name: 'Suporte N1' },
  assignee: null,
  assigned_to: null,
  status: 'open',
  status_label: 'Aberto',
  priority: 'critical',
  priority_label: 'Crítica',
  first_response_at: null,
  opened_at: '2026-04-23T10:00:00Z',
  resolved_at: null,
  resolution_notes: '',
  events: [],
}

const mountView = async () => {
  const wrapper = mount(OperationalQueueView)
  await flushPromises()
  await nextTick()
  return wrapper
}

describe('OperationalQueueView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    authSessionMock.authState.user = {
      id: 4,
      permissions: [],
    }

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return [
        'attendances.view',
        'attendances.create',
        'attendances.update_status',
      ].includes(permission)
    })

    attendanceServiceMocks.fetchQueues.mockResolvedValue(queues)
    attendanceServiceMocks.fetchAttendances.mockResolvedValue(attendancesResponse)
    attendanceServiceMocks.fetchAttendance.mockResolvedValue(attendanceDetail)
    attendanceServiceMocks.fetchAssignableUsers.mockResolvedValue([])
    attendanceServiceMocks.createAttendance.mockResolvedValue({ id: 302 })
    attendanceServiceMocks.updateAttendanceStatus.mockResolvedValue({})
    attendanceServiceMocks.assignAttendance.mockResolvedValue({})
  })

  it('renderiza estado bloqueado de atribuição quando o papel não possui permissão', async () => {
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('A reatribuição de atendimentos fica disponível apenas para supervisão e administração.')
    expect(wrapper.find('[data-testid="assignment-select"]').exists()).toBe(false)
  })

  it('exibe lista vazia quando a API não retorna atendimentos', async () => {
    attendanceServiceMocks.fetchAttendances.mockResolvedValue({ data: [] })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Nenhum atendimento encontrado para os filtros atuais.')
    expect(wrapper.text()).toContain('Selecione um atendimento na fila para visualizar o detalhe.')
  })

  it('envia criação de atendimento com queue_id numérico e atualiza a visão', async () => {
    const wrapper = await mountView()

    const inputs = wrapper.findAll('input.form-control')
    await inputs[0].setValue('Falha de sincronização')
    await wrapper.get('textarea.form-control').setValue('Fila travada após processamento.')
    const selects = wrapper.findAll('select.form-control')
    await selects[2].setValue('critical')
    await selects[3].setValue('1')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(attendanceServiceMocks.createAttendance).toHaveBeenCalledWith({
      title: 'Falha de sincronização',
      description: 'Fila travada após processamento.',
      type: 'incident',
      origin: 'manual',
      priority: 'critical',
      queue_id: 1,
    })
  })

  it('mantém o erro de validação ao falhar atualização de status', async () => {
    attendanceServiceMocks.updateAttendanceStatus.mockRejectedValue({
      response: {
        data: {
          errors: {
            status: ['Transição de status inválida.'],
          },
        },
      },
    })

    const wrapper = await mountView()

    await wrapper.get('[data-testid="status-select"]').setValue('in_progress')
    await wrapper.get('[data-testid="status-submit"]').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Transição de status inválida.')
    expect(wrapper.text()).not.toContain('Status atualizado com sucesso.')
  })
})
