<script setup>
defineProps({
  canEdit: {
    type: Boolean,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  options: {
    type: Array,
    default: () => [],
  },
  permissionMessage: {
    type: String,
    default: '',
  },
  requiresResolutionNotes: {
    type: Boolean,
    default: false,
  },
  resolutionNotes: {
    type: String,
    default: '',
  },
  status: {
    type: String,
    default: '',
  },
  submitDisabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'submit',
  'update:resolutionNotes',
  'update:status',
])
</script>

<template>
  <template v-if="canEdit">
    <div class="mB-15">
      <label class="form-label">Novo status</label>
      <select
        :value="status"
        class="form-control"
        data-testid="status-select"
        @change="emit('update:status', $event.target.value)"
      >
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <small v-if="errors.status" class="field-error">{{ errors.status[0] }}</small>
    </div>

    <div v-if="requiresResolutionNotes" class="mB-15">
      <label class="form-label">Notas de resolução</label>
      <textarea
        :value="resolutionNotes"
        class="form-control"
        rows="4"
        placeholder="Descreva o que foi feito para encerrar o atendimento."
        data-testid="resolution-notes"
        @input="emit('update:resolutionNotes', $event.target.value)"
      />
      <small v-if="errors.resolution_notes" class="field-error">
        {{ errors.resolution_notes[0] }}
      </small>
    </div>

    <button
      type="button"
      class="btn btn-primary btn-sm"
      :disabled="submitDisabled"
      data-testid="status-submit"
      @click="emit('submit')"
    >
      {{ loading ? 'Salvando...' : 'Salvar status' }}
    </button>
  </template>

  <div v-else class="empty-state compact permission-state" data-testid="status-blocked-state">
    {{ permissionMessage }}
  </div>
</template>
