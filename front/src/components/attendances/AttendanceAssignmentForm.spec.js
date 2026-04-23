import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttendanceAssignmentForm from './AttendanceAssignmentForm.vue'

describe('AttendanceAssignmentForm', () => {
  it('renderiza estado bloqueado quando a ACL não permite reatribuição', () => {
    const wrapper = mount(AttendanceAssignmentForm, {
      props: {
        canEdit: false,
        permissionMessage: 'A reatribuição de atendimentos fica disponível apenas para supervisão e administração.',
      },
    })

    expect(wrapper.get('[data-testid="assignment-blocked-state"]').text()).toContain('supervisão e administração')
    expect(wrapper.find('[data-testid="assignment-select"]').exists()).toBe(false)
  })

  it('desabilita seleção quando não há operadores disponíveis', () => {
    const wrapper = mount(AttendanceAssignmentForm, {
      props: {
        canEdit: true,
        hasUsers: false,
        submitDisabled: true,
        users: [],
      },
    })

    expect(wrapper.get('[data-testid="assignment-select"]').element.disabled).toBe(true)
    expect(wrapper.get('[data-testid="assignment-submit"]').attributes('disabled')).toBeDefined()
  })
})
