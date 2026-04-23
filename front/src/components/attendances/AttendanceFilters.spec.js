import { mount } from '@vue/test-utils'
import { reactive } from 'vue'
import { describe, expect, it } from 'vitest'
import AttendanceFilters from './AttendanceFilters.vue'

describe('AttendanceFilters', () => {
  it('atualiza filtros e emite apply ao trocar campos', async () => {
    const filters = reactive({
      status: '',
      priority: '',
      queue_id: '',
      page: 1,
    })

    const wrapper = mount(AttendanceFilters, {
      props: {
        filters,
        queues: [{ id: 3, name: 'Suporte N2' }],
      },
    })

    await wrapper.get('[data-testid="attendance-filter-status"]').setValue('waiting_external')
    await wrapper.get('[data-testid="attendance-filter-priority"]').setValue('critical')
    await wrapper.get('[data-testid="attendance-filter-queue"]').setValue('3')

    expect(filters.status).toBe('waiting_external')
    expect(filters.priority).toBe('critical')
    expect(filters.queue_id).toBe(3)
    expect(wrapper.emitted('apply')).toHaveLength(3)
  })

  it('emite reset ao acionar limpeza de filtros', async () => {
    const wrapper = mount(AttendanceFilters, {
      props: {
        filters: reactive({
          status: 'open',
          priority: 'high',
          queue_id: '1',
          page: 2,
        }),
        queues: [],
      },
    })

    await wrapper.get('[data-testid="attendance-filter-reset"]').trigger('click')

    expect(wrapper.emitted('reset')).toHaveLength(1)
  })
})
