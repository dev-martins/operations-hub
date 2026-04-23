import { computed, unref } from 'vue'
import { authState, hasPermission as defaultHasPermission } from '../stores/authSession'

const terminalStatuses = ['resolved', 'cancelled']

export const useAttendancePermissions = (selectedAttendance, options = {}) => {
  const permissionChecker = options.hasPermission ?? defaultHasPermission
  const currentUser = options.currentUser ?? computed(() => authState.user)

  const canUpdateStatus = computed(() => permissionChecker('attendances.update_status'))
  const canAssignAttendance = computed(() => permissionChecker('attendances.assign'))
  const canResolveAttendance = computed(() => permissionChecker('attendances.resolve'))
  const canCancelAttendance = computed(() => permissionChecker('attendances.cancel'))
  const selectedAttendanceIsTerminal = computed(() => {
    return terminalStatuses.includes(unref(selectedAttendance)?.status ?? '')
  })
  const operatorOwnsSelectedAttendance = computed(() => {
    const attendance = unref(selectedAttendance)

    if (!attendance) {
      return false
    }

    const currentUserId = unref(currentUser)?.id ?? null

    return attendance.assigned_to === null || attendance.assigned_to === currentUserId
  })
  const canUpdateSelectedAttendanceStatus = computed(() => {
    if (!unref(selectedAttendance) || !canUpdateStatus.value || selectedAttendanceIsTerminal.value) {
      return false
    }

    if (canResolveAttendance.value || canAssignAttendance.value) {
      return true
    }

    return operatorOwnsSelectedAttendance.value
  })
  const canManageSelectedAssignment = computed(() => {
    return Boolean(unref(selectedAttendance) && canAssignAttendance.value && !selectedAttendanceIsTerminal.value)
  })
  const availableStatusOptions = computed(() => {
    const options = [
      { value: 'open', label: 'Aberto' },
      { value: 'in_progress', label: 'Em atendimento' },
      { value: 'waiting_external', label: 'Aguardando externo' },
    ]

    if (canResolveAttendance.value) {
      options.push({ value: 'resolved', label: 'Resolvido' })
    }

    if (canCancelAttendance.value) {
      options.push({ value: 'cancelled', label: 'Cancelado' })
    }

    return options
  })
  const statusPermissionMessage = computed(() => {
    if (!unref(selectedAttendance)) {
      return ''
    }

    if (!canUpdateStatus.value) {
      return 'Seu papel pode acompanhar o atendimento, mas não alterar seu status.'
    }

    if (selectedAttendanceIsTerminal.value) {
      return 'Atendimentos encerrados não aceitam novas mudanças de status.'
    }

    if (!canUpdateSelectedAttendanceStatus.value) {
      return 'Como operador, você só pode avançar atendimentos sem responsável ou atribuídos a você.'
    }

    return ''
  })
  const assignmentPermissionMessage = computed(() => {
    if (!unref(selectedAttendance)) {
      return ''
    }

    if (!canAssignAttendance.value) {
      return 'A reatribuição de atendimentos fica disponível apenas para supervisão e administração.'
    }

    if (selectedAttendanceIsTerminal.value) {
      return 'Atendimentos encerrados não podem ser reatribuídos.'
    }

    return ''
  })

  return {
    availableStatusOptions,
    canAssignAttendance,
    canCancelAttendance,
    canManageSelectedAssignment,
    canResolveAttendance,
    canUpdateSelectedAttendanceStatus,
    canUpdateStatus,
    operatorOwnsSelectedAttendance,
    selectedAttendanceIsTerminal,
    statusPermissionMessage,
    assignmentPermissionMessage,
  }
}
