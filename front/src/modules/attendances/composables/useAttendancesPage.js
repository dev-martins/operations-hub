import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAttendancePermissions } from '../../../composables/useAttendancePermissions'
import {
  assignAttendance,
  fetchAssignableUsers,
  fetchAttendance,
  fetchAttendances,
  fetchQueues,
  updateAttendanceStatus,
} from '../../../services/attendanceService'

const buildDefaultPagination = () => ({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
})

const buildFilters = () => reactive({
  status: '',
  priority: '',
  queue_id: '',
  page: 1,
})

const buildStatusForm = () => reactive({
  status: '',
  resolution_notes: '',
})

const buildAssignmentForm = () => reactive({
  assigned_to: '',
})

const buildActionState = () => ({
  errors: ref({}),
  feedback: ref(''),
})

export const useAttendancesPage = (options = {}) => {
  const attendanceApi = options.attendanceApi ?? {
    assignAttendance,
    fetchAssignableUsers,
    fetchAttendance,
    fetchAttendances,
    fetchQueues,
    updateAttendanceStatus,
  }

  const loading = ref(true)
  const refreshing = ref(false)
  const updatingStatus = ref(false)
  const updatingAssignment = ref(false)
  const queues = ref([])
  const users = ref([])
  const attendances = ref([])
  const pagination = ref(buildDefaultPagination())
  const selectedAttendance = ref(null)
  const filters = buildFilters()
  const statusForm = buildStatusForm()
  const assignmentForm = buildAssignmentForm()
  const actionState = buildActionState()

  const hasUsers = computed(() => users.value.length > 0)
  const hasAttendances = computed(() => attendances.value.length > 0)
  const requiresResolutionNotes = computed(() => statusForm.status === 'resolved')

  const {
    assignmentPermissionMessage,
    availableStatusOptions,
    canAssignAttendance,
    canManageSelectedAssignment,
    canUpdateSelectedAttendanceStatus,
    statusPermissionMessage,
  } = useAttendancePermissions(selectedAttendance)

  const summaryCards = computed(() => {
    const openCount = attendances.value.filter(({ status }) => status === 'open').length
    const waitingExternalCount = attendances.value.filter(({ status }) => status === 'waiting_external').length
    const criticalCount = attendances.value.filter(({ priority }) => priority === 'critical').length

    return [
      {
        title: 'Total do recorte',
        value: pagination.value.total.toString(),
        note: 'Atendimentos retornados pelo filtro atual.',
      },
      {
        title: 'Na página',
        value: `${pagination.value.from || 0}-${pagination.value.to || 0}`,
        note: 'Faixa atual da paginação.',
      },
      {
        title: 'Abertos',
        value: openCount.toString(),
        note: 'Itens em abertura ou triagem.',
      },
      {
        title: 'Aguardando externo',
        value: waitingExternalCount.toString(),
        note: 'Dependências fora da operação.',
      },
      {
        title: 'Críticos',
        value: criticalCount.toString(),
        note: 'Maior impacto operacional.',
      },
    ]
  })

  const detailEvents = computed(() => selectedAttendance.value?.events ?? [])

  const resetActionFeedback = () => {
    actionState.errors.value = {}
    actionState.feedback.value = ''
  }

  const loadQueues = async () => {
    queues.value = await attendanceApi.fetchQueues()
  }

  const loadUsers = async () => {
    if (!canAssignAttendance.value) {
      users.value = []
      return
    }

    users.value = await attendanceApi.fetchAssignableUsers()
  }

  const activeFilterPayload = () => {
    return Object.fromEntries(
      Object.entries(filters).filter(([, value]) => value !== ''),
    )
  }

  const syncSelectedAttendanceAfterList = async (rows) => {
    if (rows.length === 0) {
      selectedAttendance.value = null
      return
    }

    const selectedId = selectedAttendance.value?.id
    const selectedStillExists = selectedId !== undefined && rows.some(({ id }) => id === selectedId)
    const targetId = selectedStillExists ? selectedId : rows[0].id

    selectedAttendance.value = await attendanceApi.fetchAttendance(targetId)
  }

  const loadAttendancesPage = async () => {
    const response = await attendanceApi.fetchAttendances(activeFilterPayload())

    attendances.value = response.data
    pagination.value = {
      current_page: response.meta.current_page,
      last_page: response.meta.last_page,
      per_page: response.meta.per_page,
      total: response.meta.total,
      from: response.meta.from ?? 0,
      to: response.meta.to ?? 0,
    }

    await syncSelectedAttendanceAfterList(response.data)
  }

  const loadView = async () => {
    loading.value = true

    try {
      const tasks = [
        loadQueues(),
        loadAttendancesPage(),
      ]

      if (canAssignAttendance.value) {
        tasks.push(loadUsers())
      }

      await Promise.all(tasks)
    } finally {
      loading.value = false
    }
  }

  const refreshView = async () => {
    refreshing.value = true

    try {
      await loadView()
    } finally {
      refreshing.value = false
    }
  }

  const selectAttendance = async (attendanceId) => {
    resetActionFeedback()
    selectedAttendance.value = await attendanceApi.fetchAttendance(attendanceId)
  }

  const goToPage = async (page) => {
    if (page < 1 || page > pagination.value.last_page || page === filters.page) {
      return
    }

    filters.page = page
    await loadAttendancesPage()
  }

  const applyFilters = async () => {
    filters.page = 1
    await loadAttendancesPage()
  }

  const resetFilters = async () => {
    filters.status = ''
    filters.priority = ''
    filters.queue_id = ''
    filters.page = 1
    await loadAttendancesPage()
  }

  const submitStatusUpdate = async () => {
    if (!selectedAttendance.value || !statusForm.status) {
      return
    }

    updatingStatus.value = true
    resetActionFeedback()

    try {
      const payload = {
        status: statusForm.status,
      }

      if (requiresResolutionNotes.value) {
        payload.resolution_notes = statusForm.resolution_notes
      }

      await attendanceApi.updateAttendanceStatus(selectedAttendance.value.id, payload)
      await loadAttendancesPage()
      actionState.feedback.value = 'Status atualizado com sucesso.'
    } catch (error) {
      actionState.errors.value = error.response?.data?.errors ?? {}

      if (error.response?.data?.message) {
        actionState.feedback.value = error.response.data.message
      }
    } finally {
      updatingStatus.value = false
    }
  }

  const submitAssignmentUpdate = async () => {
    if (!selectedAttendance.value || !assignmentForm.assigned_to) {
      return
    }

    updatingAssignment.value = true
    resetActionFeedback()

    try {
      await attendanceApi.assignAttendance(selectedAttendance.value.id, {
        assigned_to: Number(assignmentForm.assigned_to),
      })
      await loadAttendancesPage()
      actionState.feedback.value = 'Responsável atualizado com sucesso.'
    } catch (error) {
      actionState.errors.value = error.response?.data?.errors ?? {}

      if (error.response?.data?.message) {
        actionState.feedback.value = error.response.data.message
      }
    } finally {
      updatingAssignment.value = false
    }
  }

  watch(selectedAttendance, (attendance) => {
    statusForm.status = attendance?.status ?? ''
    statusForm.resolution_notes = attendance?.resolution_notes ?? ''
    assignmentForm.assigned_to = attendance?.assigned_to ?? ''
  }, { immediate: true })

  onMounted(async () => {
    await loadView()
  })

  return {
    actionErrors: actionState.errors,
    actionFeedback: actionState.feedback,
    applyFilters,
    assignmentForm,
    assignmentPermissionMessage,
    attendances,
    availableStatusOptions,
    canManageSelectedAssignment,
    canUpdateSelectedAttendanceStatus,
    detailEvents,
    filters,
    goToPage,
    hasAttendances,
    hasUsers,
    loading,
    pagination,
    queues,
    refreshing,
    refreshView,
    requiresResolutionNotes,
    resetFilters,
    selectAttendance,
    selectedAttendance,
    statusForm,
    statusPermissionMessage,
    submitAssignmentUpdate,
    submitStatusUpdate,
    summaryCards,
    updatingAssignment,
    updatingStatus,
    users,
  }
}
