import { resetAppShellState } from './appShell'
import { resetGovernanceState } from './governanceContext'
import { resetOperationalQueueState } from './operationalQueueContext'

export const resetSessionBoundState = () => {
  resetAppShellState()
  resetGovernanceState()
  resetOperationalQueueState()
}
