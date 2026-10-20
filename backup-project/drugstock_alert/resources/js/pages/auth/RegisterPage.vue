<template>
  <div class="auth-wrapper">
    <div class="auth-bg-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>
    
    <v-container class="fill-height align-center justify-center relative-z1 px-4 py-8">
      <v-card class="premium-auth-card" rounded="xl" elevation="0" max-width="540" width="100%">
        <div class="auth-card-inner">
          <div class="text-center mb-8">
            <v-avatar color="teal" size="64" variant="tonal" class="mb-4 shadow-sm">
              <v-icon icon="mdi-account-plus-outline" size="36" />
            </v-avatar>
            <div class="text-h4 font-weight-black text-slate-900 mb-2">สมัครสมาชิก</div>
            <div class="text-subtitle-1 text-slate-500">ลงทะเบียนเพื่อเริ่มต้นใช้งาน Drug Stock Alert</div>
          </div>

          <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-6 rounded-lg font-weight-medium">
            {{ errorMessage }}
          </v-alert>

          <v-form @submit.prevent="submit" class="auth-form">
            <div class="form-group mb-4">
              <label class="form-label text-slate-700 font-weight-bold d-block mb-2 text-sm">ชื่อ-นามสกุล</label>
              <v-text-field 
                v-model="name" 
                placeholder="ระบุชื่อและนามสกุล" 
                prepend-inner-icon="mdi-account-outline"
                variant="outlined" 
                density="comfortable"
                bg-color="white"
                color="teal"
                hide-details="auto"
                class="premium-input premium-input--teal"
              />
            </div>

            <div class="form-group mb-4">
              <label class="form-label text-slate-700 font-weight-bold d-block mb-2 text-sm">อีเมล</label>
              <v-text-field 
                v-model="email" 
                placeholder="you@hospital.local" 
                prepend-inner-icon="mdi-email-outline"
                variant="outlined" 
                density="comfortable"
                bg-color="white"
                color="teal"
                hide-details="auto"
                class="premium-input premium-input--teal"
              />
            </div>
            
            <div class="form-group mb-4">
              <label class="form-label text-slate-700 font-weight-bold d-block mb-2 text-sm">รหัสผ่าน</label>
              <v-text-field 
                v-model="password" 
                :type="showPassword ? 'text' : 'password'" 
                placeholder="••••••••" 
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined" 
                density="comfortable"
                bg-color="white"
                color="teal"
                hide-details="auto"
                class="premium-input premium-input--teal"
              />
            </div>

            <div class="form-group mb-8">
              <label class="form-label text-slate-700 font-weight-bold d-block mb-2 text-sm">ยืนยันรหัสผ่าน</label>
              <v-text-field 
                v-model="passwordConfirmation" 
                :type="showPasswordConfirm ? 'text' : 'password'" 
                placeholder="••••••••" 
                prepend-inner-icon="mdi-lock-check-outline"
                :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                variant="outlined" 
                density="comfortable"
                bg-color="white"
                color="teal"
                hide-details="auto"
                class="premium-input premium-input--teal"
              />
            </div>

            <v-btn 
              type="submit" 
              block 
              color="teal" 
              size="x-large" 
              rounded="pill" 
              class="font-weight-bold text-body-1 mb-6 shadow-btn shadow-btn--teal"
              :loading="loading"
            >
              ลงทะเบียนเข้าใช้งาน
            </v-btn>
          </v-form>

          <div class="text-center text-body-2 text-slate-600">
            มีบัญชีผู้ใช้งานอยู่แล้ว? 
            <router-link to="/login" class="text-teal font-weight-bold text-decoration-none ml-1">เข้าสู่ระบบ</router-link>
          </div>
        </div>
      </v-card>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const errorMessage = ref('')
const loading = ref(false)
const auth = useAuthStore()
const router = useRouter()

const submit = async () => {
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน'
    return
  }

  errorMessage.value = ''
  loading.value = true

  try {
    await api.post('/auth/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    
    // Auto login after register
    await auth.login({ email: email.value, password: password.value })
    await router.push('/')
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'สมัครสมาชิกไม่สำเร็จ กรุณาตรวจสอบข้อมูลอีกครั้ง'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-wrapper {
  position: relative;
  min-height: 100vh;
  background: #f8fafc;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Abstract Background Shapes */
.auth-bg-shapes {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.shape {
  position: absolute;
  filter: blur(80px);
  opacity: 0.5;
  border-radius: 50%;
  animation: float 20s infinite alternate;
}

.shape-1 {
  background: rgba(20, 184, 166, 0.3);
  width: 500px;
  height: 500px;
  top: -100px;
  left: -150px;
  animation-delay: 0s;
}

.shape-2 {
  background: rgba(14, 165, 233, 0.3);
  width: 400px;
  height: 400px;
  bottom: -50px;
  right: -100px;
  animation-delay: -5s;
}

.shape-3 {
  background: rgba(139, 92, 246, 0.2);
  width: 600px;
  height: 600px;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: -10s;
}

@keyframes float {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(30px, 50px) scale(1.1); }
}

.relative-z1 {
  position: relative;
  z-index: 1;
}

/* Premium Card */
.premium-auth-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15) !important;
}

.auth-card-inner {
  padding: 48px 40px;
}

/* Typography & Colors */
.text-slate-900 { color: #0f172a; }
.text-slate-700 { color: #334155; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }

/* Inputs */
.premium-input :deep(.v-field) {
  border-radius: 12px;
  border-color: #e2e8f0;
  transition: all 0.3s ease;
}
.premium-input--teal :deep(.v-field--focused) {
  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.15);
}

/* Buttons */
.shadow-btn--teal {
  box-shadow: 0 8px 20px -6px rgba(20, 184, 166, 0.5) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.shadow-btn--teal:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px -8px rgba(20, 184, 166, 0.6) !important;
}

@media (max-width: 600px) {
  .auth-card-inner {
    padding: 32px 24px;
  }
}
</style>
