<template>
  <div>
    <v-alert
      v-if="errorMessage"
      type="warning"
      variant="tonal"
      class="mb-4"
      rounded="xl"
      density="comfortable"
    >
      {{ errorMessage }}
    </v-alert>

    <v-card class="mb-4 page-hero ds-alerts-hero" rounded="xl" elevation="0">
      <v-card-text class="d-flex flex-column flex-md-row justify-space-between ga-4">
        <div class="d-flex align-center ga-3">
          <v-avatar color="red-lighten-4" size="42" variant="tonal">
            <v-icon icon="mdi-bell-outline" color="#dc2626" size="22" />
          </v-avatar>
          <div>
            <div class="text-overline text-medium-emphasis">การแจ้งเตือน</div>
            <div class="text-h5 font-weight-bold">ติดตามรายการหมดอายุและ stock ต่ำ</div>
          </div>
        </div>
        <div class="d-flex ga-2 flex-wrap">
          <v-btn color="primary" prepend-icon="mdi-plus" @click="ruleDialog = true">เพิ่มกฎแจ้งเตือน</v-btn>
          <v-btn variant="outlined" prepend-icon="mdi-sync" :loading="presetLoading" @click="createPresetRules">สร้าง 4 รอบอัตโนมัติ</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-row class="mb-4" dense>
      <v-col cols="12" md="4"><KpiCard title="ทั้งหมด" :value="summary.all || 0" subtitle="รายการแจ้งเตือนทั้งหมด" icon="mdi-bell-outline" color="primary" /></v-col>
      <v-col cols="12" md="4"><KpiCard title="ยังไม่แก้ไข" :value="summary.pending || 0" subtitle="ต้องติดตาม" icon="mdi-alert-circle" color="amber" /></v-col>
      <v-col cols="12" md="4"><KpiCard title="แก้ไขแล้ว" :value="summary.resolved || 0" subtitle="ปิดรายการแล้ว" icon="mdi-check-circle" color="success" /></v-col>
    </v-row>

    <v-card rounded="xl" elevation="0" class="mb-4 ds-alerts-panel ds-alerts-panel--violet">
      <v-card-title class="font-weight-bold">ประวัติรอบแจ้งเตือน 4 ระยะ</v-card-title>
      <v-card-text>
        <v-data-table :headers="roundHeaders" :items="roundItems" :loading="roundLoading" class="ds-alerts-table">
          <template #item.is_enabled="{ item }">
            <v-chip size="small" :color="resolveRow(item).is_enabled ? 'success' : 'grey'" variant="tonal">
              {{ resolveRow(item).is_enabled ? 'ใช้งาน' : 'ปิด' }}
            </v-chip>
          </template>
          <template #item.latest_alert_at="{ item }">
            {{ resolveRow(item).latest_alert_at || '-' }}
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <v-card rounded="xl" elevation="0" class="ds-alerts-panel ds-alerts-panel--amber">
      <v-card-text>
        <v-data-table 
          :headers="headers" 
          :items="items" 
          :loading="loading" 
          class="ds-alerts-table"
          @dblclick:row="handleRowDoubleClick"
          hover
        >
          <template #item.status="{ item }">
            <v-chip size="small" :color="resolveRow(item).status === 'resolved' ? 'success' : 'warning'" variant="tonal">
              {{ resolveRow(item).status === 'resolved' ? 'แก้ไขแล้ว' : 'ยังไม่แก้ไข' }}
            </v-chip>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              v-if="resolveRow(item).status !== 'resolved'"
              color="primary"
              variant="text"
              size="small"
              @click="resolveAlert(resolveRow(item))"
            >
              ปิด
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <v-dialog v-model="ruleDialog" max-width="640">
      <v-card rounded="xl">
        <v-card-title class="font-weight-bold">เพิ่มกฎแจ้งเตือน</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-select v-model="rule.scope" :items="scopeItems" label="ขอบเขต" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="6">
              <v-select v-model="rule.alert_type" :items="alertTypeItems" label="ประเภท" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="6"><v-text-field v-model="rule.threshold_qty" label="เกณฑ์ stock ต่ำ" type="number" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="rule.expiry_days"
                :items="expiryRounds"
                label="รอบวันก่อนหมดอายุ"
                variant="outlined"
                density="comfortable"
                :disabled="rule.alert_type !== 'near_expiry'"
              />
            </v-col>
            <v-col cols="12">
              <v-switch v-model="rule.notify_telegram" label="แจ้ง Telegram" inset />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="ruleDialog = false">ยกเลิก</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveRule">บันทึก</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import KpiCard from '@/components/KpiCard.vue'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const presetLoading = ref(false)
const roundLoading = ref(false)
const errorMessage = ref('')
const items = ref([])
const summary = ref({})
const roundItems = ref([])
const ruleDialog = ref(false)

const headers = [
  { title: 'ประเภท', key: 'notification_type' },
  { title: 'ยา', key: 'drug.name' },
  { title: 'คลัง', key: 'warehouse.name' },
  { title: 'สถานะ', key: 'status' },
  { title: 'จัดการ', key: 'actions', sortable: false },
]

const roundHeaders = [
  { title: 'รอบ', key: 'round' },
  { title: 'กฎ', key: 'rule_id' },
  { title: 'สถานะ', key: 'is_enabled' },
  { title: 'ทั้งหมด', key: 'total' },
  { title: 'ค้าง', key: 'pending' },
  { title: 'ปิดแล้ว', key: 'resolved' },
  { title: 'แจ้งล่าสุด', key: 'latest_alert_at' },
]

const scopeItems = ['warehouse', 'location', 'drug']
const alertTypeItems = ['low_stock', 'near_expiry', 'expired']
const expiryRounds = [30, 45, 60, 90]

const rule = reactive({
  scope: 'warehouse',
  alert_type: 'low_stock',
  threshold_qty: 10,
  expiry_days: 90,
  notify_telegram: true,
})

async function loadAlerts() {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await api.get('/drugstock/alerts', { params: { per_page: 100 } })
    const payload = data?.data
    if (!payload || typeof payload !== 'object') {
      throw new Error('invalid_api_response')
    }

    summary.value = payload.summary ?? {}
    items.value = payload.items?.data ?? []
  } catch (error) {
    summary.value = {}
    items.value = []
    if (error?.response?.status === 401) {
      errorMessage.value = 'กรุณาเข้าสู่ระบบก่อนดูข้อมูลแจ้งเตือน'
    } else if (error?.message === 'invalid_api_response') {
      errorMessage.value = 'ระบบตัวอย่างยังไม่ได้เชื่อม API จริง จึงแสดงข้อมูลตัวอย่างว่าง'
    } else {
      errorMessage.value = error?.response?.data?.message || 'ไม่สามารถโหลดรายการแจ้งเตือนได้'
    }
  } finally {
    loading.value = false
  }
}

async function loadRounds() {
  roundLoading.value = true
  try {
    const { data } = await api.get('/drugstock/alerts/rounds')
    const payload = data?.data
    if (!payload || typeof payload !== 'object') {
      throw new Error('invalid_api_response')
    }

    roundItems.value = payload ?? []
  } catch (error) {
    roundItems.value = []
    if (!errorMessage.value) {
      errorMessage.value =
        error?.message === 'invalid_api_response'
          ? 'ระบบตัวอย่างยังไม่ได้เชื่อม API จริง จึงแสดงข้อมูลตัวอย่างว่าง'
          : error?.response?.data?.message || 'ไม่สามารถโหลดประวัติรอบแจ้งเตือนได้'
    }
  } finally {
    roundLoading.value = false
  }
}

async function resolveAlert(item) {
  await api.patch(`/drugstock/alerts/${item.id}/resolve`)
  await loadAlerts()
  await loadRounds()
}

async function saveRule() {
  saving.value = true
  try {
    const payload = {
      ...rule,
      expiry_days: rule.alert_type === 'near_expiry' ? Number(rule.expiry_days) : null,
    }
    await api.post('/drugstock/alerts/rules', payload)
    ruleDialog.value = false
    await loadRounds()
  } finally {
    saving.value = false
  }
}

async function createPresetRules() {
  presetLoading.value = true
  try {
    await api.post('/drugstock/alerts/rules/presets', { scope: 'warehouse' })
    await loadRounds()
  } finally {
    presetLoading.value = false
  }
}

function resolveRow(item) {
  return item?.raw ?? item ?? {}
}

const handleRowDoubleClick = (event, { item }) => {
  const rowItem = resolveRow(item)
  if (rowItem.status !== 'resolved' && rowItem.warehouse_id) {
    router.push(`/warehouses/${rowItem.warehouse_id}`)
  }
}

onMounted(async () => {
  await Promise.all([loadAlerts(), loadRounds()])
})
</script>

<style scoped>
.ds-alerts-hero {
  background: linear-gradient(135deg, rgba(255, 241, 242, 0.98), rgba(255, 255, 255, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.ds-alerts-panel {
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
}

.ds-alerts-panel--amber {
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.44), rgba(255, 255, 255, 0.98));
}

.ds-alerts-panel--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.44), rgba(255, 255, 255, 0.98));
}

.ds-alerts-table :deep(th) {
  background: rgba(241, 245, 249, 0.95);
  color: #334155;
  font-weight: 800;
}
</style>
