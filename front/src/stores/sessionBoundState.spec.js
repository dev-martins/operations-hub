import { describe, expect, it, vi } from 'vitest'
import { resetSessionBoundState } from './sessionBoundState'

const appShellMocks = vi.hoisted(() => ({
  resetAppShellState: vi.fn(),
}))

const governanceMocks = vi.hoisted(() => ({
  resetGovernanceState: vi.fn(),
}))

const operationalQueueMocks = vi.hoisted(() => ({
  resetOperationalQueueState: vi.fn(),
}))

vi.mock('./appShell', () => appShellMocks)
vi.mock('./governanceContext', () => governanceMocks)
vi.mock('./operationalQueueContext', () => operationalQueueMocks)

describe('sessionBoundState', () => {
  it('reseta shell, governança e fila operacional de forma coordenada', () => {
    resetSessionBoundState()

    expect(appShellMocks.resetAppShellState).toHaveBeenCalledTimes(1)
    expect(governanceMocks.resetGovernanceState).toHaveBeenCalledTimes(1)
    expect(operationalQueueMocks.resetOperationalQueueState).toHaveBeenCalledTimes(1)
  })
})
