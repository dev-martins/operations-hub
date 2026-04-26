import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  canAssignAttendance,
  canCreateAttendance,
  detailEvents,
  loadOperationalAttendances,
  loadOperationalQueues,
  loadOperationalUsers,
  operationalMetrics,
  operationalQueueState,
  queueCards,
  resetOperationalQueueState,
  submitOperationalAttendance,
  submitOperationalStatusUpdate,
} from './operationalQueueContext'

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
  hasPermission: vi.fn(),
}))

vi.mock('../services/attendanceService', () => attendanceServiceMocks)
vi.mock('./authSession', () => authSessionMock)

describe('operationalQueueContext', () => {
  beforeEach(() => {
    resetOperationalQueueState()
    vi.clearAllMocks()

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['attendances.create', 'attendances.assign'].includes(permission)
    })
  })

  it('carrega filas e define queue inicial do formulário', async () => {
    attendanceServiceMocks.fetchQueues.mockResolvedValue([
      { id: 1, name: 'Suporte N1', waiting_count: 2 },
    ])

    await loadOperationalQueues()

    expect(operationalQueueState.queues).toHaveLength(1)
    expect(operationalQueueState.createForm.queue_id).toBe(1)
    expect(queueCards.value[0].waitingText).toBe('2 atendimentos')
  })

  it('carrega atendimentos e calcula métricas do recorte atual', async () => {
    attendanceServiceMocks.fetchAttendances.mockResolvedValue({
      data: [
        {
          id: 10,
          status: 'open',
          priority: 'critical',
          assigned_to: null,
        },
        {
          id: 11,
          status: 'open',
          priority: 'low',
          assigned_to: 7,
        },
      ],
    })
    attendanceServiceMocks.fetchAttendance.mockResolvedValue({
      id: 10,
      events: [{ id: 1, description: 'Criado' }],
    })

    await loadOperationalAttendances()

    expect(operationalQueueState.attendances).toHaveLength(2)
    expect(operationalMetrics.value.map((metric) => metric.value)).toEqual(['2', '2', '1', '1'])
    expect(detailEvents.value).toHaveLength(1)
  })

  it('carrega usuários atribuíveis apenas quando a permissão existe', async () => {
    attendanceServiceMocks.fetchAssignableUsers.mockResolvedValue([{ id: 1, name: 'Ana' }])

    await loadOperationalUsers()

    expect(canAssignAttendance.value).toBe(true)
    expect(operationalQueueState.users).toHaveLength(1)
  })

  it('cria atendimento com queue_id numérico', async () => {
    attendanceServiceMocks.createAttendance.mockResolvedValue({ id: 77 })
    attendanceServiceMocks.fetchQueues.mockResolvedValue([{ id: 1, name: 'Suporte N1', waiting_count: 1 }])
    attendanceServiceMocks.fetchAttendances.mockResolvedValue({ data: [] })

    operationalQueueState.createForm.title = 'Webhook parado'
    operationalQueueState.createForm.description = 'Sem retorno'
    operationalQueueState.createForm.queue_id = '1'

    await submitOperationalAttendance()

    expect(canCreateAttendance.value).toBe(true)
    expect(attendanceServiceMocks.createAttendance).toHaveBeenCalledWith({
      title: 'Webhook parado',
      description: 'Sem retorno',
      type: 'incident',
      origin: 'manual',
      priority: 'medium',
      queue_id: 1,
    })
  })

  it('envia atualização de status com notas de resolução quando necessário', async () => {
    attendanceServiceMocks.updateAttendanceStatus.mockResolvedValue({})
    attendanceServiceMocks.fetchQueues.mockResolvedValue([])
    attendanceServiceMocks.fetchAttendances.mockResolvedValue({ data: [] })
    attendanceServiceMocks.fetchAttendance.mockResolvedValue({
      id: 99,
      status: 'resolved',
      resolution_notes: 'Concluído',
      events: [],
    })

    operationalQueueState.selectedAttendance = { id: 99 }
    operationalQueueState.statusForm.status = 'resolved'
    operationalQueueState.statusForm.resolution_notes = 'Concluído'

    await submitOperationalStatusUpdate()

    expect(attendanceServiceMocks.updateAttendanceStatus).toHaveBeenCalledWith(99, {
      status: 'resolved',
      resolution_notes: 'Concluído',
    })
  })
})
