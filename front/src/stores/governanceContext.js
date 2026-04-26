import { computed, reactive } from 'vue'
import { fetchAclOverview } from '../services/authService'
import { createQueue, fetchQueuesOverview, updateQueue, updateUserRole } from '../services/governanceService'

const buildQueueForm = () => ({
  name: '',
  code: '',
  description: '',
  active: true,
})

const state = reactive({
  queuesLoading: false,
  queuesSaving: false,
  queues: [],
  queueFeedback: '',
  queueErrors: {},
  editingQueueId: null,
  queueForm: buildQueueForm(),
  aclLoading: false,
  aclData: null,
  aclActionErrors: {},
  aclActionFeedback: '',
  updatingUserId: null,
})

export const governanceState = state

export const activeQueuesCount = computed(() => state.queues.filter((queue) => queue.active).length)
export const inactiveQueuesCount = computed(() => state.queues.filter((queue) => !queue.active).length)
export const totalWaitingCount = computed(() => {
  return state.queues.reduce((sum, queue) => sum + (queue.waiting_count ?? 0), 0)
})
export const queueSubmitLabel = computed(() => state.editingQueueId ? 'Salvar fila' : 'Criar fila')

export const resetQueueFeedback = () => {
  state.queueFeedback = ''
  state.queueErrors = {}
}

export const resetQueueForm = () => {
  state.queueForm = buildQueueForm()
  state.editingQueueId = null
}

export const loadQueuesOverview = async () => {
  state.queuesLoading = true

  try {
    state.queues = await fetchQueuesOverview()
  } finally {
    state.queuesLoading = false
  }
}

export const startQueueEditing = (queue) => {
  resetQueueFeedback()
  state.editingQueueId = queue.id
  state.queueForm = {
    name: queue.name,
    code: queue.code,
    description: queue.description ?? '',
    active: Boolean(queue.active),
  }
}

export const cancelQueueEditing = () => {
  resetQueueFeedback()
  resetQueueForm()
}

export const submitQueue = async () => {
  state.queuesSaving = true
  resetQueueFeedback()

  try {
    const payload = {
      name: state.queueForm.name,
      code: state.queueForm.code,
      description: state.queueForm.description,
      active: state.queueForm.active,
    }

    if (state.editingQueueId) {
      await updateQueue(state.editingQueueId, payload)
      state.queueFeedback = 'Fila atualizada com sucesso.'
    } else {
      await createQueue(payload)
      state.queueFeedback = 'Fila criada com sucesso.'
    }

    resetQueueForm()
    await loadQueuesOverview()
  } catch (error) {
    state.queueErrors = error.response?.data?.errors ?? {}
    state.queueFeedback = error.response?.data?.message ?? 'Não foi possível salvar a fila.'
  } finally {
    state.queuesSaving = false
  }
}

export const loadAclOverview = async () => {
  state.aclLoading = true

  try {
    state.aclData = await fetchAclOverview()
  } finally {
    state.aclLoading = false
  }
}

export const submitRoleUpdate = async (userId, role) => {
  state.aclActionErrors = {}
  state.aclActionFeedback = ''
  state.updatingUserId = userId

  try {
    await updateUserRole(userId, { role })
    state.aclActionFeedback = 'Papel atualizado com sucesso.'
    await loadAclOverview()
  } catch (error) {
    state.aclActionErrors = error.response?.data?.errors ?? {}
    state.aclActionFeedback = error.response?.data?.message ?? 'Não foi possível atualizar o papel.'
  } finally {
    state.updatingUserId = null
  }
}

export const resetGovernanceState = () => {
  state.queuesLoading = false
  state.queuesSaving = false
  state.queues = []
  state.queueFeedback = ''
  state.queueErrors = {}
  state.editingQueueId = null
  state.queueForm = buildQueueForm()
  state.aclLoading = false
  state.aclData = null
  state.aclActionErrors = {}
  state.aclActionFeedback = ''
  state.updatingUserId = null
}
