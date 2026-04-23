<script setup>
import {
  attendancePriorityToneMap,
  attendanceStatusToneMap,
  formatAttendanceDateTime,
} from '../../modules/attendances/constants/attendancePresentation'

defineProps({
  attendances: {
    type: Array,
    default: () => [],
  },
  hasAttendances: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    required: true,
  },
  selectedAttendanceId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits([
  'go-to-page',
  'select',
])
</script>

<template>
  <div class="layers">
    <div class="layer w-100 p-20">
      <div class="attendance-summary-line">
        <span>{{ pagination.total }} atendimentos encontrados</span>
        <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
      </div>
    </div>

    <div class="layer w-100">
      <div v-if="loading" class="empty-state" data-testid="attendance-list-loading">
        Carregando atendimentos...
      </div>

      <div v-else-if="!hasAttendances" class="empty-state" data-testid="attendance-list-empty">
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
              :class="{ selected: selectedAttendanceId === attendance.id }"
              @click="emit('select', attendance.id)"
            >
              <td>
                <strong class="d-block">{{ attendance.protocol }}</strong>
                <small class="c-grey-600">{{ attendance.title }}</small>
              </td>
              <td>{{ attendance.queue?.name }}</td>
              <td>
                <span class="ticket-tag" :class="attendanceStatusToneMap[attendance.status]">
                  {{ attendance.status_label }}
                </span>
              </td>
              <td>
                <span class="ticket-tag" :class="attendancePriorityToneMap[attendance.priority]">
                  {{ attendance.priority_label }}
                </span>
              </td>
              <td>{{ attendance.assignee?.name ?? 'Não atribuído' }}</td>
              <td>{{ formatAttendanceDateTime(attendance.opened_at) }}</td>
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
          data-testid="attendance-page-prev"
          @click="emit('go-to-page', pagination.current_page - 1)"
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
          data-testid="attendance-page-next"
          @click="emit('go-to-page', pagination.current_page + 1)"
        >
          Próxima página
        </button>
      </div>
    </div>
  </div>
</template>
