import { computed, ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { useAttendancePermissions } from './useAttendancePermissions'

const makePermissionChecker = (permissions) => {
  return (permission) => permissions.includes(permission)
}

describe('useAttendancePermissions', () => {
  it('bloqueia operador sem posse do atendimento e exibe mensagem explicativa', () => {
    const selectedAttendance = ref({
      id: 10,
      status: 'open',
      assigned_to: 8,
    })

    const permissions = useAttendancePermissions(selectedAttendance, {
      currentUser: computed(() => ({ id: 2 })),
      hasPermission: makePermissionChecker(['attendances.update_status']),
    })

    expect(permissions.canUpdateSelectedAttendanceStatus.value).toBe(false)
    expect(permissions.statusPermissionMessage.value).toContain('atribuídos a você')
  })

  it('libera status finais apenas quando o papel possui as permissões correspondentes', () => {
    const selectedAttendance = ref({
      id: 11,
      status: 'in_progress',
      assigned_to: 5,
    })

    const permissions = useAttendancePermissions(selectedAttendance, {
      currentUser: computed(() => ({ id: 5 })),
      hasPermission: makePermissionChecker([
        'attendances.update_status',
        'attendances.resolve',
      ]),
    })

    expect(permissions.canUpdateSelectedAttendanceStatus.value).toBe(true)
    expect(permissions.availableStatusOptions.value.map(({ value }) => value)).toEqual([
      'open',
      'in_progress',
      'waiting_external',
      'resolved',
    ])
  })

  it('bloqueia reatribuição para atendimentos terminais', () => {
    const selectedAttendance = ref({
      id: 12,
      status: 'resolved',
      assigned_to: 5,
    })

    const permissions = useAttendancePermissions(selectedAttendance, {
      currentUser: computed(() => ({ id: 5 })),
      hasPermission: makePermissionChecker(['attendances.assign']),
    })

    expect(permissions.canManageSelectedAssignment.value).toBe(false)
    expect(permissions.assignmentPermissionMessage.value).toContain('não podem ser reatribuídos')
  })
})
