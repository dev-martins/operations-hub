<script setup>
import { computed, onMounted } from 'vue'
import AttendanceAssignmentForm from '../components/attendances/AttendanceAssignmentForm.vue'
import AttendanceStatusForm from '../components/attendances/AttendanceStatusForm.vue'
import { useAttendancePermissions } from '../composables/useAttendancePermissions'
import {
  assigneeName,
  canAssignAttendance,
  canCreateAttendance,
  creatorName,
  detailEvents,
  ensureOperationalView,
  hasAttendances,
  hasUsers,
  loadOperationalAttendances,
  loadOperationalView,
  operationalMetrics,
  operationalQueueState,
  queueCards,
  refreshOperationalPanels,
  requiresResolutionNotes,
  selectOperationalAttendance,
  submitOperationalAssignmentUpdate,
  submitOperationalAttendance,
  submitOperationalStatusUpdate,
} from '../stores/operationalQueueContext'

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

const {
  assignmentPermissionMessage,
  availableStatusOptions,
  canManageSelectedAssignment,
  canUpdateSelectedAttendanceStatus,
  statusPermissionMessage,
} = useAttendancePermissions(computed(() => operationalQueueState.selectedAttendance))

const formatDateTime = (value) => {
  if (!value) {
    return 'Sem registro'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value))
}

onMounted(async () => {
  await ensureOperationalView()
})
</script>

<template>
  <div class="row gap-20">
    <div
      v-for="metric in operationalMetrics"
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

        <form class="layers gap-10" @submit.prevent="submitOperationalAttendance">
          <div class="layer w-100">
            <label class="form-label">Título</label>
            <input v-model="operationalQueueState.createForm.title" class="form-control" type="text" placeholder="Ex.: webhook sem retorno" />
            <small v-if="operationalQueueState.createFormErrors.title" class="field-error">{{ operationalQueueState.createFormErrors.title[0] }}</small>
          </div>

          <div class="layer w-100">
            <label class="form-label">Descrição</label>
            <textarea v-model="operationalQueueState.createForm.description" class="form-control" rows="4" placeholder="Descreva o contexto operacional da ocorrência." />
            <small v-if="operationalQueueState.createFormErrors.description" class="field-error">{{ operationalQueueState.createFormErrors.description[0] }}</small>
          </div>

          <div class="layer w-100">
            <div class="row">
              <div class="col-md-6 mB-15">
                <label class="form-label">Tipo</label>
                <select v-model="operationalQueueState.createForm.type" class="form-control">
                  <option value="incident">Incidente</option>
                  <option value="request">Solicitação</option>
                  <option value="integration">Integração</option>
                  <option value="financial">Financeiro</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Origem</label>
                <select v-model="operationalQueueState.createForm.origin" class="form-control">
                  <option value="manual">Manual</option>
                  <option value="erp">ERP</option>
                  <option value="pdv">PDV</option>
                  <option value="portal">Portal</option>
                  <option value="api">API</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Prioridade</label>
                <select v-model="operationalQueueState.createForm.priority" class="form-control">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                  <option value="critical">Crítica</option>
                </select>
              </div>
              <div class="col-md-6 mB-15">
                <label class="form-label">Fila</label>
                <select v-model="operationalQueueState.createForm.queue_id" class="form-control">
                  <option v-for="queue in operationalQueueState.queues" :key="queue.id" :value="queue.id">
                    {{ queue.name }}
                  </option>
                </select>
                <small v-if="operationalQueueState.createFormErrors.queue_id" class="field-error">{{ operationalQueueState.createFormErrors.queue_id[0] }}</small>
              </div>
            </div>
          </div>

          <div class="layer w-100">
            <button type="submit" class="btn btn-primary create-button" :disabled="operationalQueueState.savingAttendance">
              {{ operationalQueueState.savingAttendance ? 'Salvando...' : 'Abrir atendimento' }}
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
              <button type="button" class="btn btn-outline-primary btn-sm" @click="loadOperationalView">
                Atualizar
              </button>
            </div>
          </div>

          <div class="layer w-100 pX-20 pT-15">
            <div class="row">
              <div class="col-md-4 mB-15">
                <select v-model="operationalQueueState.filters.status" class="form-control" @change="loadOperationalAttendances">
                  <option value="">Todos os status</option>
                  <option value="open">Aberto</option>
                  <option value="in_progress">Em atendimento</option>
                  <option value="waiting_external">Aguardando externo</option>
                  <option value="resolved">Resolvido</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </div>
              <div class="col-md-4 mB-15">
                <select v-model="operationalQueueState.filters.priority" class="form-control" @change="loadOperationalAttendances">
                  <option value="">Todas as prioridades</option>
                  <option value="critical">Crítica</option>
                  <option value="high">Alta</option>
                  <option value="medium">Média</option>
                  <option value="low">Baixa</option>
                </select>
              </div>
              <div class="col-md-4 mB-15">
                <select v-model="operationalQueueState.filters.queue_id" class="form-control" @change="loadOperationalAttendances">
                  <option value="">Todas as filas</option>
                  <option v-for="queue in operationalQueueState.queues" :key="queue.id" :value="queue.id">
                    {{ queue.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div class="layer w-100">
            <div v-if="operationalQueueState.loading" class="empty-state">
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
                    v-for="attendance in operationalQueueState.attendances"
                    :key="attendance.id"
                    class="attendance-row"
                    :class="{ selected: operationalQueueState.selectedAttendance?.id === attendance.id }"
                    @click="selectOperationalAttendance(attendance.id)"
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
          <span v-if="operationalQueueState.selectedAttendance" class="section-kicker">
            {{ operationalQueueState.selectedAttendance.protocol }}
          </span>
        </div>

        <div v-if="operationalQueueState.selectedAttendance" class="detail-stack">
          <div class="detail-card">
            <h4 class="mB-10">{{ operationalQueueState.selectedAttendance.title }}</h4>
            <p class="mB-15 c-grey-700">{{ operationalQueueState.selectedAttendance.description }}</p>
            <div class="detail-meta">
              <span class="ticket-tag" :class="statusToneMap[operationalQueueState.selectedAttendance.status]">
                {{ operationalQueueState.selectedAttendance.status_label }}
              </span>
              <span class="ticket-tag" :class="priorityToneMap[operationalQueueState.selectedAttendance.priority]">
                {{ operationalQueueState.selectedAttendance.priority_label }}
              </span>
              <span class="ticket-tag is-info">{{ operationalQueueState.selectedAttendance.queue?.name }}</span>
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
                <strong class="d-block">{{ formatDateTime(operationalQueueState.selectedAttendance.opened_at) }}</strong>
              </div>
              <div>
                <small class="detail-label">Primeira resposta</small>
                <strong class="d-block">{{ formatDateTime(operationalQueueState.selectedAttendance.first_response_at) }}</strong>
              </div>
              <div>
                <small class="detail-label">Resolução</small>
                <strong class="d-block">{{ formatDateTime(operationalQueueState.selectedAttendance.resolved_at) }}</strong>
              </div>
            </div>
          </div>

          <div class="detail-card">
            <div class="row">
              <div class="col-lg-6 mB-20">
                <h6 class="mB-15">Atualizar status</h6>
                <AttendanceStatusForm
                  :can-edit="canUpdateSelectedAttendanceStatus"
                  :errors="operationalQueueState.actionErrors"
                  :loading="operationalQueueState.updatingStatus"
                  :options="availableStatusOptions"
                  :permission-message="statusPermissionMessage"
                  :requires-resolution-notes="requiresResolutionNotes"
                  :resolution-notes="operationalQueueState.statusForm.resolution_notes"
                  :status="operationalQueueState.statusForm.status"
                  :submit-disabled="operationalQueueState.updatingStatus || !operationalQueueState.statusForm.status"
                  @submit="submitOperationalStatusUpdate"
                  @update:resolution-notes="operationalQueueState.statusForm.resolution_notes = $event"
                  @update:status="operationalQueueState.statusForm.status = $event"
                />
              </div>

              <div class="col-lg-6 mB-20">
                <h6 class="mB-15">Atribuir responsável</h6>
                <AttendanceAssignmentForm
                  :assigned-to="operationalQueueState.assignmentForm.assigned_to"
                  :can-edit="canManageSelectedAssignment"
                  :errors="operationalQueueState.actionErrors"
                  :has-users="hasUsers"
                  :loading="operationalQueueState.updatingAssignment"
                  :permission-message="assignmentPermissionMessage"
                  :submit-disabled="operationalQueueState.updatingAssignment || !operationalQueueState.assignmentForm.assigned_to"
                  :users="operationalQueueState.users"
                  @submit="submitOperationalAssignmentUpdate"
                  @update:assigned-to="operationalQueueState.assignmentForm.assigned_to = $event"
                />
              </div>
            </div>

            <div v-if="operationalQueueState.actionFeedback" class="action-feedback">
              {{ operationalQueueState.actionFeedback }}
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
