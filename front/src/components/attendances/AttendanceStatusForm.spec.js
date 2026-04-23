import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttendanceStatusForm from './AttendanceStatusForm.vue'

describe('AttendanceStatusForm', () => {
  it('renderiza estado bloqueado quando o usuário não pode alterar status', () => {
    const wrapper = mount(AttendanceStatusForm, {
      props: {
        canEdit: false,
        permissionMessage: 'Seu papel pode acompanhar o atendimento, mas não alterar seu status.',
      },
    })

    expect(wrapper.get('[data-testid="status-blocked-state"]').text()).toContain('não alterar seu status')
    expect(wrapper.find('[data-testid="status-select"]').exists()).toBe(false)
  })

  it('exibe campo de notas quando o fluxo exige resolução', () => {
    const wrapper = mount(AttendanceStatusForm, {
      props: {
        canEdit: true,
        options: [
          { value: 'open', label: 'Aberto' },
          { value: 'resolved', label: 'Resolvido' },
        ],
        requiresResolutionNotes: true,
        resolutionNotes: '',
        status: 'resolved',
      },
    })

    expect(wrapper.get('[data-testid="status-select"]').element.value).toBe('resolved')
    expect(wrapper.find('[data-testid="resolution-notes"]').exists()).toBe(true)
  })
})
