import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
})

const token = window.localStorage.getItem('medstock_token')
if (token) {
  api.defaults.headers.common.Authorization = `Bearer ${token}`
}

api.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error),
)

export default api
