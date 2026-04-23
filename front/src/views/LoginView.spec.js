import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LoginView from './LoginView.vue'

const authSessionMock = vi.hoisted(() => ({
  login: vi.fn(),
}))

vi.mock('../stores/authSession', () => authSessionMock)

const TargetView = { template: '<div>Destino</div>' }

const makeRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/login',
        name: 'login',
        component: LoginView,
      },
      {
        path: '/operacional/fila',
        name: 'operational-queue',
        component: TargetView,
      },
      {
        path: '/filas',
        name: 'queues',
        component: TargetView,
      },
    ],
  })
}

const mountAt = async (path) => {
  const router = makeRouter()
  await router.push(path)
  await router.isReady()

  const wrapper = mount(LoginView, {
    global: {
      plugins: [router],
    },
  })

  await flushPromises()

  return { router, wrapper }
}

describe('LoginView', () => {
  beforeEach(() => {
    authSessionMock.login.mockReset()
    authSessionMock.login.mockResolvedValue({})
  })

  it('redireciona para a query redirect após login com sucesso', async () => {
    const { router, wrapper } = await mountAt('/login?redirect=%2Ffilas')

    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(authSessionMock.login).toHaveBeenCalledWith({
      email: 'test@example.com',
      password: 'password',
    })
    expect(router.currentRoute.value.path).toBe('/filas')
  })

  it('redireciona para a fila operacional quando não há redirect', async () => {
    const { router, wrapper } = await mountAt('/login')

    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('operational-queue')
  })
})
