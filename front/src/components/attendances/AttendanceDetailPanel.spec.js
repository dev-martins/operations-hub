import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttendanceDetailPanel from './AttendanceDetailPanel.vue'

const buildProps = (overrides = {}) => ({
  actionErrors: {},
  actionFeedback: '',
  assignmentForm: {
    assigned_to: 7,
  },
  assignmentPermissionMessage: '',
  availableStatusOptions: [
    { value: 'open', label: 'Aberto' },
    { value: 'resolved', label: 'Resolvido' },
  ],
  canManageSelectedAssignment: true,
  canUpdateSelectedAttendanceStatus: true,
  detailEvents: [],
  hasUsers: true,
  requiresResolutionNotes: false,
  selectedAttendance: null,
  statusForm: {
    status: 'open',
    resolution_notes: '',
  },
  statusPermissionMessage: '',
  updatingAssignment: false,
  updatingStatus: false,
  users: [
    { id: 7, name: 'Ana Operadora', email: 'ana@example.com' },
  ],
  ...overrides,
})

describe('AttendanceDetailPanel', () => {
  it('renderiza estado vazio quando nenhum atendimento foi selecionado', () => {
    const wrapper = mount(AttendanceDetailPanel, {
      props: buildProps(),
    })

    expect(wrapper.get('[data-testid="attendance-detail-empty"]').text()).toContain('Selecione um atendimento')
  })

  it('renderiza detalhe e timeline quando há atendimento selecionado', () => {
    const wrapper = mount(AttendanceDetailPanel, {
      props: buildProps({
        detailEvents: [
          {
            id: 1,
            description: 'Atendimento criado',
            type: 'created',
            created_at: '2026-04-23T12:00:00Z',
          },
        ],
        selectedAttendance: {
          id: 101,
          protocol: 'AT-101',
          title: 'Webhook parado',
          description: 'Fila sem retorno do legado.',
          queue: { name: 'Suporte N1' },
          creator: { name: 'Alice Admin' },
          assignee: { name: 'Ana Operadora' },
          status: 'open',
          status_label: 'Aberto',
          priority: 'high',
          priority_label: 'Alta',
          origin_label: 'Manual',
          type_label: 'Incidente',
          opened_at: '2026-04-23T10:00:00Z',
        },
      }),
    })

    expect(wrapper.get('[data-testid="attendance-detail-panel"]').text()).toContain('Alice Admin')
    expect(wrapper.get('[data-testid="attendance-timeline"]').text()).toContain('Atendimento criado')
  })
})
