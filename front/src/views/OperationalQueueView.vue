<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AttendanceAssignmentForm from '../components/attendances/AttendanceAssignmentForm.vue'
import AttendanceStatusForm from '../components/attendances/AttendanceStatusForm.vue'
import { useAttendancePermissions } from '../composables/useAttendancePermissions'
import { hasPermission } from '../stores/authSession'
import {
  assignAttendance,
  createAttendance,
  fetchAssignableUsers,
  fetchAttendance,
  fetchAttendances,
  fetchQueues,
  updateAttendanceStatus,
} from '../services/attendanceService'

const loading = ref(true)
const savingAttendance = ref(false)
const updatingStatus = ref(false)
const updatingAssignment = ref(false)
const queues = ref([])
const users = ref([])
const attendances = ref([])
const selectedAttendance = ref(null)
const actionErrors = ref({})
const actionFeedback = ref('')
const filters = reactive({
  status: '',
  priority: '',
  queue_id: '',
})
const form = reactive({
  title: '',
  description: '',
  type: 'incident',
  origin: 'manual',
  priority: 'medium',
  queue_id: '',
})
const formErrors = ref({})
const statusForm = reactive({
  status: '',
  resolution_notes: '',
})
const assignmentForm = reactive({
  assigned_to: '',
})

const priorityToneMap = {
  critical: 'is-critical',
  high: 'is-warning',
  medium: 'is-info',
  low: 'is-ok',
}

const statusToneMap = {
  open: 'is-critical',
  in_progress: 'is-warning',
  waiting_external: 'is-info',
  resolved: 'is-ok',
  cancelled: 'is-muted',
}

const metrics = computed(() => {
  const total = attendances.value.length
  const openCount = attendances.value.filter(({ status }) => status === 'open').length
  const criticalCount = attendances.value.filter(({ priority }) => priority === 'critical').length
  const assignedCount = attendances.value.filter(({ assigned_to }) => assigned_to !== null).length

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

const hasAttendances = computed(() => attendances.value.length > 0)
const hasUsers = computed(() => users.value.length > 0)
const requiresResolutionNotes = computed(() => statusForm.status === 'resolved')
const detailEvents = computed(() => selectedAttendance.value?.events ?? [])
const assigneeName = computed(() => selectedAttendance.value?.assignee?.name ?? 'Não atribuído')
const creatorName = computed(() => selectedAttendance.value?.creator?.name ?? 'Não identificado')
const canCreateAttendance = computed(() => hasPermission('attendances.create'))
const {
  assignmentPermissionMessage,
  availableStatusOptions,
  canAssignAttendance,
  canManageSelectedAssignment,
  canUpdateSelectedAttendanceStatus,
  statusPermissionMessage,
} = useAttendancePermissions(selectedAttendance)

const queueCards = computed(() => {
  return queues.value.map((queue) => ({
    ...queue,
    waitingText: queue.waiting_count === 1 ? '1 atendimento' : `${queue.waiting_count} atendimentos`,
  }))
})

const formatDateTime = (value) => {
  if (!value) {
    return 'Sem registro'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value))
}

const loadQueues = async () => {
  const data = await fetchQueues()
  queues.value = data

  if (!form.queue_id && data.length > 0) {
    form.queue_id = data[0].id
  }
}

const loadUsers = async () => {
  if (!canAssignAttendance.value) {
    users.value = []
    return
  }

  users.value = await fetchAssignableUsers()
}

const loadAttendances = async () => {
  const activeFilters = Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== ''),
  )
  const data = await fetchAttendances({
    ...activeFilters,
    operational_only: true,
  })

  attendances.value = data.data

  if (attendances.value.length > 0 && !selectedAttendance.value) {
    await selectAttendance(attendances.value[0].id)
  }
}

const loadView = async () => {
  loading.value = true

  try {
    const tasks = [
      loadQueues(),
      loadAttendances(),
    ]

    if (canAssignAttendance.value) {
      tasks.push(loadUsers())
    }

    await Promise.all(tasks)
  } finally {
    loading.value = false
  }
}

const selectAttendance = async (attendanceId) => {
  actionErrors.value = {}
  actionFeedback.value = ''
  selectedAttendance.value = await fetchAttendance(attendanceId)
}

const refreshOperationalPanels = async (attendanceId) => {
  await Promise.all([
    loadQueues(),
    loadAttendances(),
  ])

  if (attendanceId) {
    selectedAttendance.value = await fetchAttendance(attendanceId)
  }
}

const submitAttendance = async () => {
  savingAttendance.value = true
  formErrors.value = {}

  try {
    const attendance = await createAttendance({
      ...form,
      queue_id: Number(form.queue_id),
    })

    form.title = ''
    form.description = ''
    form.type = 'incident'
    form.origin = 'manual'
    form.priority = 'medium'

    await refreshOperationalPanels(attendance.id)
  } catch (error) {
    formErrors.value = error.response?.data?.errors ?? {}
  } finally {
    savingAttendance.value = false
  }
}

const submitStatusUpdate = async () => {
  if (!selectedAttendance.value || !statusForm.status) {
    return
  }

  updatingStatus.value = true
  actionErrors.value = {}
  actionFeedback.value = ''

  try {
    const payload = {
      status: statusForm.status,
    }

    if (requiresResolutionNotes.value) {
      payload.resolution_notes = statusForm.resolution_notes
    }

    await updateAttendanceStatus(selectedAttendance.value.id, payload)
    await refreshOperationalPanels(selectedAttendance.value.id)
    actionFeedback.value = 'Status atualizado com sucesso.'
  } catch (error) {
    actionErrors.value = error.response?.data?.errors ?? {}
  } finally {
    updatingStatus.value = false
  }
}

const submitAssignmentUpdate = async () => {
  if (!selectedAttendance.value || !assignmentForm.assigned_to) {
    return
  }

  updatingAssignment.value = true
  actionErrors.value = {}
  actionFeedback.value = ''

  try {
    await assignAttendance(selectedAttendance.value.id, {
      assigned_to: Number(assignmentForm.assigned_to),
    })
    await refreshOperationalPanels(selectedAttendance.value.id)
    actionFeedback.value = 'Responsável atualizado com sucesso.'
  } catch (error) {
    actionErrors.value = error.response?.data?.errors ?? {}
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
</script>

<template>
  <div class="row gap-20">
    <div
      v-for="metric in metrics"
      :key="metric.title"
      class="col-md-6 col-xl-3"
    >
      <div class="layers bd bgc-white p-20 metric-card h-100 compact-card">
        <div class="layer w-100">
          <h6>{{ metric.title }}</h6>
          <h2>{{ metric.value }}</h2>
          <small class="c-grey-600">{{ metric.note }}</small>
        </div>
      </div>
    </div>

    <div v-if="canCreateAttendance" class="col-lg-4">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Novo atendimento</h5>
          <span class="section-kicker">rápido</span>
        </div>

        <form class="layers gap-10" @submit.prevent="submitAttendance">
          <div class="layer w-100">
            <label class="form-label">Título</label>
            <input v-model="form.title" class="form-control" type="text" placeholder="Ex.: webhook sem retorno" />
            <small v-if="formErrors.title" class="field-error">{{ formErrors.title[0] }}</small>
          </div>

          <div class="layer w-100">
            <label class="form-label">Descrição</label>
            <textarea v-model="form.description" class="form-control" rows="4" placeholder="Descreva o contexto operacional da ocorrência." />
            <small v-if="formErrors.description" class="field-error">{{ formErrors.description[0] }}</small>
          </div>

          <div class="layer w-100">
            <div class="row">
              <div class="col-md-6 mB-15">
                <label class="form-label">Tipo</label>
                <select v-model="form.type" class="form-control">
                  <option value="incident">Incidente</option>
                  <option value="request">Solicitação</option>
                  <option value="integration">Integração</option>
                  <option value="financial">Financeiro</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Origem</label>
                <select v-model="form.origin" class="form-control">
                  <option value="manual">Manual</option>
                  <option value="erp">ERP</option>
                  <option value="pdv">PDV</option>
                  <option value="portal">Portal</option>
                  <option value="api">API</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Prioridade</label>
                <select v-model="form.priority" class="form-control">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                  <option value="critical">Crítica</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Fila</label>
                <select v-model="form.queue_id" class="form-control">
                  <option v-for="queue in queues" :key="queue.id" :value="queue.id">
                    {{ queue.name }}
                  </option>
                </select>
                <small v-if="formErrors.queue_id" class="field-error">{{ formErrors.queue_id[0] }}</small>
              </div>
            </div>
          </div>

          <div class="layer w-100">
            <button type="submit" class="btn btn-primary create-button" :disabled="savingAttendance">
              {{ savingAttendance ? 'Salvando...' : 'Abrir atendimento' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div :class="canCreateAttendance ? 'col-lg-8' : 'col-12'">
      <div class="bd bgc-white h-100">
        <div class="layers">
          <div class="layer w-100 pX-20 pT-20">
            <div class="d-flex flex-wrap jc-sb ai-c gap-10">
              <h5 class="mB-0">Fila operacional</h5>
              <button type="button" class="btn btn-outline-primary btn-sm" @click="loadView">
                Atualizar
              </button>
            </div>
          </div>

          <div class="layer w-100 pX-20 pT-15">
            <div class="row">
              <div class="col-md-4 mB-15">
                <select v-model="filters.status" class="form-control" @change="loadAttendances">
                  <option value="">Todos os status</option>
                  <option value="open">Aberto</option>
                  <option value="in_progress">Em atendimento</option>
                  <option value="waiting_external">Aguardando externo</option>
                  <option value="resolved">Resolvido</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </div>
              <div class="col-md-4 mB-15">
                <select v-model="filters.priority" class="form-control" @change="loadAttendances">
                  <option value="">Todas as prioridades</option>
                  <option value="critical">Crítica</option>
                  <option value="high">Alta</option>
                  <option value="medium">Média</option>
                  <option value="low">Baixa</option>
                </select>
              </div>
              <div class="col-md-4 mB-15">
                <select v-model="filters.queue_id" class="form-control" @change="loadAttendances">
                  <option value="">Todas as filas</option>
                  <option v-for="queue in queues" :key="queue.id" :value="queue.id">
                    {{ queue.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div class="layer w-100">
            <div v-if="loading" class="empty-state">
              Carregando fila operacional...
            </div>

            <div v-else-if="!hasAttendances" class="empty-state">
              Nenhum atendimento encontrado para os filtros atuais.
            </div>

            <div v-else class="table-responsive">
              <table class="table attendance-table">
                <thead>
                  <tr>
                    <th>Protocolo</th>
                    <th>Título</th>
                    <th>Fila</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="attendance in attendances"
                    :key="attendance.id"
                    class="attendance-row"
                    :class="{ selected: selectedAttendance?.id === attendance.id }"
                    @click="selectAttendance(attendance.id)"
                  >
                    <td class="fw-600">{{ attendance.protocol }}</td>
                    <td>
                      <strong class="d-block">{{ attendance.title }}</strong>
                      <small class="c-grey-600">{{ attendance.origin_label }} • {{ attendance.type_label }}</small>
                    </td>
                    <td>{{ attendance.queue?.name }}</td>
                    <td>{{ attendance.assignee?.name ?? 'Não atribuído' }}</td>
                    <td>
                      <span class="ticket-tag" :class="statusToneMap[attendance.status]">
                        {{ attendance.status_label }}
                      </span>
                    </td>
                    <td>
                      <span class="ticket-tag" :class="priorityToneMap[attendance.priority]">
                        {{ attendance.priority_label }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Filas monitoradas</h5>
          <span class="section-kicker">queues</span>
        </div>

        <div
          v-for="queue in queueCards"
          :key="queue.id"
          class="layers quick-status queue-card p-15 bd mB-15"
        >
          <div class="layer w-100">
            <div class="peers ai-c jc-sb">
              <div class="peer">
                <h6 class="mB-5">{{ queue.name }}</h6>
                <small class="c-grey-600">{{ queue.code }}</small>
              </div>
              <div class="peer ta-r">
                <strong class="d-b">{{ queue.waitingText }}</strong>
                <small class="c-grey-600">{{ queue.description }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Detalhe do atendimento</h5>
          <span v-if="selectedAttendance" class="section-kicker">
            {{ selectedAttendance.protocol }}
          </span>
        </div>

        <div v-if="selectedAttendance" class="detail-stack">
          <div class="detail-card">
            <h4 class="mB-10">{{ selectedAttendance.title }}</h4>
            <p class="mB-15 c-grey-700">{{ selectedAttendance.description }}</p>
            <div class="detail-meta">
              <span class="ticket-tag" :class="statusToneMap[selectedAttendance.status]">
                {{ selectedAttendance.status_label }}
              </span>
              <span class="ticket-tag" :class="priorityToneMap[selectedAttendance.priority]">
                {{ selectedAttendance.priority_label }}
              </span>
              <span class="ticket-tag is-info">{{ selectedAttendance.queue?.name }}</span>
            </div>
            <div class="detail-grid mT-20">
              <div>
                <small class="detail-label">Responsável</small>
                <strong class="d-block">{{ assigneeName }}</strong>
              </div>
              <div>
                <small class="detail-label">Aberto por</small>
                <strong class="d-block">{{ creatorName }}</strong>
              </div>
              <div>
                <small class="detail-label">Abertura</small>
                <strong class="d-block">{{ formatDateTime(selectedAttendance.opened_at) }}</strong>
              </div>
              <div>
                <small class="detail-label">Primeira resposta</small>
                <strong class="d-block">{{ formatDateTime(selectedAttendance.first_response_at) }}</strong>
              </div>
              <div>
                <small class="detail-label">Resolução</small>
                <strong class="d-block">{{ formatDateTime(selectedAttendance.resolved_at) }}</strong>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <div class="row">
              <div class="col-lg-6 mB-20">
                <h6 class="mB-15">Atualizar status</h6>
                <AttendanceStatusForm
                  :can-edit="canUpdateSelectedAttendanceStatus"
                  :errors="actionErrors"
                  :loading="updatingStatus"
                  :options="availableStatusOptions"
                  :permission-message="statusPermissionMessage"
                  :requires-resolution-notes="requiresResolutionNotes"
                  :resolution-notes="statusForm.resolution_notes"
                  :status="statusForm.status"
                  :submit-disabled="updatingStatus || !statusForm.status"
                  @submit="submitStatusUpdate"
                  @update:resolution-notes="statusForm.resolution_notes = $event"
                  @update:status="statusForm.status = $event"
                />
              </div>

              <div class="col-lg-6 mB-20">
                <h6 class="mB-15">Atribuir responsável</h6>
                <AttendanceAssignmentForm
                  :assigned-to="assignmentForm.assigned_to"
                  :can-edit="canManageSelectedAssignment"
                  :errors="actionErrors"
                  :has-users="hasUsers"
                  :loading="updatingAssignment"
                  :permission-message="assignmentPermissionMessage"
                  :submit-disabled="updatingAssignment || !assignmentForm.assigned_to"
                  :users="users"
                  @submit="submitAssignmentUpdate"
                  @update:assigned-to="assignmentForm.assigned_to = $event"
                />
              </div>
            </div>

            <div v-if="actionFeedback" class="action-feedback">
              {{ actionFeedback }}
            </div>
          </div>

          <div class="detail-card">
            <h6 class="mB-15">Linha do tempo</h6>
            <div v-if="detailEvents.length === 0" class="empty-state compact">
              Este atendimento ainda não possui eventos carregados.
            </div>
            <div v-else class="timeline">
              <div v-for="event in detailEvents" :key="event.id" class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                  <strong class="d-block">{{ event.description }}</strong>
                  <small class="c-grey-600">{{ event.type }} • {{ formatDateTime(event.created_at) }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          Selecione um atendimento na fila para visualizar o detalhe.
        </div>
      </div>
    </div>
  </div>
</template>
