import { computed, reactive } from 'vue'
import { fetchCurrentUser, loginRequest, logoutRequest } from '../services/authService'
import {
  readAccessToken,
  readStoredAuthUser,
  writeAccessToken,
  writeStoredAuthUser,
} from '../services/authTokenStorage'

const storedUser = readStoredAuthUser()

const state = reactive({
  accessToken: readAccessToken(),
  user: storedUser,
  tenant: storedUser?.tenant ?? null,
  initialized: Boolean(readAccessToken() && storedUser),
  bootstrapping: false,
})

const persistToken = (token) => {
  state.accessToken = token

  writeAccessToken(token)
}

export const clearAuthSession = () => {
  persistToken(null)
  state.user = null
  state.tenant = null
  writeStoredAuthUser(null)
  state.initialized = true
}

export const hydrateAuthUser = (payload) => {
  state.user = payload
  state.tenant = payload?.tenant ?? null
  writeStoredAuthUser(payload)
  state.initialized = true
}

export const initializeAuthSession = async () => {
  if (state.initialized || state.bootstrapping) {
    return
  }

  if (!state.accessToken) {
    state.initialized = true
    return
  }

  state.bootstrapping = true

  try {
    const user = await fetchCurrentUser()
    hydrateAuthUser(user)
  } catch (error) {
    if ([401, 403].includes(error.response?.status ?? 0)) {
      clearAuthSession()
      return
    }

    state.initialized = true
  } finally {
    state.bootstrapping = false
  }
}

export const login = async (credentials) => {
  const session = await loginRequest(credentials)

  persistToken(session.access_token)
  hydrateAuthUser(session.user)

  return session
}

export const logout = async () => {
  try {
    if (state.accessToken) {
      await logoutRequest()
    }
  } finally {
    clearAuthSession()
  }
}

export const authState = state

export const isAuthenticated = computed(() => Boolean(state.accessToken && state.user))

export const authPermissions = computed(() => state.user?.permissions ?? [])

export const hasPermission = (permission) => {
  return authPermissions.value.some(({ key }) => key === permission)
}

export const getAccessToken = () => state.accessToken ?? readAccessToken()
