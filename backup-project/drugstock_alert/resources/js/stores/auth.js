import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(window.localStorage.getItem('medstock_user') || 'null'),
    token: window.localStorage.getItem('medstock_token'),
  }),
  actions: {
    async login(payload) {
      const { data } = await api.post('/auth/login', payload)
      this.token = data.data.token
      this.user = data.data.user
      window.localStorage.setItem('medstock_token', this.token)
      window.localStorage.setItem('medstock_user', JSON.stringify(this.user))
      api.defaults.headers.common.Authorization = `Bearer ${this.token}`
    },
    async register(payload) {
      const { data } = await api.post('/auth/register', payload)
      return data
    },
    async fetchMe() {
      const { data } = await api.get('/auth/me')
      this.user = data.data
      window.localStorage.setItem('medstock_user', JSON.stringify(this.user))
    },
    logout() {
      this.user = null
      this.token = null
      delete api.defaults.headers.common.Authorization
      window.localStorage.removeItem('medstock_token')
      window.localStorage.removeItem('medstock_user')
    },
  },
})
