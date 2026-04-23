<script setup>
defineProps({
  assignedTo: {
    type: [Number, String],
    default: '',
  },
  canEdit: {
    type: Boolean,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  hasUsers: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  permissionMessage: {
    type: String,
    default: '',
  },
  submitDisabled: {
    type: Boolean,
    default: false,
  },
  users: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'submit',
  'update:assignedTo',
])
</script>

<template>
  <template v-if="canEdit">
    <div class="mB-15">
      <label class="form-label">Operador</label>
      <select
        :value="assignedTo"
        class="form-control"
        :disabled="!hasUsers"
        data-testid="assignment-select"
        @change="emit('update:assignedTo', $event.target.value)"
      >
        <option value="">
          {{ hasUsers ? 'Selecione um operador' : 'Nenhum operador disponível' }}
        </option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.name }} • {{ user.email }}
        </option>
      </select>
      <small v-if="errors.assigned_to" class="field-error">{{ errors.assigned_to[0] }}</small>
    </div>

    <button
      type="button"
      class="btn btn-outline-primary btn-sm"
      :disabled="submitDisabled"
      data-testid="assignment-submit"
      @click="emit('submit')"
    >
      {{ loading ? 'Atribuindo...' : 'Salvar responsável' }}
    </button>
  </template>

  <div v-else class="empty-state compact permission-state" data-testid="assignment-blocked-state">
    {{ permissionMessage }}
  </div>
</template>
