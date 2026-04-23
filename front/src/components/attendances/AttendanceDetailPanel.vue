<script setup>
import AttendanceAssignmentForm from './AttendanceAssignmentForm.vue'
import AttendanceStatusForm from './AttendanceStatusForm.vue'
import {
  attendancePriorityToneMap,
  attendanceStatusToneMap,
  formatAttendanceDateTime,
} from '../../modules/attendances/constants/attendancePresentation'

defineProps({
  actionErrors: {
    type: Object,
    default: () => ({}),
  },
  actionFeedback: {
    type: String,
    default: '',
  },
  assignmentForm: {
    type: Object,
    required: true,
  },
  assignmentPermissionMessage: {
    type: String,
    default: '',
  },
  availableStatusOptions: {
    type: Array,
    default: () => [],
  },
  canManageSelectedAssignment: {
    type: Boolean,
    required: true,
  },
  canUpdateSelectedAttendanceStatus: {
    type: Boolean,
    required: true,
  },
  detailEvents: {
    type: Array,
    default: () => [],
  },
  hasUsers: {
    type: Boolean,
    default: false,
  },
  requiresResolutionNotes: {
    type: Boolean,
    default: false,
  },
  selectedAttendance: {
    type: Object,
    default: null,
  },
  statusForm: {
    type: Object,
    required: true,
  },
  statusPermissionMessage: {
    type: String,
    default: '',
  },
  updatingAssignment: {
    type: Boolean,
    default: false,
  },
  updatingStatus: {
    type: Boolean,
    default: false,
  },
  users: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'submit-assignment',
  'submit-status',
  'update:assigned-to',
  'update:resolution-notes',
  'update:status',
])
</script>

<template>
  <div class="bd bgc-white p-20 h-100">
    <div class="d-flex jc-sb ai-c mB-20">
      <h5 class="mB-0">Detalhe do atendimento</h5>
      <span v-if="selectedAttendance" class="section-kicker">{{ selectedAttendance.protocol }}</span>
    </div>

    <div v-if="selectedAttendance" class="detail-stack" data-testid="attendance-detail-panel">
      <div class="detail-card">
        <h4 class="mB-10">{{ selectedAttendance.title }}</h4>
        <p class="mB-15 c-grey-700">{{ selectedAttendance.description }}</p>

        <div class="detail-meta">
          <span class="ticket-tag" :class="attendanceStatusToneMap[selectedAttendance.status]">
            {{ selectedAttendance.status_label }}
          </span>
          <span class="ticket-tag" :class="attendancePriorityToneMap[selectedAttendance.priority]">
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
            <small class="detail-label">Aberto por</small>
            <strong class="d-block">{{ selectedAttendance.creator?.name ?? 'Não identificado' }}</strong>
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
            <strong class="d-block">{{ formatAttendanceDateTime(selectedAttendance.opened_at) }}</strong>
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
              @submit="emit('submit-status')"
              @update:resolution-notes="emit('update:resolution-notes', $event)"
              @update:status="emit('update:status', $event)"
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
              @submit="emit('submit-assignment')"
              @update:assigned-to="emit('update:assigned-to', $event)"
            />
          </div>
        </div>

        <div v-if="actionFeedback" class="action-feedback">
          {{ actionFeedback }}
        </div>
      </div>

      <div class="detail-card">
        <h6 class="mB-15">Linha do tempo</h6>

        <div v-if="detailEvents.length === 0" class="empty-state compact" data-testid="attendance-timeline-empty">
          Este atendimento ainda não possui eventos carregados.
        </div>

        <div v-else class="timeline" data-testid="attendance-timeline">
          <div v-for="event in detailEvents" :key="event.id" class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
              <strong class="d-block">{{ event.description }}</strong>
              <small class="c-grey-600">{{ event.type }} • {{ formatAttendanceDateTime(event.created_at) }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-state" data-testid="attendance-detail-empty">
      Selecione um atendimento para abrir o detalhe, validar contexto e aplicar ações permitidas pela ACL.
    </div>
  </div>
</template>
