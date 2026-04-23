import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AclView from './AclView.vue'

const authServiceMocks = vi.hoisted(() => ({
  fetchAclOverview: vi.fn(),
}))

const governanceServiceMocks = vi.hoisted(() => ({
  updateUserRole: vi.fn(),
}))

const authSessionMock = vi.hoisted(() => ({
  authState: {
    user: {
      role_context: {
        key: 'admin',
      },
    },
  },
  hasPermission: vi.fn(),
}))

vi.mock('../services/authService', () => authServiceMocks)
vi.mock('../services/governanceService', () => governanceServiceMocks)
vi.mock('../stores/authSession', () => authSessionMock)

const aclOverview = {
  current_user: {
    name: 'Alice Admin',
    role: {
      key: 'admin',
      label: 'Administrador operacional',
      description: 'Responsável por governança operacional.',
    },
    permissions: [
      { key: 'acl.view', label: 'Visualizar matriz de ACL' },
    ],
    tenant: {
      name: 'Montreal Operacoes',
    },
  },
  roles: [
    {
      key: 'admin',
      label: 'Administrador operacional',
      description: 'Coordena o tenant.',
      permissions: [{ key: 'acl.view', label: 'Visualizar matriz de ACL' }],
    },
  ],
  manageable_roles: [
    { key: 'admin', label: 'Administrador operacional' },
    { key: 'viewer', label: 'Leitor operacional' },
  ],
  tenant_users: [
    {
      id: 7,
      name: 'Otavio Operator',
      email: 'otavio.operator@example.com',
      role: 'operator',
      role_context: {
        key: 'operator',
        label: 'Operador',
      },
      permissions: [{ key: 'attendances.view', label: 'Visualizar atendimentos' }],
    },
  ],
  role_summary: [
    {
      key: 'admin',
      label: 'Administrador operacional',
      description: 'Coordena o tenant.',
      users_count: 1,
    },
  ],
}

const mountView = async () => {
  const wrapper = mount(AclView)
  await flushPromises()
  await nextTick()
  return wrapper
}

describe('AclView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    authSessionMock.hasPermission.mockImplementation((permission) => {
      return ['acl.view', 'acl.manage'].includes(permission)
    })

    authServiceMocks.fetchAclOverview.mockResolvedValue(aclOverview)
    governanceServiceMocks.updateUserRole.mockResolvedValue({})
  })

  it('renderiza usuários do tenant e resumo por papel', async () => {
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Governança do tenant')
    expect(wrapper.text()).toContain('Otavio Operator')
    expect(wrapper.text()).toContain('Administrador operacional')
  })

  it('atualiza papel quando o usuário tem permissão administrativa', async () => {
    const wrapper = await mountView()

    await wrapper.get('[data-testid="acl-role-select"]').setValue('viewer')
    await flushPromises()

    expect(governanceServiceMocks.updateUserRole).toHaveBeenCalledWith(7, { role: 'viewer' })
    expect(wrapper.text()).toContain('Papel atualizado com sucesso.')
  })

  it('mostra estado somente leitura quando falta permissão de gestão', async () => {
    authSessionMock.hasPermission.mockImplementation((permission) => permission === 'acl.view')

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Somente administração operacional pode alterar papéis.')
    expect(wrapper.find('[data-testid="acl-role-select"]').exists()).toBe(false)
  })
})
