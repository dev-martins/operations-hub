import { computed, reactive } from 'vue'
import { hasPermission } from './authSession'
import {
  assignAttendance,
  createAttendance,
  fetchAssignableUsers,
  fetchAttendance,
  fetchAttendances,
  fetchQueues,
  updateAttendanceStatus,
} from '../services/attendanceService'

const buildFilters = () => ({
  status: '',
  priority: '',
  queue_id: '',
})

const buildCreateForm = () => ({
  title: '',
  description: '',
  type: 'incident',
  origin: 'manual',
  priority: 'medium',
  queue_id: '',
})

const buildStatusForm = () => ({
  status: '',
  resolution_notes: '',
})

const buildAssignmentForm = () => ({
  assigned_to: '',
})

const state = reactive({
  loading: true,
  initialized: false,
  savingAttendance: false,
  updatingStatus: false,
  updatingAssignment: false,
  queues: [],
  users: [],
  attendances: [],
  selectedAttendance: null,
  actionErrors: {},
  actionFeedback: '',
  filters: buildFilters(),
  createForm: buildCreateForm(),
  createFormErrors: {},
  statusForm: buildStatusForm(),
  assignmentForm: buildAssignmentForm(),
})

export const operationalQueueState = state

export const hasAttendances = computed(() => state.attendances.length > 0)
export const hasUsers = computed(() => state.users.length > 0)
export const requiresResolutionNotes = computed(() => state.statusForm.status === 'resolved')
export const detailEvents = computed(() => state.selectedAttendance?.events ?? [])
export const assigneeName = computed(() => state.selectedAttendance?.assignee?.name ?? 'Não atribuído')
export const creatorName = computed(() => state.selectedAttendance?.creator?.name ?? 'Não identificado')
export const canCreateAttendance = computed(() => hasPermission('attendances.create'))
export const canAssignAttendance = computed(() => hasPermission('attendances.assign'))
export const queueCards = computed(() => {
  return state.queues.map((queue) => ({
    ...queue,
    waitingText: queue.waiting_count === 1 ? '1 atendimento' : `${queue.waiting_count} atendimentos`,
  }))
})
export const operationalMetrics = computed(() => {
  const total = state.attendances.length
  const openCount = state.attendances.filter(({ status }) => status === 'open').length
  const criticalCount = state.attendances.filter(({ priority }) => priority === 'critical').length
  const assignedCount = state.attendances.filter(({ assigned_to }) => assigned_to !== null).length

  return [
    {
      title: 'Na tela',
      value: total.toString(),
      note: 'Recorte atual da fila.',
    },
    {
      title: 'Abertos',
      value: openCount.toString(),
      note: 'Demandas aguardando triagem.',
    },
    {
      title: 'Críticos',
      value: criticalCount.toString(),
      note: 'Itens com maior impacto.',
    },
    {
      title: 'Atribuídos',
      value: assignedCount.toString(),
      note: 'Com responsável definido.',
    },
  ]
})

const resetActionFeedback = () => {
  state.actionErrors = {}
  state.actionFeedback = ''
}

const syncSelectedAttendanceForms = () => {
  state.statusForm.status = state.selectedAttendance?.status ?? ''
  state.statusForm.resolution_notes = state.selectedAttendance?.resolution_notes ?? ''
  state.assignmentForm.assigned_to = state.selectedAttendance?.assigned_to ?? ''
}

export const loadOperationalQueues = async () => {
  const data = await fetchQueues()
  state.queues = data

  if (!state.createForm.queue_id && data.length > 0) {
    state.createForm.queue_id = data[0].id
  }
}

export const loadOperationalUsers = async () => {
  if (!canAssignAttendance.value) {
    state.users = []
    return
  }

  state.users = await fetchAssignableUsers()
}

export const loadOperationalAttendances = async () => {
  const activeFilters = Object.fromEntries(
    Object.entries(state.filters).filter(([, value]) => value !== ''),
  )
  const data = await fetchAttendances({
    ...activeFilters,
    operational_only: true,
  })

  state.attendances = data.data
  const currentSelectedId = state.selectedAttendance?.id ?? null

  if (state.attendances.length === 0) {
    state.selectedAttendance = null
    syncSelectedAttendanceForms()
    return
  }

  if (currentSelectedId === null) {
    await selectOperationalAttendance(state.attendances[0].id)
    return
  }

  const selectedStillVisible = state.attendances.some(({ id }) => id === currentSelectedId)

  if (!selectedStillVisible) {
    await selectOperationalAttendance(state.attendances[0].id)
    return
  }

  await selectOperationalAttendance(currentSelectedId)
}

export const loadOperationalView = async () => {
  state.loading = true

  try {
    const tasks = [
      loadOperationalQueues(),
      loadOperationalAttendances(),
    ]

    if (canAssignAttendance.value) {
      tasks.push(loadOperationalUsers())
    }

    await Promise.all(tasks)
    state.initialized = true
  } finally {
    state.loading = false
  }
}

export const ensureOperationalView = async () => {
  if (state.loading && state.initialized) {
    return
  }

  if (state.initialized) {
    return
  }

  await loadOperationalView()
}

export const selectOperationalAttendance = async (attendanceId) => {
  resetActionFeedback()
  state.selectedAttendance = await fetchAttendance(attendanceId)
  syncSelectedAttendanceForms()
}

export const refreshOperationalPanels = async (attendanceId) => {
  await Promise.all([
    loadOperationalQueues(),
    loadOperationalAttendances(),
  ])

  if (attendanceId) {
    state.selectedAttendance = await fetchAttendance(attendanceId)
    syncSelectedAttendanceForms()
  }
}

export const submitOperationalAttendance = async () => {
  state.savingAttendance = true
  state.createFormErrors = {}

  try {
    const attendance = await createAttendance({
      ...state.createForm,
      queue_id: Number(state.createForm.queue_id),
    })

    state.createForm.title = ''
    state.createForm.description = ''
    state.createForm.type = 'incident'
    state.createForm.origin = 'manual'
    state.createForm.priority = 'medium'

    await refreshOperationalPanels(attendance.id)
  } catch (error) {
    state.createFormErrors = error.response?.data?.errors ?? {}
  } finally {
    state.savingAttendance = false
  }
}

export const submitOperationalStatusUpdate = async () => {
  if (!state.selectedAttendance || !state.statusForm.status) {
    return
  }

  state.updatingStatus = true
  resetActionFeedback()

  try {
    const payload = {
      status: state.statusForm.status,
    }

    if (requiresResolutionNotes.value) {
      payload.resolution_notes = state.statusForm.resolution_notes
    }

    await updateAttendanceStatus(state.selectedAttendance.id, payload)
    await refreshOperationalPanels(state.selectedAttendance.id)
    state.actionFeedback = 'Status atualizado com sucesso.'
  } catch (error) {
    state.actionErrors = error.response?.data?.errors ?? {}
  } finally {
    state.updatingStatus = false
  }
}

export const submitOperationalAssignmentUpdate = async () => {
  if (!state.selectedAttendance || !state.assignmentForm.assigned_to) {
    return
  }

  state.updatingAssignment = true
  resetActionFeedback()

  try {
    await assignAttendance(state.selectedAttendance.id, {
      assigned_to: Number(state.assignmentForm.assigned_to),
    })
    await refreshOperationalPanels(state.selectedAttendance.id)
    state.actionFeedback = 'Responsável atualizado com sucesso.'
  } catch (error) {
    state.actionErrors = error.response?.data?.errors ?? {}
  } finally {
    state.updatingAssignment = false
  }
}

export const resetOperationalQueueState = () => {
  state.loading = true
  state.initialized = false
  state.savingAttendance = false
  state.updatingStatus = false
  state.updatingAssignment = false
  state.queues = []
  state.users = []
  state.attendances = []
  state.selectedAttendance = null
  state.actionErrors = {}
  state.actionFeedback = ''
  state.filters = buildFilters()
  state.createForm = buildCreateForm()
  state.createFormErrors = {}
  state.statusForm = buildStatusForm()
  state.assignmentForm = buildAssignmentForm()
}
