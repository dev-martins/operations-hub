export const attendancePriorityToneMap = {
  critical: 'is-critical',
  high: 'is-warning',
  medium: 'is-info',
  low: 'is-ok',
}

export const attendanceStatusToneMap = {
  open: 'is-critical',
  in_progress: 'is-warning',
  waiting_external: 'is-info',
  resolved: 'is-ok',
  cancelled: 'is-muted',
}

export const attendanceStatusFilterOptions = [
  { value: 'open', label: 'Aberto' },
  { value: 'in_progress', label: 'Em atendimento' },
  { value: 'waiting_external', label: 'Aguardando externo' },
  { value: 'resolved', label: 'Resolvido' },
  { value: 'cancelled', label: 'Cancelado' },
]

export const attendancePriorityFilterOptions = [
  { value: 'critical', label: 'Crítica' },
  { value: 'high', label: 'Alta' },
  { value: 'medium', label: 'Média' },
  { value: 'low', label: 'Baixa' },
]

export const formatAttendanceDateTime = (value) => {
  if (!value) {
    return 'Sem registro'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value))
}
