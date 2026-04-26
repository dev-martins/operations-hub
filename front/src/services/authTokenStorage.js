const ACCESS_TOKEN_KEY = 'operations_hub_access_token'
const AUTH_USER_KEY = 'operations_hub_auth_user'

export const readAccessToken = () => window.localStorage.getItem(ACCESS_TOKEN_KEY)

export const writeAccessToken = (token) => {
  if (token) {
    window.localStorage.setItem(ACCESS_TOKEN_KEY, token)
    return
  }

  window.localStorage.removeItem(ACCESS_TOKEN_KEY)
}

export const readStoredAuthUser = () => {
  const value = window.localStorage.getItem(AUTH_USER_KEY)

  if (!value) {
    return null
  }

  try {
    return JSON.parse(value)
  } catch {
    window.localStorage.removeItem(AUTH_USER_KEY)

    return null
  }
}

export const writeStoredAuthUser = (user) => {
  if (user) {
    window.localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user))
    return
  }

  window.localStorage.removeItem(AUTH_USER_KEY)
}
