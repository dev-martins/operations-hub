<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { login } from '../stores/authSession'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const formError = ref('')
const validationErrors = ref({})
const form = reactive({
  email: 'test@example.com',
  password: 'password',
})

const submit = async () => {
  loading.value = true
  formError.value = ''
  validationErrors.value = {}

  try {
    await login(form)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null

    if (redirect) {
      await router.push(redirect)
      return
    }

    await router.push({ name: 'operational-queue' })
  } catch (error) {
    formError.value = error.response?.data?.message ?? 'Nao foi possivel autenticar.'
    validationErrors.value = error.response?.data?.errors ?? {}
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="mB-25">
        <small class="section-kicker">passport</small>
        <h1 class="auth-title mT-15">Entrar na operacao</h1>
        <p class="auth-subtitle mB-0">
          Autenticacao inicial com Laravel Passport e contexto de tenant carregado apos o login.
        </p>
      </div>

      <form @submit.prevent="submit">
        <div class="mB-20">
          <label class="form-label">E-mail</label>
          <input v-model="form.email" type="email" class="form-control" autocomplete="username" />
          <small v-if="validationErrors.email" class="field-error">{{ validationErrors.email[0] }}</small>
        </div>

        <div class="mB-20">
          <label class="form-label">Senha</label>
          <input v-model="form.password" type="password" class="form-control" autocomplete="current-password" />
          <small v-if="validationErrors.password" class="field-error">{{ validationErrors.password[0] }}</small>
        </div>

        <div v-if="formError" class="auth-error mB-20">
          {{ formError }}
        </div>

        <button type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </div>
  </div>
</template>
