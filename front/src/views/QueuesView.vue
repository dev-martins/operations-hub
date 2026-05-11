<script setup>
import { computed, onMounted } from 'vue'
import { hasPermission } from '../stores/authSession'
import {
  activeQueuesCount,
  cancelQueueEditing,
  ensureQueuesOverview,
  governanceState,
  loadQueuesOverview,
  queueSubmitLabel,
  startQueueEditing,
  submitQueue,
  totalWaitingCount,
  inactiveQueuesCount,
} from '../stores/governanceContext'

const canManageQueues = computed(() => hasPermission('queues.manage'))

onMounted(async () => {
  await ensureQueuesOverview()
})
</script>

<template>
  <div class="row gap-20">
    <div class="col-12">
      <div class="bd bgc-white p-20">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Filas operacionais</h5>
          <span class="section-kicker">governança</span>
        </div>
        <p class="mB-0 c-grey-700">
          Catálogo administrativo de filas do tenant com leitura operacional, ativação e manutenção do roteamento base.
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="bd bgc-white p-20 h-100">
        <small class="detail-label">Filas ativas</small>
        <h2 class="mT-10 mB-0">{{ activeQueuesCount }}</h2>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bd bgc-white p-20 h-100">
        <small class="detail-label">Filas inativas</small>
        <h2 class="mT-10 mB-0">{{ inactiveQueuesCount }}</h2>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bd bgc-white p-20 h-100">
        <small class="detail-label">Carga operacional</small>
        <h2 class="mT-10 mB-0">{{ totalWaitingCount }}</h2>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Catálogo do tenant</h5>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="loadQueuesOverview">
            Atualizar
          </button>
        </div>

        <div v-if="governanceState.queuesLoading" class="empty-state compact">
          Carregando filas...
        </div>

        <div v-else-if="governanceState.queues.length === 0" class="empty-state compact">
          Nenhuma fila operacional cadastrada para o tenant atual.
        </div>

        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Fila</th>
                <th>Código</th>
                <th>Status</th>
                <th>Em andamento</th>
                <th v-if="canManageQueues">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="queue in governanceState.queues" :key="queue.id">
                <td>
                  <strong class="d-block">{{ queue.name }}</strong>
                  <small class="c-grey-600">{{ queue.description || 'Sem descrição operacional.' }}</small>
                </td>
                <td>{{ queue.code }}</td>
                <td>
                  <span class="ticket-tag" :class="queue.active ? 'is-ok' : 'is-muted'">
                    {{ queue.active ? 'Ativa' : 'Inativa' }}
                  </span>
                </td>
                <td>{{ queue.waiting_count ?? 0 }}</td>
                <td v-if="canManageQueues">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    data-testid="queue-edit-button"
                    @click="startQueueEditing(queue)"
                  >
                    Editar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Governança da fila</h5>
          <span class="section-kicker">{{ governanceState.editingQueueId ? 'edição' : 'cadastro' }}</span>
        </div>

        <div v-if="!canManageQueues" class="empty-state compact permission-state" data-testid="queues-management-blocked">
          A governança de filas fica disponível apenas para administração operacional.
        </div>

        <form v-else @submit.prevent="submitQueue">
          <div class="mB-15">
            <label class="form-label">Nome</label>
            <input v-model="governanceState.queueForm.name" type="text" class="form-control" data-testid="queue-name-input">
            <small v-if="governanceState.queueErrors.name" class="field-error">{{ governanceState.queueErrors.name[0] }}</small>
          </div>

          <div class="mB-15">
            <label class="form-label">Código</label>
            <input v-model="governanceState.queueForm.code" type="text" class="form-control" data-testid="queue-code-input">
            <small v-if="governanceState.queueErrors.code" class="field-error">{{ governanceState.queueErrors.code[0] }}</small>
          </div>

          <div class="mB-15">
            <label class="form-label">Descrição</label>
            <textarea v-model="governanceState.queueForm.description" rows="4" class="form-control" data-testid="queue-description-input"></textarea>
          </div>

          <div class="form-check mB-20">
            <input id="queue-active" v-model="governanceState.queueForm.active" class="form-check-input" type="checkbox">
            <label class="form-check-label" for="queue-active">
              Fila ativa para novas entradas operacionais
            </label>
          </div>

          <div class="d-flex gap-10">
            <button type="submit" class="btn btn-primary btn-sm" :disabled="governanceState.queuesSaving" data-testid="queue-submit">
              {{ governanceState.queuesSaving ? 'Salvando...' : queueSubmitLabel }}
            </button>
            <button
              v-if="governanceState.editingQueueId"
              type="button"
              class="btn btn-outline-secondary btn-sm"
              data-testid="queue-cancel-edit"
              @click="cancelQueueEditing"
            >
              Cancelar edição
            </button>
          </div>

          <div v-if="governanceState.queueFeedback" class="action-feedback mT-20">
            {{ governanceState.queueFeedback }}
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
