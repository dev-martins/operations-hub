<script setup>
import AttendanceDetailPanel from '../components/attendances/AttendanceDetailPanel.vue'
import AttendanceFilters from '../components/attendances/AttendanceFilters.vue'
import AttendanceListTable from '../components/attendances/AttendanceListTable.vue'
import AttendanceSummaryCards from '../components/attendances/AttendanceSummaryCards.vue'
import { useAttendancesPage } from '../modules/attendances/composables/useAttendancesPage'

const {
  actionErrors,
  actionFeedback,
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
} = useAttendancesPage()
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

    <AttendanceSummaryCards :cards="summaryCards" />

    <div class="col-lg-7">
      <div class="bd bgc-white h-100">
        <AttendanceFilters :filters="filters" :queues="queues" @apply="applyFilters" @reset="resetFilters" />
        <AttendanceListTable
          :attendances="attendances"
          :has-attendances="hasAttendances"
          :loading="loading"
          :pagination="pagination"
          :selected-attendance-id="selectedAttendance?.id ?? null"
          @go-to-page="goToPage"
          @select="selectAttendance"
        />
      </div>
    </div>

    <div class="col-lg-5">
      <AttendanceDetailPanel
        :action-errors="actionErrors"
        :action-feedback="actionFeedback"
        :assignment-form="assignmentForm"
        :assignment-permission-message="assignmentPermissionMessage"
        :available-status-options="availableStatusOptions"
        :can-manage-selected-assignment="canManageSelectedAssignment"
        :can-update-selected-attendance-status="canUpdateSelectedAttendanceStatus"
        :detail-events="detailEvents"
        :has-users="hasUsers"
        :requires-resolution-notes="requiresResolutionNotes"
        :selected-attendance="selectedAttendance"
        :status-form="statusForm"
        :status-permission-message="statusPermissionMessage"
        :updating-assignment="updatingAssignment"
        :updating-status="updatingStatus"
        :users="users"
        @submit-assignment="submitAssignmentUpdate"
        @submit-status="submitStatusUpdate"
        @update:assigned-to="assignmentForm.assigned_to = $event"
        @update:resolution-notes="statusForm.resolution_notes = $event"
        @update:status="statusForm.status = $event"
      />
    </div>
  </div>
</template>
