<script setup>
import {
  attendancePriorityFilterOptions,
  attendanceStatusFilterOptions,
} from '../../modules/attendances/constants/attendancePresentation'

defineProps({
  filters: {
    type: Object,
    required: true,
  },
  queues: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'apply',
  'reset',
])
</script>

<template>
  <div class="attendance-toolbar">
    <select
      v-model="filters.status"
      class="form-control"
      data-testid="attendance-filter-status"
      @change="emit('apply')"
    >
      <option value="">Todos os status</option>
      <option
        v-for="option in attendanceStatusFilterOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>

    <select
      v-model="filters.priority"
      class="form-control"
      data-testid="attendance-filter-priority"
      @change="emit('apply')"
    >
      <option value="">Todas as prioridades</option>
      <option
        v-for="option in attendancePriorityFilterOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>

    <select
      v-model="filters.queue_id"
      class="form-control"
      data-testid="attendance-filter-queue"
      @change="emit('apply')"
    >
      <option value="">Todas as filas</option>
      <option v-for="queue in queues" :key="queue.id" :value="queue.id">
        {{ queue.name }}
      </option>
    </select>

    <button
      type="button"
      class="btn btn-outline-secondary btn-sm"
      data-testid="attendance-filter-reset"
      @click="emit('reset')"
    >
      Limpar filtros
    </button>
  </div>
</template>
