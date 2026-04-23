<script setup>
import { onMounted, ref } from 'vue'
import { authState } from '../stores/authSession'
import { fetchAclOverview } from '../services/authService'

const loading = ref(true)
const aclData = ref(null)

const loadAclOverview = async () => {
  loading.value = true

  try {
    aclData.value = await fetchAclOverview()
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadAclOverview()
})
</script>

<template>
  <div class="row gap-20">
    <div class="col-12">
      <div class="bd bgc-white p-20">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">ACL inicial</h5>
          <span class="section-kicker">governança</span>
        </div>
        <p class="mB-0 c-grey-700">
          Papéis e permissões iniciais usados para controlar visibilidade de rotas e ações do tenant atual.
        </p>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Sessão atual</h5>
          <span class="section-kicker">{{ authState.user?.role_context?.key }}</span>
        </div>

        <div v-if="loading" class="empty-state compact">
          Carregando matriz de ACL...
        </div>

        <div v-else-if="aclData" class="detail-stack">
          <div class="detail-card">
            <small class="detail-label">Usuário</small>
            <strong class="d-block">{{ aclData.current_user.name }}</strong>
            <small class="c-grey-600">{{ aclData.current_user.tenant?.name }}</small>
          </div>

          <div class="detail-card">
            <small class="detail-label">Papel</small>
            <strong class="d-block">{{ aclData.current_user.role.label }}</strong>
            <p class="mB-0 c-grey-700">{{ aclData.current_user.role.description }}</p>
          </div>

          <div class="detail-card">
            <small class="detail-label">Permissões ativas</small>
            <div class="permission-list mT-15">
              <span
                v-for="permission in aclData.current_user.permissions"
                :key="permission.key"
                class="ticket-tag is-info permission-chip"
              >
                {{ permission.label }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Matriz de papéis</h5>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="loadAclOverview">
            Atualizar
          </button>
        </div>

        <div v-if="loading" class="empty-state compact">
          Carregando papéis e permissões...
        </div>

        <div v-else-if="aclData" class="row">
          <div
            v-for="role in aclData.roles"
            :key="role.key"
            class="col-xl-6 mB-20"
          >
            <div class="acl-role-card bd p-20 h-100">
              <div class="d-flex jc-sb ai-c mB-15">
                <div>
                  <h6 class="mB-5">{{ role.label }}</h6>
                  <small class="c-grey-600">{{ role.key }}</small>
                </div>
                <span class="section-kicker">{{ role.permissions.length }} permissões</span>
              </div>

              <p class="c-grey-700">{{ role.description }}</p>

              <div class="permission-list">
                <span
                  v-for="permission in role.permissions"
                  :key="permission.key"
                  class="ticket-tag is-ok permission-chip"
                >
                  {{ permission.label }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
