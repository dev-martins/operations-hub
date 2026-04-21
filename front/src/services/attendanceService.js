import api from './api'

export const fetchApiStatus = async () => {
  const { data } = await api.get('/v1/status')

  return data
}

export const fetchQueues = async () => {
  const { data } = await api.get('/v1/queues')

  return data.data
}

export const fetchAttendances = async (filters = {}) => {
  const { data } = await api.get('/v1/attendances', {
    params: filters,
  })

  return data
}

export const fetchAttendance = async (attendanceId) => {
  const { data } = await api.get(`/v1/attendances/${attendanceId}`)

  return data.data
}

export const createAttendance = async (payload) => {
  const { data } = await api.post('/v1/attendances', payload)

  return data.data
}
