<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AttendanceAssignmentForm from '../components/attendances/AttendanceAssignmentForm.vue'
import AttendanceStatusForm from '../components/attendances/AttendanceStatusForm.vue'
import { useAttendancePermissions } from '../composables/useAttendancePermissions'
import {
  assignAttendance,
  fetchAssignableUsers,
  fetchAttendance,
  fetchAttendances,
  fetchQueues,
  updateAttendanceStatus,
} from '../services/attendanceService'

const loading = ref(true)
const refreshing = ref(false)
const updatingStatus = ref(false)
const updatingAssignment = ref(false)
const queues = ref([])
const users = ref([])
const attendances = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
})
const selectedAttendance = ref(null)
const actionErrors = ref({})
const actionFeedback = ref('')

const filters = reactive({
  status: '',
  priority: '',
  queue_id: '',
  page: 1,
})

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
  queues.value = await fetchQueues()
}

const loadUsers = async () => {
  if (!canAssignAttendance.value) {
    users.value = []
    return
  }

  users.value = await fetchAssignableUsers()
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

  selectedAttendance.value = await fetchAttendance(targetId)
}

const loadAttendancesPage = async () => {
  const response = await fetchAttendances(activeFilterPayload())

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
  actionErrors.value = {}
  actionFeedback.value = ''
  selectedAttendance.value = await fetchAttendance(attendanceId)
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
    await loadAttendancesPage()
    actionFeedback.value = 'Status atualizado com sucesso.'
  } catch (error) {
    actionErrors.value = error.response?.data?.errors ?? {}

    if (error.response?.data?.message) {
      actionFeedback.value = error.response.data.message
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
  actionErrors.value = {}
  actionFeedback.value = ''

  try {
    await assignAttendance(selectedAttendance.value.id, {
      assigned_to: Number(assignmentForm.assigned_to),
    })
    await loadAttendancesPage()
    actionFeedback.value = 'Responsável atualizado com sucesso.'
  } catch (error) {
    actionErrors.value = error.response?.data?.errors ?? {}

    if (error.response?.data?.message) {
      actionFeedback.value = error.response.data.message
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
</script>

<template>
  <div class="row gap-20">
    <div class="col-12">
      <div class="bd bgc-white p-20">
        <div class="d-flex flex-wrap jc-sb ai-c gap-10">
          <div>
            <h5 class="mB-5">Atendimentos</h5>
            <p class="mB-0 c-grey-700">
              Visão dedicada para consulta, priorização e condução do fluxo operacional com ACL aplicada por atendimento.
            </p>
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" :disabled="refreshing" @click="refreshView">
            {{ refreshing ? 'Atualizando...' : 'Atualizar visão' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-for="card in summaryCards"
      :key="card.title"
      class="col-md-6 col-xl"
    >
      <div class="layers bd bgc-white p-20 metric-card h-100 compact-card">
        <div class="layer w-100">
          <h6>{{ card.title }}</h6>
          <h2>{{ card.value }}</h2>
          <small class="c-grey-600">{{ card.note }}</small>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="bd bgc-white h-100">
        <div class="layers">
          <div class="layer w-100 p-20">
            <div class="attendance-toolbar">
              <select v-model="filters.status" class="form-control" @change="applyFilters">
                <option value="">Todos os status</option>
                <option value="open">Aberto</option>
                <option value="in_progress">Em atendimento</option>
                <option value="waiting_external">Aguardando externo</option>
                <option value="resolved">Resolvido</option>
                <option value="cancelled">Cancelado</option>
              </select>

              <select v-model="filters.priority" class="form-control" @change="applyFilters">
                <option value="">Todas as prioridades</option>
                <option value="critical">Crítica</option>
                <option value="high">Alta</option>
                <option value="medium">Média</option>
                <option value="low">Baixa</option>
              </select>

              <select v-model="filters.queue_id" class="form-control" @change="applyFilters">
                <option value="">Todas as filas</option>
                <option v-for="queue in queues" :key="queue.id" :value="queue.id">
                  {{ queue.name }}
                </option>
              </select>

              <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                Limpar filtros
              </button>
            </div>
          </div>

          <div class="layer w-100 pX-20 pB-15">
            <div class="attendance-summary-line">
              <span>{{ pagination.total }} atendimentos encontrados</span>
              <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
            </div>
          </div>

          <div class="layer w-100">
            <div v-if="loading" class="empty-state">
              Carregando atendimentos...
            </div>

            <div v-else-if="!hasAttendances" class="empty-state">
              Nenhum atendimento encontrado para os filtros atuais.
            </div>

            <div v-else class="table-responsive">
              <table class="table attendance-table attendance-dedicated-table">
                <thead>
                  <tr>
                    <th>Protocolo</th>
                    <th>Fila</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Responsável</th>
                    <th>Abertura</th>
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
                    <td>
                      <strong class="d-block">{{ attendance.protocol }}</strong>
                      <small class="c-grey-600">{{ attendance.title }}</small>
                    </td>
                    <td>{{ attendance.queue?.name }}</td>
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
                    <td>{{ attendance.assignee?.name ?? 'Não atribuído' }}</td>
                    <td>{{ formatDateTime(attendance.opened_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="hasAttendances" class="layer w-100 p-20">
            <div class="attendance-pagination">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                :disabled="pagination.current_page <= 1"
                @click="goToPage(pagination.current_page - 1)"
              >
                Página anterior
              </button>

              <span class="pagination-chip">
                {{ pagination.from || 0 }}-{{ pagination.to || 0 }} de {{ pagination.total }}
              </span>

              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                :disabled="pagination.current_page >= pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
              >
                Próxima página
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Detalhe do atendimento</h5>
          <span v-if="selectedAttendance" class="section-kicker">{{ selectedAttendance.protocol }}</span>
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
                <strong class="d-block">{{ selectedAttendance.assignee?.name ?? 'Não atribuído' }}</strong>
              </div>
              <div>
                <small class="detail-label">Origem</small>
                <strong class="d-block">{{ selectedAttendance.origin_label }}</strong>
              </div>
              <div>
                <small class="detail-label">Tipo</small>
                <strong class="d-block">{{ selectedAttendance.type_label }}</strong>
              </div>
              <div>
                <small class="detail-label">Abertura</small>
                <strong class="d-block">{{ formatDateTime(selectedAttendance.opened_at) }}</strong>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <div class="row">
              <div class="col-lg-6 mB-20">
                <h6 class="mB-15">Fluxo do atendimento</h6>

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
                <h6 class="mB-15">Responsabilidade</h6>

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
          Selecione um atendimento para abrir o detalhe, validar contexto e aplicar ações permitidas pela ACL.
        </div>
      </div>
    </div>
  </div>
</template>
