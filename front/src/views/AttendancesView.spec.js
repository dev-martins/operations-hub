import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AttendancesView from './AttendancesView.vue'

const attendanceServiceMocks = vi.hoisted(() => ({
  assignAttendance: vi.fn(),
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

const baseQueues = [
  { id: 1, name: 'Suporte N1' },
]

const attendanceList = [
  {
    id: 101,
    protocol: 'AT-101',
    title: 'Webhook parado',
    queue: { name: 'Suporte N1' },
    status: 'open',
    status_label: 'Aberto',
    priority: 'high',
    priority_label: 'Alta',
    assignee: { name: 'Ana Operadora' },
    assigned_to: 7,
    opened_at: '2026-04-23T10:00:00Z',
  },
]

const attendanceDetail = {
  id: 101,
  protocol: 'AT-101',
  title: 'Webhook parado',
  description: 'Fila sem retorno do legado.',
  queue: { name: 'Suporte N1' },
  status: 'open',
  status_label: 'Aberto',
  priority: 'high',
  priority_label: 'Alta',
  assignee: { name: 'Ana Operadora' },
  assigned_to: 7,
  origin_label: 'Manual',
  type_label: 'Incidente',
  opened_at: '2026-04-23T10:00:00Z',
  resolution_notes: '',
  events: [],
}

const paginatedResponse = {
  data: attendanceList,
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 1,
    from: 1,
    to: 1,
  },
}

const mountView = async () => {
  const wrapper = mount(AttendancesView)
  await flushPromises()
  await nextTick()
  return wrapper
}

describe('AttendancesView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    authSessionMock.authState.user = {
      id: 7,
      permissions: [],
    }

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return [
        'attendances.view',
        'attendances.update_status',
        'attendances.assign',
        'attendances.resolve',
      ].includes(permission)
    })

    attendanceServiceMocks.fetchQueues.mockResolvedValue(baseQueues)
    attendanceServiceMocks.fetchAttendances.mockResolvedValue(paginatedResponse)
    attendanceServiceMocks.fetchAttendance.mockResolvedValue(attendanceDetail)
    attendanceServiceMocks.fetchAssignableUsers.mockResolvedValue([
      { id: 7, name: 'Ana Operadora', email: 'ana@example.com' },
      { id: 8, name: 'Bruno Supervisor', email: 'bruno@example.com' },
    ])
    attendanceServiceMocks.updateAttendanceStatus.mockResolvedValue({})
    attendanceServiceMocks.assignAttendance.mockResolvedValue({})
  })

  it('exibe estado bloqueado de status para operador sem posse do atendimento', async () => {
    authSessionMock.authState.user = {
      id: 2,
      permissions: [],
    }
    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['attendances.view', 'attendances.update_status'].includes(permission)
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Como operador, você só pode avançar atendimentos sem responsável ou atribuídos a você.')
    expect(wrapper.find('[data-testid="status-select"]').exists()).toBe(false)
  })

  it('bloqueia atribuição e status quando o atendimento está encerrado', async () => {
    attendanceServiceMocks.fetchAttendance.mockResolvedValue({
      ...attendanceDetail,
      status: 'resolved',
      status_label: 'Resolvido',
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Atendimentos encerrados não aceitam novas mudanças de status.')
    expect(wrapper.text()).toContain('Atendimentos encerrados não podem ser reatribuídos.')
  })

  it('mostra opções de status conforme permissões e envia atualização com notas de resolução', async () => {
    const wrapper = await mountView()

    const statusSelect = wrapper.get('[data-testid="status-select"]')
    const options = statusSelect.findAll('option').map((option) => option.element.value)

    expect(options).toEqual(['open', 'in_progress', 'waiting_external', 'resolved'])

    await statusSelect.setValue('resolved')
    await wrapper.get('[data-testid="resolution-notes"]').setValue('Retomado após ajuste no legado')
    await wrapper.get('[data-testid="status-submit"]').trigger('click')
    await flushPromises()

    expect(attendanceServiceMocks.updateAttendanceStatus).toHaveBeenCalledWith(101, {
      status: 'resolved',
      resolution_notes: 'Retomado após ajuste no legado',
    })
    expect(wrapper.text()).toContain('Status atualizado com sucesso.')
  })

  it('exibe mensagem de erro retornada pela API ao atribuir responsável', async () => {
    attendanceServiceMocks.assignAttendance.mockRejectedValue({
      response: {
        data: {
          message: 'Não foi possível reatribuir o atendimento.',
          errors: {
            assigned_to: ['Usuário indisponível.'],
          },
        },
      },
    })

    const wrapper = await mountView()

    await wrapper.get('[data-testid="assignment-select"]').setValue('8')
    await wrapper.get('[data-testid="assignment-submit"]').trigger('click')
    await flushPromises()

    expect(attendanceServiceMocks.assignAttendance).toHaveBeenCalledWith(101, {
      assigned_to: 8,
    })
    expect(wrapper.text()).toContain('Não foi possível reatribuir o atendimento.')
    expect(wrapper.text()).toContain('Usuário indisponível.')
  })
})
