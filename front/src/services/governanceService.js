import api from './api'

export const fetchQueuesOverview = async () => {
  const { data } = await api.get('/v1/queues')

  return data.data
}

export const createQueue = async (payload) => {
  const { data } = await api.post('/v1/queues', payload)

  return data.data
}

export const updateQueue = async (queueId, payload) => {
  const { data } = await api.patch(`/v1/queues/${queueId}`, payload)

  return data.data
}

export const updateUserRole = async (userId, payload) => {
  const { data } = await api.patch(`/v1/users/${userId}/role`, payload)

  return data.data
}
