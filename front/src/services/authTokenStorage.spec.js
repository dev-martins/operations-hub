import { beforeEach, describe, expect, it } from 'vitest'
import {
  readAccessToken,
  readStoredAuthUser,
  writeAccessToken,
  writeStoredAuthUser,
} from './authTokenStorage'

describe('authTokenStorage', () => {
  beforeEach(() => {
    window.localStorage.clear()
  })

  it('persiste e remove access token do storage', () => {
    writeAccessToken('token-demo')
    expect(readAccessToken()).toBe('token-demo')

    writeAccessToken(null)
    expect(readAccessToken()).toBeNull()
  })

  it('persiste e recupera usuário autenticado', () => {
    writeStoredAuthUser({
      id: 7,
      name: 'Alice',
      tenant: { id: 1, name: 'Tenant Demo' },
    })

    expect(readStoredAuthUser()).toEqual({
      id: 7,
      name: 'Alice',
      tenant: { id: 1, name: 'Tenant Demo' },
    })
  })

  it('remove valor inválido do storage quando o JSON está corrompido', () => {
    window.localStorage.setItem('operations_hub_auth_user', '{invalido')

    expect(readStoredAuthUser()).toBeNull()
    expect(window.localStorage.getItem('operations_hub_auth_user')).toBeNull()
  })
})
