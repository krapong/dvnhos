<template>
  <div>
    <v-card class="mb-4 page-hero ds-roles-hero" rounded="xl" elevation="0">
      <v-card-text class="d-flex flex-column flex-md-row justify-space-between ga-4 ds-roles-hero__content">
        <div class="d-flex flex-column">
          <div class="d-flex align-center ga-3 mb-2">
            <v-avatar color="green-lighten-4" size="42" variant="tonal" class="ds-hero-icon ds-hero-icon--green">
              <v-icon icon="mdi-shield-account-outline" color="#059669" size="22" />
            </v-avatar>
            <div class="text-overline text-medium-emphasis">เมนูแอดมิน</div>
          </div>
          <div class="text-h5 font-weight-bold">จัดการบทบาทและสิทธิ์</div>
          <div class="text-body-2 text-medium-emphasis">เลือกบทบาทหนึ่ง แล้วกำหนดสิทธิ์ที่ต้องการใช้งานในระบบ</div>
        </div>
        <div class="d-flex ga-2 flex-wrap">
          <v-btn variant="outlined" prepend-icon="mdi-refresh" color="teal" :loading="loading" @click="loadRoles">
            รีเฟรช
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-content-save" :loading="saving" @click="save">
            บันทึกสิทธิ์
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-alert v-if="message" class="mb-4" variant="tonal" type="info" rounded="xl">{{ message }}</v-alert>

    <v-row class="mb-2">
      <v-col cols="12" md="4">
        <v-card rounded="xl" elevation="0" class="ds-role-summary ds-role-summary--green">
          <v-card-text>
            <div class="d-flex align-center ga-3">
              <v-avatar color="green-lighten-4" size="34" variant="tonal">
                <v-icon icon="mdi-shield-account-outline" color="#059669" size="18" />
              </v-avatar>
              <div>
                <div class="text-caption text-medium-emphasis">จำนวนบทบาท</div>
                <div class="text-h4 font-weight-bold">{{ roles.length }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" elevation="0" class="ds-role-summary ds-role-summary--blue">
          <v-card-text>
            <div class="d-flex align-center ga-3">
              <v-avatar color="blue-lighten-4" size="34" variant="tonal">
                <v-icon icon="mdi-key-variant" color="#2563eb" size="18" />
              </v-avatar>
              <div>
                <div class="text-caption text-medium-emphasis">จำนวนสิทธิ์</div>
                <div class="text-h4 font-weight-bold">{{ permissions.length }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" elevation="0" class="ds-role-summary ds-role-summary--amber">
          <v-card-text>
            <div class="d-flex align-center ga-3">
              <v-avatar color="amber-lighten-4" size="34" variant="tonal">
                <v-icon icon="mdi-account-star-outline" color="#d97706" size="18" />
              </v-avatar>
              <div>
                <div class="text-caption text-medium-emphasis">บทบาทสมัครสมาชิก</div>
                <div class="text-h6 font-weight-bold">{{ defaultRole || '-' }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card rounded="xl" elevation="0" class="mb-4">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="5">
            <v-select
              v-model="selectedRoleId"
              :items="roleItems"
              item-title="title"
              item-value="value"
              label="เลือกบทบาท"
              variant="outlined"
              density="comfortable"
              @update:model-value="applySelectedRole"
            />
          </v-col>
          <v-col cols="12" md="7">
            <v-text-field :model-value="selectedRole?.name || '-'" label="บทบาทที่กำลังแก้ไข" variant="outlined" density="comfortable" readonly />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card rounded="xl" elevation="0" class="ds-role-panel">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4" v-for="group in permissionGroups" :key="group.name">
            <v-card variant="tonal" rounded="lg" class="h-100 ds-role-card ds-role-card--violet">
              <v-card-text>
                <div class="d-flex align-center ga-2 mb-3">
                  <v-avatar color="cyan-lighten-4" size="30" variant="tonal">
                    <v-icon icon="mdi-shield-outline" color="#0891b2" size="16" />
                  </v-avatar>
                  <div class="text-subtitle-1 font-weight-bold">{{ group.name }}</div>
                </div>
                <v-checkbox
                  v-for="permission in group.items"
                  :key="permission.name"
                  v-model="selectedPermissions"
                  :value="permission.name"
                  :label="permission.name"
                  density="compact"
                  hide-details
                />
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(false)
const saving = ref(false)
const message = ref('')
const roles = ref([])
const permissions = ref([])
const defaultRole = ref('')
const selectedRoleId = ref(null)
const selectedPermissions = ref([])

const roleItems = computed(() => roles.value.map((role) => ({ title: role.name, value: role.id })))

const selectedRole = computed(() => roles.value.find((role) => role.id === selectedRoleId.value) || null)

const permissionGroups = computed(() => {
  const groups = new Map()

  for (const permission of permissions.value) {
    const [group] = String(permission.name).split('.')
    const title = groupLabels[group] || group || 'อื่น ๆ'
    if (!groups.has(title)) groups.set(title, [])
    groups.get(title).push(permission)
  }

  return Array.from(groups.entries()).map(([name, items]) => ({ name, items }))
})

const groupLabels = {
  dashboard: 'ภาพรวม',
  warehouse: 'คลังย่อย',
  drug: 'ยา',
  location: 'สถานที่',
  stock: 'คลัง/สต็อก',
  alert: 'แจ้งเตือน',
  user: 'ผู้ใช้งาน',
  report: 'รายงาน',
  role: 'บทบาท',
  settings: 'ตั้งค่า',
  telegram: 'Telegram',
}

async function loadRoles() {
  loading.value = true
  message.value = ''
  try {
    const { data } = await api.get('/drugstock/roles')
    const payload = data?.data || {}
    roles.value = Array.isArray(payload.roles) ? payload.roles : []
    permissions.value = Array.isArray(payload.permissions) ? payload.permissions : []
    defaultRole.value = payload.default_role || ''
    if (!selectedRoleId.value && roles.value.length) {
      selectedRoleId.value = roles.value[0].id
    }
    applySelectedRole()
  } finally {
    loading.value = false
  }
}

function applySelectedRole() {
  const role = selectedRole.value
  selectedPermissions.value = Array.isArray(role?.permissions) ? [...role.permissions] : []
}

async function save() {
  if (!selectedRole.value) return
  saving.value = true
  message.value = ''
  try {
    await api.put(`/drugstock/roles/${selectedRole.value.id}`, {
      permissions: selectedPermissions.value,
    })
    message.value = 'บันทึกสิทธิ์บทบาทเรียบร้อย'
    await loadRoles()
  } finally {
    saving.value = false
  }
}

onMounted(loadRoles)
</script>

<style scoped>
.ds-roles-hero {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(240, 253, 244, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}
.ds-roles-hero__content {
  padding-top: 4px;
  padding-bottom: 4px;
}
.ds-hero-icon {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
}
.ds-hero-icon--green {
  background: linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(187, 247, 208, 0.92));
}
.ds-role-card {
  border: 1px solid rgba(148, 163, 184, 0.14);
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}
.ds-role-card .v-card-text {
  gap: 8px;
}

.ds-role-summary,
.ds-role-panel {
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
}

.ds-role-summary--green {
  background: linear-gradient(180deg, rgba(240, 253, 244, 0.55), rgba(255, 255, 255, 0.98));
}

.ds-role-summary--blue {
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.55), rgba(255, 255, 255, 0.98));
}

.ds-role-summary--amber {
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.55), rgba(255, 255, 255, 0.98));
}

.ds-role-card--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.68), rgba(255, 255, 255, 0.98));
}
</style>


