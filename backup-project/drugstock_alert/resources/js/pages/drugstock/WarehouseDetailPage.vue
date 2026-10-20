<template>
  <div>
    <v-card class="mb-4 page-hero ds-warehouse-hero" rounded="xl" elevation="0">
      <v-card-text
        v-if="warehouse"
        class="d-flex flex-column flex-md-row align-center justify-space-between ga-4"
      >
        <div class="d-flex align-center ga-3">
          <v-btn icon variant="tonal" size="x-large" rounded="circle" @click="goBack">
            <v-icon icon="mdi-arrow-left" />
          </v-btn>
          <div>
            <div class="text-overline text-medium-emphasis">รายละเอียดคลังย่อย</div>
            <div class="text-h5 font-weight-bold">{{ warehouse.name }}</div>
            <div class="text-body-2 text-medium-emphasis">
              {{ warehouse.code }} • {{ warehouse.location?.name || 'ไม่มีสถานที่' }}
            </div>
            <div class="d-flex ga-4 mt-3 flex-wrap">
              <div>
                <div class="text-caption text-medium-emphasis">คงเหลือรวม</div>
                <div class="text-body-1 font-weight-bold">{{ summary.total_qty || 0 }}</div>
              </div>
              <div>
                <div class="text-caption text-medium-emphasis">ใช้ไปแล้ว</div>
                <div class="text-body-1 font-weight-bold">{{ summary.issued_qty || 0 }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex ga-2 flex-wrap justify-end">
          <v-btn color="primary" prepend-icon="mdi-arrow-down-bold-box-outline" @click="openAction('receive')">
            เบิกยา
          </v-btn>
          <v-btn color="error" variant="tonal" prepend-icon="mdi-pill" @click="openAction('issue')">
            ตัด stock
          </v-btn>
          <v-btn variant="outlined" prepend-icon="mdi-scissors-cutting" @click="openAction('adjust')">
            เบิกเข้าคลัง
          </v-btn>
          <v-btn variant="outlined" prepend-icon="mdi-history" @click="tab = 'history'">
            ประวัติการเบิก/พิมพ์
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-expand-transition>
      <v-card
        v-if="activeAction"
        rounded="xl"
        elevation="0"
        class="mb-4 ds-warehouse-action"
        :color="actionMeta.cardColor"
        variant="tonal"
      >
        <v-card-title class="d-flex align-center justify-space-between">
          <div class="d-flex align-center ga-2">
            <v-icon :icon="actionMeta.icon" />
            <span class="font-weight-bold">{{ actionMeta.title }}</span>
          </div>
          <v-btn icon variant="text" @click="cancelAction">
            <v-icon icon="mdi-close" />
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-alert
            :type="actionMeta.alertType"
            variant="tonal"
            class="mb-4"
            density="comfortable"
          >
            {{ actionMeta.description }}
          </v-alert>

          <v-row v-if="activeAction === 'receive'">
            <v-col cols="12" md="6">
              <v-select
                v-model="receiveForm.drug_id"
                :items="drugOptions"
                item-title="title"
                item-value="value"
                label="รายการยา"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="receiveForm.quantity" label="จำนวน" type="number" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="receiveForm.reason_type"
                :items="receiveReasons"
                label="เหตุผล"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="receiveForm.lot_no" label="Lot" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="receiveForm.unit_cost" label="ต้นทุน/หน่วย" type="number" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="receiveForm.expiry_date" label="หมดอายุ" type="date" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="receiveForm.received_date" label="วันที่รับเข้า" type="date" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="receiveForm.note" label="หมายเหตุ" variant="outlined" density="comfortable" rows="2" />
            </v-col>
          </v-row>

          <v-row v-else-if="activeAction === 'issue'">
            <v-col cols="12" md="6">
              <v-select
                v-model="issueForm.drug_id"
                :items="drugOptions"
                item-title="title"
                item-value="value"
                label="รายการยา"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="issueForm.quantity" label="จำนวนตัด" type="number" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="issueForm.reason_type"
                :items="issueReasons"
                label="เหตุผล"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="issueForm.note" label="รายละเอียด" variant="outlined" density="comfortable" rows="3" />
            </v-col>
          </v-row>

          <v-row v-else-if="activeAction === 'adjust'">
            <v-col cols="12" md="6">
              <v-select
                v-model="adjustForm.drug_id"
                :items="drugOptions"
                item-title="title"
                item-value="value"
                label="รายการยา"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="adjustForm.quantity" label="จำนวน" type="number" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="adjustForm.direction"
                :items="directionItems"
                item-title="title"
                item-value="value"
                label="เหตุผล"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="adjustForm.lot_no" label="Lot" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="adjustForm.expiry_date" label="หมดอายุ" type="date" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="adjustForm.note" label="หมายเหตุ" variant="outlined" density="comfortable" />
            </v-col>
          </v-row>

          <div class="d-flex align-center justify-space-between mt-2 flex-wrap ga-3">
            <v-btn variant="text" color="primary" @click="cancelAction">ยกเลิก</v-btn>
            <div class="d-flex ga-2">
              <v-btn variant="outlined" prepend-icon="mdi-sync" :loading="saving" @click="resetAction">
                รีเฟรชรายการ
              </v-btn>
              <v-btn color="primary" :loading="saving" @click="submitAction">บันทึก{{ actionMeta.shortTitle }}</v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </v-expand-transition>

    <v-row class="mb-4" dense>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="รายการยาในคลัง" :value="summary.drug_count || 0" subtitle="รายการที่มี stock" icon="mdi-pill" color="primary" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="คงเหลือรวม" :value="summary.total_qty || 0" subtitle="หน่วยยา" icon="mdi-database-outline" color="teal" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="ใกล้หมดอายุ" :value="summary.near_expiry || 0" subtitle="ล็อตที่ต้องติดตาม" icon="mdi-alert-circle" color="amber" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="แจ้งเตือน" :value="summary.alerts || 0" subtitle="รายการที่ยังไม่แก้ไข" icon="mdi-bell-outline" color="red" />
      </v-col>
    </v-row>

    <v-card rounded="xl" elevation="0" class="ds-warehouse-tabs">
      <v-tabs v-model="tab" color="primary" class="px-4 ds-warehouse-tabs__bar">
        <v-tab value="current">รายการปัจจุบัน</v-tab>
        <v-tab value="lots">ล็อตยา</v-tab>
        <v-tab value="near_expiry">ยาใกล้หมดอายุ</v-tab>
        <v-tab value="issue">ประวัติการเบิก</v-tab>
        <v-tab value="receive">ประวัติรับเข้าคลัง</v-tab>
        <v-tab value="adjust">ประวัติตัด stock</v-tab>
      </v-tabs>
      <v-divider />

      <v-window v-model="tab">
        <v-window-item value="current">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
              ตารางนี้แสดงคงเหลือปัจจุบันจากคลังจริง และจำนวนที่ใช้ไปตั้งแต่รอบเบิกยาคลังล่าสุด
            </v-alert>
            <div class="d-flex justify-end mb-3">
              <v-btn size="small" variant="tonal" color="info" prepend-icon="mdi-view-list" @click="tab = 'lots'">
                แสดง lot
              </v-btn>
            </div>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>ยา</th>
                  <th>Lot</th>
                  <th>คงเหลือ</th>
                  <th>หมดอายุ</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="lot in currentLots" :key="lot.id">
                  <td>{{ lot.stock?.drug?.name || '-' }}</td>
                  <td>{{ lot.lot_no }}</td>
                  <td>{{ lot.qty_on_hand }}</td>
                  <td>{{ lot.expiry_date || '-' }}</td>
                  <td>{{ lot.status }}</td>
                </tr>
                <tr v-if="!currentLots.length">
                  <td colspan="5" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีรายการในคลังนี้
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>

        <v-window-item value="lots">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
              มุมมองนี้แยกล็อตยาออกมาเพื่อดูวันหมดอายุ คงเหลือ และสถานะของแต่ละล็อตแบบละเอียด
            </v-alert>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>รหัสยา</th>
                  <th>ยา</th>
                  <th>Lot</th>
                  <th>คงเหลือ</th>
                  <th>หมดอายุ</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="lot in lotRows" :key="lot.id">
                  <td>{{ lot.stock?.drug?.code || '-' }}</td>
                  <td>{{ lot.stock?.drug?.name || '-' }}</td>
                  <td>{{ lot.lot_no }}</td>
                  <td>{{ lot.qty_on_hand }}</td>
                  <td>{{ lot.expiry_date || '-' }}</td>
                  <td>{{ lot.status }}</td>
                </tr>
                <tr v-if="!lotRows.length">
                  <td colspan="6" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีล็อตยาในคลังนี้
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>

        <v-window-item value="near_expiry">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="warning" variant="tonal" class="mb-4" density="comfortable">
              แสดงเฉพาะล็อตที่เข้าเกณฑ์ใกล้หมดอายุภายใน {{ expiryWindowDays }} วัน
            </v-alert>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>ยา</th>
                  <th>Lot</th>
                  <th>คงเหลือ</th>
                  <th>หมดอายุ</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="lot in nearExpiryLots" :key="lot.id">
                  <td>{{ lot.stock?.drug?.name || '-' }}</td>
                  <td>{{ lot.lot_no }}</td>
                  <td>{{ lot.qty_on_hand }}</td>
                  <td>{{ lot.expiry_date || '-' }}</td>
                  <td>{{ lot.status }}</td>
                </tr>
                <tr v-if="!nearExpiryLots.length">
                  <td colspan="5" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีรายการใกล้หมดอายุ
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>

        <v-window-item value="issue">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
              ประวัติการเบิกยาจากคลัง สามารถพิมพ์ย้อนหลังและตรวจสอบรายการได้
            </v-alert>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>เลขรายการ</th>
                  <th>ผู้บันทึก</th>
                  <th>วันที่เวลา</th>
                  <th>จำนวนรายการ</th>
                  <th>มูลค่า</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="movement in issueHistory" :key="movement.id">
                  <td>{{ movement.movement_no }}</td>
                  <td>{{ movement.user?.name || '-' }}</td>
                  <td>{{ formatDate(movement.movement_at) }}</td>
                  <td>{{ movement.total_qty }}</td>
                  <td>{{ formatCurrency(movement.total_value) }}</td>
                  <td>
                    <v-btn size="small" variant="text" color="primary">พิมพ์</v-btn>
                  </td>
                </tr>
                <tr v-if="!issueHistory.length">
                  <td colspan="6" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีประวัติการเบิก
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>

        <v-window-item value="receive">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
              ประวัติการรับเข้าคลังย้อนหลังจากรอบเบิกยาและรับสินค้าจริง
            </v-alert>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>เลขรายการ</th>
                  <th>ผู้บันทึก</th>
                  <th>วันที่เวลา</th>
                  <th>จำนวนรายการ</th>
                  <th>รับเข้า (หน่วย)</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="movement in receiveHistory" :key="movement.id">
                  <td>{{ movement.movement_no }}</td>
                  <td>{{ movement.user?.name || '-' }}</td>
                  <td>{{ formatDate(movement.movement_at) }}</td>
                  <td>{{ movement.total_qty }}</td>
                  <td>{{ movement.total_received_qty ?? movement.total_qty }}</td>
                  <td>{{ movement.status }}</td>
                </tr>
                <tr v-if="!receiveHistory.length">
                  <td colspan="6" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีประวัติรับเข้าคลัง
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>

        <v-window-item value="adjust">
          <v-card-text class="ds-warehouse-tab">
            <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
              ประวัติตัด stock และการปรับยอดยาที่บันทึกจากการใช้งานจริง
            </v-alert>
            <v-table class="ds-warehouse-table">
              <thead>
                <tr>
                  <th>เลขรายการ</th>
                  <th>ผู้บันทึก</th>
                  <th>วันที่เวลา</th>
                  <th>จำนวนรายการ</th>
                  <th>จำนวนตัดรวม</th>
                  <th>มูลค่า</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="movement in adjustHistory" :key="movement.id">
                  <td>{{ movement.movement_no }}</td>
                  <td>{{ movement.user?.name || '-' }}</td>
                  <td>{{ formatDate(movement.movement_at) }}</td>
                  <td>{{ movement.total_qty }}</td>
                  <td>{{ movement.total_issued_qty ?? movement.total_qty }}</td>
                  <td>{{ formatCurrency(movement.total_value) }}</td>
                  <td>
                    <v-btn size="small" variant="text" color="primary">ดู</v-btn>
                  </td>
                </tr>
                <tr v-if="!adjustHistory.length">
                  <td colspan="7" class="text-center text-medium-emphasis py-8">
                    ยังไม่มีประวัติตัด stock
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-window-item>
      </v-window>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import KpiCard from '@/components/KpiCard.vue'
import { formatDate } from '@/composables/useFormatters'

const route = useRoute()
const router = useRouter()
const warehouseId = route.params.id

const expiryWindowDays = 90
const warehouse = ref(null)
const summary = ref({})
const currentLots = ref([])
const histories = ref([])
const drugOptions = ref([])
const tab = ref('current')
const saving = ref(false)
const activeAction = ref(null)

const receiveReasons = ['รับเข้าคลัง', 'รับยาคืน', 'ปรับยอดรับเข้า']
const issueReasons = ['เบิกใช้', 'แจกจ่าย', 'ส่งต่อ']
const directionItems = [
  { title: 'ลดจำนวน', value: 'decrease' },
  { title: 'เพิ่มจำนวน', value: 'increase' },
]

const dialogs = reactive({ receive: false, issue: false, adjust: false })
const receiveForm = reactive({
  drug_id: null,
  lot_no: '',
  quantity: 1,
  unit_cost: 0,
  expiry_date: '',
  received_date: '',
  reason_type: 'รับเข้าคลัง',
  note: '',
  reason: '',
})
const issueForm = reactive({ drug_id: null, quantity: 1, reason_type: 'เบิกใช้', note: '', reason: '' })
const adjustForm = reactive({
  drug_id: null,
  lot_no: '',
  quantity: 1,
  expiry_date: '',
  direction: 'decrease',
  note: '',
})

const nearExpiryLots = computed(() => {
  const threshold = new Date()
  threshold.setDate(threshold.getDate() + expiryWindowDays)

  return currentLots.value.filter((lot) => {
    if (!lot.expiry_date || !lot.qty_on_hand) return false
    const expiry = new Date(lot.expiry_date)
    return !Number.isNaN(expiry.getTime()) && expiry <= threshold
  })
})

const lotRows = computed(() => {
  return [...currentLots.value].sort((left, right) => {
    const leftExpiry = left.expiry_date ? new Date(left.expiry_date).getTime() : Number.POSITIVE_INFINITY
    const rightExpiry = right.expiry_date ? new Date(right.expiry_date).getTime() : Number.POSITIVE_INFINITY
    return leftExpiry - rightExpiry
  })
})

const issueHistory = computed(() => histories.value.filter((item) => item.type === 'issue'))
const receiveHistory = computed(() => histories.value.filter((item) => item.type === 'receive'))
const adjustHistory = computed(() => histories.value.filter((item) => item.type === 'adjust'))

const actionMeta = computed(() => {
  const map = {
    receive: {
      title: 'เบิกยาเข้าคลังย่อย',
      shortTitle: 'เบิกยา',
      description: 'ปรับรายการยาที่มีอยู่ในคลังแบบรวดเร็ว จากนั้นกดบันทึกเพื่อส่งเข้าระบบจริง',
      icon: 'mdi-arrow-down-bold-box-outline',
      alertType: 'info',
      cardColor: 'blue',
    },
    issue: {
      title: 'ตัด stock',
      shortTitle: 'ตัด stock',
      description: 'เลือกยาและจำนวนที่ต้องการตัดออกจากคลัง แล้วบันทึกเหตุผลให้ชัดเจน',
      icon: 'mdi-scissors-cutting',
      alertType: 'error',
      cardColor: 'red',
    },
    adjust: {
      title: 'เบิกเข้าคลังย่อย',
      shortTitle: 'เบิกเข้าคลัง',
      description: 'ใช้สำหรับบันทึกการเคลื่อนย้ายเข้าคลังย่อยและแก้ไขยอดยาในรอบเดียว',
      icon: 'mdi-clipboard-text-outline',
      alertType: 'warning',
      cardColor: 'amber',
    },
  }

  return map[activeAction.value] || map.receive
})

function goBack() {
  router.push('/warehouses')
}

function openAction(action) {
  activeAction.value = action
  tab.value = 'current'
}

function cancelAction() {
  activeAction.value = null
}

function resetAction() {
  if (activeAction.value === 'receive') {
    Object.assign(receiveForm, {
      drug_id: null,
      lot_no: '',
      quantity: 1,
      unit_cost: 0,
      expiry_date: '',
      received_date: '',
      reason_type: 'รับเข้าคลัง',
      note: '',
      reason: '',
    })
  } else if (activeAction.value === 'issue') {
    Object.assign(issueForm, { drug_id: null, quantity: 1, reason_type: 'เบิกใช้', note: '', reason: '' })
  } else if (activeAction.value === 'adjust') {
    Object.assign(adjustForm, {
      drug_id: null,
      lot_no: '',
      quantity: 1,
      expiry_date: '',
      direction: 'decrease',
      note: '',
    })
  }
}

function formatCurrency(value) {
  const number = Number(value || 0)
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(number)
}

async function loadWarehouse() {
  const [{ data: warehouseResp }, { data: stocksResp }, { data: historyResp }, { data: drugResp }] = await Promise.all([
    api.get(`/drugstock/warehouses/${warehouseId}`),
    api.get('/drugstock/stocks/current', { params: { warehouse_id: warehouseId, per_page: 200 } }),
    api.get('/drugstock/stocks/history', { params: { warehouse_id: warehouseId, per_page: 200 } }),
    api.get('/drugstock/drugs', { params: { per_page: 500, active_only: 1 } }),
  ])

  warehouse.value = warehouseResp.data
  const stockRows = stocksResp.data.data || []
  const historyRows = historyResp.data.data || []
  histories.value = historyRows
  summary.value = {
    drug_count: stockRows.length,
    total_qty: stockRows.reduce((sum, item) => sum + Number(item.qty_on_hand || 0), 0),
    issued_qty: historyRows.filter((item) => item.type === 'issue').reduce((sum, item) => sum + Number(item.total_qty || 0), 0),
    near_expiry: stockRows.flatMap((item) => item.lots || []).filter((lot) => {
      if (!lot.expiry_date || !lot.qty_on_hand) return false
      const expiry = new Date(lot.expiry_date)
      const threshold = new Date()
      threshold.setDate(threshold.getDate() + expiryWindowDays)
      return !Number.isNaN(expiry.getTime()) && expiry <= threshold
    }).length,
    alerts: stockRows.flatMap((item) => item.lots || []).filter((lot) => {
      if (!lot.expiry_date || !lot.qty_on_hand) return false
      const expiry = new Date(lot.expiry_date)
      const threshold = new Date()
      threshold.setDate(threshold.getDate() + expiryWindowDays)
      return !Number.isNaN(expiry.getTime()) && expiry <= threshold
    }).length,
  }
  currentLots.value = stockRows.flatMap((item) => item.lots || [])
  drugOptions.value = (drugResp.data.data.data || []).map((item) => ({
    title: item.name,
    value: item.id,
  }))
}

async function submitReceive() {
  saving.value = true
  try {
    await api.post('/drugstock/stocks/receive', {
      warehouse_id: warehouseId,
      note: receiveForm.note || receiveForm.reason_type,
      items: [receiveForm],
    })
    activeAction.value = null
    await loadWarehouse()
  } finally {
    saving.value = false
  }
}

async function submitIssue() {
  saving.value = true
  try {
    await api.post('/drugstock/stocks/issue', {
      warehouse_id: warehouseId,
      note: issueForm.note || issueForm.reason_type,
      items: [issueForm],
    })
    activeAction.value = null
    await loadWarehouse()
  } finally {
    saving.value = false
  }
}

async function submitAdjust() {
  saving.value = true
  try {
    await api.post('/drugstock/stocks/adjust', {
      warehouse_id: warehouseId,
      note: adjustForm.note,
      items: [adjustForm],
    })
    activeAction.value = null
    await loadWarehouse()
  } finally {
    saving.value = false
  }
}

async function submitAction() {
  if (activeAction.value === 'receive') return submitReceive()
  if (activeAction.value === 'issue') return submitIssue()
  if (activeAction.value === 'adjust') return submitAdjust()
}

onMounted(loadWarehouse)
</script>

<style scoped>
.ds-warehouse-hero {
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.ds-warehouse-action {
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
}

.ds-warehouse-tabs {
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
  overflow: hidden;
}

.ds-warehouse-tabs__bar {
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(255, 255, 255, 0.98));
}

.ds-warehouse-tab {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
}

.ds-warehouse-table {
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 18px;
  overflow: hidden;
}

.ds-warehouse-table :deep(th) {
  background: rgba(241, 245, 249, 0.96);
  color: #334155;
  font-weight: 800;
}

.ds-warehouse-table :deep(td) {
  border-bottom: 1px solid rgba(226, 232, 240, 0.9);
}

.ds-warehouse-table :deep(tbody tr:hover td) {
  background: rgba(239, 246, 255, 0.58);
}
</style>
