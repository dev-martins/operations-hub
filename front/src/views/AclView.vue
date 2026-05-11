<script setup>
import { onMounted } from 'vue'
import { authState, hasPermission } from '../stores/authSession'
import {
  ensureAclOverview,
  governanceState,
  loadAclOverview,
  submitRoleUpdate,
} from '../stores/governanceContext'

const canManageAcl = () => hasPermission('acl.manage')

onMounted(async () => {
  await ensureAclOverview()
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
          Papéis, permissões e distribuição do tenant atual para sustentar governança operacional explícita.
        </p>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="bd bgc-white p-20 h-100">
        <div class="d-flex jc-sb ai-c mB-20">
          <h5 class="mB-0">Sessão atual</h5>
          <span class="section-kicker">{{ authState.user?.role_context?.key }}</span>
        </div>

        <div v-if="governanceState.aclLoading" class="empty-state compact">
          Carregando matriz de ACL...
        </div>

        <div v-else-if="governanceState.aclData" class="detail-stack">
          <div class="detail-card">
            <small class="detail-label">Usuário</small>
            <strong class="d-block">{{ governanceState.aclData.current_user.name }}</strong>
            <small class="c-grey-600">{{ governanceState.aclData.current_user.tenant?.name }}</small>
          </div>

          <div class="detail-card">
            <small class="detail-label">Papel</small>
            <strong class="d-block">{{ governanceState.aclData.current_user.role.label }}</strong>
            <p class="mB-0 c-grey-700">{{ governanceState.aclData.current_user.role.description }}</p>
          </div>

          <div class="detail-card">
            <small class="detail-label">Permissões ativas</small>
            <div class="permission-list mT-15">
              <span
                v-for="permission in governanceState.aclData.current_user.permissions"
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

        <div v-if="governanceState.aclLoading" class="empty-state compact">
          Carregando papéis e permissões...
        </div>

        <div v-else-if="governanceState.aclData" class="row">
          <div
            v-for="role in governanceState.aclData.roles"
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

    <div class="col-12">
      <div class="bd bgc-white p-20">
        <div class="d-flex jc-sb ai-c mB-20">
          <div>
            <h5 class="mB-5">Governança do tenant</h5>
            <p class="mB-0 c-grey-700">
              Usuários, papéis e distribuição operacional para o tenant autenticado.
            </p>
          </div>
          <span class="section-kicker">{{ governanceState.aclData?.tenant_users?.length ?? 0 }} usuários</span>
        </div>

        <div v-if="governanceState.aclActionFeedback" class="action-feedback mB-20">
          {{ governanceState.aclActionFeedback }}
        </div>

        <div v-if="governanceState.aclLoading" class="empty-state compact">
          Carregando governança do tenant...
        </div>

        <div v-else-if="governanceState.aclData" class="row">
          <div class="col-lg-4 mB-20">
            <div class="detail-card h-100">
              <h6 class="mB-15">Resumo por papel</h6>
              <div class="detail-stack">
                <div
                  v-for="role in governanceState.aclData.role_summary"
                  :key="role.key"
                  class="d-flex jc-sb ai-c"
                >
                  <div>
                    <strong class="d-block">{{ role.label }}</strong>
                    <small class="c-grey-600">{{ role.description }}</small>
                  </div>
                  <span class="ticket-tag is-info">{{ role.users_count }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div v-if="governanceState.aclData.tenant_users.length === 0" class="empty-state compact">
              Nenhum usuário carregado para o tenant atual.
            </div>

            <div v-else class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Usuário</th>
                    <th>Papel atual</th>
                    <th>Permissões</th>
                    <th>Ação</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in governanceState.aclData.tenant_users" :key="user.id">
                    <td>
                      <strong class="d-block">{{ user.name }}</strong>
                      <small class="c-grey-600">{{ user.email }}</small>
                    </td>
                    <td>
                      <span class="ticket-tag is-ok">{{ user.role_context.label }}</span>
                    </td>
                    <td>
                      <small class="c-grey-600">{{ user.permissions.length }} permissões ativas</small>
                    </td>
                    <td>
                      <template v-if="canManageAcl()">
                        <select
                          class="form-control"
                          data-testid="acl-role-select"
                          :disabled="governanceState.updatingUserId === user.id"
                          :value="user.role"
                          @change="submitRoleUpdate(user.id, $event.target.value)"
                        >
                          <option
                            v-for="role in governanceState.aclData.manageable_roles"
                            :key="role.key"
                            :value="role.key"
                          >
                            {{ role.label }}
                          </option>
                        </select>
                        <small v-if="governanceState.aclActionErrors.role" class="field-error">{{ governanceState.aclActionErrors.role[0] }}</small>
                      </template>
                      <small v-else class="c-grey-600">
                        Somente administração operacional pode alterar papéis.
                      </small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
