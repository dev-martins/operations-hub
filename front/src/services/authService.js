import api from './api'

export const loginRequest = async (payload) => {
  const { data } = await api.post('/v1/auth/login', payload)

  return data.data
}

export const fetchCurrentUser = async () => {
  const { data } = await api.get('/v1/auth/me')

  return data.data
}

export const fetchAclOverview = async () => {
  const { data } = await api.get('/v1/auth/acl')

  return data.data
}

export const logoutRequest = async () => {
  const { data } = await api.post('/v1/auth/logout')

  return data
}
