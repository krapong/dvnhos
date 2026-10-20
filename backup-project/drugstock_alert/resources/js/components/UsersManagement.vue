<template>
  <div>
    <div class="d-flex ga-2 flex-wrap mb-4 justify-end">
      <v-btn variant="outlined" prepend-icon="mdi-refresh" color="violet" :loading="loading" @click="loadUsers">
        รีเฟรชผู้ใช้งาน
      </v-btn>
    </div>

    <v-alert v-if="message" class="mb-4" variant="tonal" type="info" rounded="xl">
      {{ message }}
    </v-alert>

    <v-card rounded="xl" elevation="0" class="ds-users-panel">
      <v-card-text>
        <v-data-table :headers="headers" :items="items" :loading="loading" class="ds-users-table">
          <template #item.roles="{ item }">
            <v-chip
              v-for="role in resolveRow(item).roles || []"
              :key="role.id"
              size="small"
              class="mr-1 mb-1"
              variant="tonal"
              color="indigo"
            >
              {{ role.name }}
            </v-chip>
          </template>
          <template #item.is_active="{ item }">
            <v-switch
              :model-value="resolveRow(item).is_active"
              color="success"
              inset
              hide-details
              @update:model-value="toggleStatus(resolveRow(item))"
            />
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex ga-2 flex-wrap">
              <v-btn
                size="small"
                variant="outlined"
                prepend-icon="mdi-pencil"
                color="info"
                @click="openEditDialog(resolveRow(item))"
              >
                แก้ไข
              </v-btn>
              <v-btn
                size="small"
                variant="outlined"
                prepend-icon="mdi-account-key"
                color="primary"
                @click="openRoleDialog(resolveRow(item))"
              >
                แก้บทบาท
              </v-btn>
              <v-btn
                size="small"
                variant="outlined"
                prepend-icon="mdi-delete"
                color="error"
                @click="openDeleteDialog(resolveRow(item))"
              >
                ลบ
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <v-dialog v-model="roleDialog.open" max-width="640">
      <v-card rounded="xl" class="ds-users-dialog">
        <v-card-title class="text-h6 font-weight-bold">แก้บทบาทผู้ใช้งาน</v-card-title>
        <v-card-text>
          <div class="text-body-2 text-medium-emphasis mb-4">
            {{ roleDialog.user?.name || '-' }} | {{ roleDialog.user?.email || '-' }}
          </div>
          <v-select
            v-model="roleDialog.roles"
            :items="roleItems"
            item-title="title"
            item-value="value"
            label="บทบาท"
            variant="outlined"
            density="comfortable"
            multiple
            chips
            closable-chips
          />
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="roleDialog.open = false">ยกเลิก</v-btn>
          <v-btn color="primary" :loading="roleDialog.saving" @click="saveRoles">บันทึก</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="editDialog.open" max-width="500">
      <v-card rounded="xl" class="ds-users-dialog">
        <v-card-title class="text-h6 font-weight-bold">แก้ไขข้อมูลผู้ใช้งาน</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="editDialog.form.name"
            label="ชื่อ-นามสกุล"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details
          />
          <v-text-field
            v-model="editDialog.form.email"
            label="อีเมล"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details
          />
          <v-text-field
            v-model="editDialog.form.password"
            label="รหัสผ่านใหม่ (ถ้าไม่เปลี่ยนให้เว้นว่างไว้)"
            type="password"
            variant="outlined"
            density="comfortable"
            hide-details
          />
        </v-card-text>
        <v-card-actions class="justify-end mt-2">
          <v-btn variant="text" @click="editDialog.open = false">ยกเลิก</v-btn>
          <v-btn color="primary" :loading="editDialog.saving" @click="saveUser">บันทึก</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog.open" max-width="400">
      <v-card rounded="xl" class="ds-users-dialog">
        <v-card-title class="text-h6 font-weight-bold text-error">ยืนยันการลบ</v-card-title>
        <v-card-text>
          คุณต้องการลบผู้ใช้งาน <b>{{ deleteDialog.user?.name }}</b> ใช่หรือไม่? 
          <br>การกระทำนี้ไม่สามารถกู้คืนได้
        </v-card-text>
        <v-card-actions class="justify-end mt-2">
          <v-btn variant="text" @click="deleteDialog.open = false">ยกเลิก</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="deleteUser">ยืนยันลบ</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '@/services/api'

const loading = ref(false)
const message = ref('')
const items = ref([])
const roles = ref([])

const headers = [
  { title: 'ชื่อ', key: 'name' },
  { title: 'อีเมล', key: 'email' },
  { title: 'บทบาท', key: 'roles' },
  { title: 'เปิดใช้งาน', key: 'is_active' },
  { title: 'จัดการ', key: 'actions', sortable: false },
]

const roleDialog = reactive({
  open: false,
  saving: false,
  user: null,
  roles: [],
})

const editDialog = reactive({
  open: false,
  saving: false,
  user: null,
  form: {
    name: '',
    email: '',
    password: '',
  },
})

const deleteDialog = reactive({
  open: false,
  saving: false,
  user: null,
})

const roleItems = computed(() => roles.value.map((role) => ({ title: role.name, value: role.name })))

async function loadUsers() {
  loading.value = true
  message.value = ''
  try {
    const { data } = await api.get('/drugstock/users', { params: { per_page: 100 } })
    const payload = data?.data || {}
    items.value = payload.users?.data || []
    roles.value = Array.isArray(payload.roles) ? payload.roles : []
  } catch (error) {
    items.value = []
    roles.value = []
    const status = error?.response?.status
    if (status === 401) {
      message.value = 'กรุณาเข้าสู่ระบบก่อนจัดการผู้ใช้งาน'
    } else if (status === 403) {
      message.value = 'บัญชีนี้ไม่มีสิทธิ์ดูหรือแก้ไขผู้ใช้งาน'
    } else {
      message.value = error?.response?.data?.message || 'ไม่สามารถโหลดข้อมูลผู้ใช้งานได้'
    }
  } finally {
    loading.value = false
  }
}

async function toggleStatus(user) {
  try {
    await api.patch(`/drugstock/users/${user.id}/status`, { is_active: !user.is_active })
    message.value = 'อัปเดตสถานะผู้ใช้งานเรียบร้อย'
    await loadUsers()
  } catch (error) {
    message.value = error?.response?.data?.message || 'ไม่สามารถอัปเดตสถานะผู้ใช้งานได้'
  }
}

function openRoleDialog(user) {
  roleDialog.user = user
  roleDialog.roles = Array.isArray(user.roles) ? user.roles.map((role) => role.name) : []
  roleDialog.open = true
}

async function saveRoles() {
  if (!roleDialog.user) return
  roleDialog.saving = true
  message.value = ''
  try {
    await api.put(`/drugstock/users/${roleDialog.user.id}/roles`, {
      roles: roleDialog.roles,
    })
    message.value = 'บันทึกบทบาทผู้ใช้งานเรียบร้อย'
    roleDialog.open = false
    await loadUsers()
  } catch (error) {
    message.value = error?.response?.data?.message || 'ไม่สามารถบันทึกบทบาทผู้ใช้งานได้'
  } finally {
    roleDialog.saving = false
  }
}

function openEditDialog(user) {
  editDialog.user = user
  editDialog.form.name = user.name || ''
  editDialog.form.email = user.email || ''
  editDialog.form.password = ''
  editDialog.open = true
}

async function saveUser() {
  if (!editDialog.user) return
  editDialog.saving = true
  message.value = ''
  try {
    await api.put(`/drugstock/users/${editDialog.user.id}`, editDialog.form)
    message.value = 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อย'
    editDialog.open = false
    await loadUsers()
  } catch (error) {
    message.value = error?.response?.data?.message || 'ไม่สามารถอัปเดตข้อมูลผู้ใช้งานได้'
  } finally {
    editDialog.saving = false
  }
}

function openDeleteDialog(user) {
  deleteDialog.user = user
  deleteDialog.open = true
}

async function deleteUser() {
  if (!deleteDialog.user) return
  deleteDialog.saving = true
  message.value = ''
  try {
    await api.delete(`/drugstock/users/${deleteDialog.user.id}`)
    message.value = 'ลบผู้ใช้งานเรียบร้อยแล้ว'
    deleteDialog.open = false
    await loadUsers()
  } catch (error) {
    message.value = error?.response?.data?.message || 'ไม่สามารถลบผู้ใช้งานได้'
  } finally {
    deleteDialog.saving = false
  }
}

function resolveRow(item) {
  return item?.raw ?? item ?? {}
}

onMounted(loadUsers)
</script>

<style scoped>
.ds-users-hero {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}
.ds-users-hero__content {
  padding-top: 4px;
  padding-bottom: 4px;
}
.ds-hero-icon {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
}
.ds-hero-icon--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(221, 214, 254, 0.92));
}
.ds-users-table .v-chip {
  font-weight: 700;
}
.ds-users-table .v-data-table__tr:hover {
  background: rgba(245, 247, 255, 0.65);
}

.ds-users-panel {
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
}

.ds-users-dialog {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.5), rgba(255, 255, 255, 0.98));
}
</style>


