<template>
  <div>
    <v-card class="mb-4 page-hero ds-report-hero" rounded="xl" elevation="0">
      <v-card-text class="d-flex flex-column flex-md-row justify-space-between ga-4">
        <div class="d-flex align-center ga-3">
          <v-avatar color="violet-lighten-4" size="42" variant="tonal">
            <v-icon icon="mdi-file-chart-outline" color="#7c3aed" size="22" />
          </v-avatar>
          <div>
            <div class="text-overline text-medium-emphasis">รายงาน</div>
            <div class="text-h5 font-weight-bold">ศูนย์รายงานคลังยา</div>
          </div>
        </div>
        <div class="d-flex ga-2 flex-wrap">
          <v-btn variant="outlined" prepend-icon="mdi-file-excel" :loading="exporting" @click="exportReport('xlsx')">Excel</v-btn>
          <v-btn variant="outlined" prepend-icon="mdi-file-pdf-box" :loading="exporting" @click="exportReport('pdf')">PDF</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-row class="mb-4" dense>
      <v-col cols="12" md="4"><KpiCard title="แจ้งเตือนทั้งหมด" :value="summary.total_alerts || 0" subtitle="สถานะทั้งหมด" icon="mdi-bell-outline" color="primary" /></v-col>
      <v-col cols="12" md="4"><KpiCard title="ยังไม่แก้ไข" :value="summary.open_alerts || 0" subtitle="ค้างดำเนินการ" icon="mdi-alert-circle" color="amber" /></v-col>
      <v-col cols="12" md="4"><KpiCard title="แก้ไขแล้ว" :value="summary.resolved_alerts || 0" subtitle="ปิดงานเรียบร้อย" icon="mdi-check-circle" color="success" /></v-col>
    </v-row>

    <v-card rounded="xl" elevation="0" class="mb-4 ds-report-panel ds-report-panel--amber">
      <v-card-title class="font-weight-bold">ยาใกล้หมดอายุ / หมดอายุ</v-card-title>
      <v-card-text>
        <v-table class="ds-report-table">
          <thead>
            <tr>
              <th>ยา</th>
              <th>Lot</th>
              <th>คลัง</th>
              <th>คงเหลือ</th>
              <th>วันหมดอายุ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in expiryItems" :key="item.id">
              <td>{{ item.stock?.drug?.name || '-' }}</td>
              <td>{{ item.lot_no }}</td>
              <td>{{ item.stock?.warehouse?.name || '-' }}</td>
              <td>{{ item.qty_on_hand }}</td>
              <td>{{ item.expiry_date || '-' }}</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <v-card rounded="xl" elevation="0" class="ds-report-panel ds-report-panel--violet">
      <v-card-title class="font-weight-bold">รายการตัด Stock</v-card-title>
      <v-card-text>
        <v-table class="ds-report-table">
          <thead>
            <tr>
              <th>เลขที่</th>
              <th>คลัง</th>
              <th>ผู้บันทึก</th>
              <th>จำนวน</th>
              <th>วันที่</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in adjustmentItems" :key="item.id">
              <td>{{ item.movement_no }}</td>
              <td>{{ item.warehouse?.name || '-' }}</td>
              <td>{{ item.performer?.name || '-' }}</td>
              <td>{{ item.total_qty }}</td>
              <td>{{ formatDate(item.movement_at) }}</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import KpiCard from '@/components/KpiCard.vue'
import { formatDate } from '@/composables/useFormatters'

const summary = ref({})
const expiryItems = ref([])
const adjustmentItems = ref([])
const exporting = ref(false)

async function loadReports() {
  const [summaryResp, expiryResp, adjustResp] = await Promise.all([
    api.get('/drugstock/reports/alert-summary'),
    api.get('/drugstock/reports/expiry-summary', { params: { per_page: 10 } }),
    api.get('/drugstock/reports/adjustment-summary', { params: { per_page: 10 } }),
  ])

  summary.value = summaryResp.data.data || {}
  expiryItems.value = expiryResp.data.data.data || []
  adjustmentItems.value = adjustResp.data.data.data || []
}

async function exportReport(format) {
  exporting.value = true

  try {
    const response = await api.get('/drugstock/reports/export', {
      params: { format },
      responseType: 'blob',
    })

    const blob = new Blob([response.data], {
      type: format === 'pdf'
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `drugstock-report.${format}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

onMounted(loadReports)
</script>

<style scoped>
.ds-report-hero {
  background: linear-gradient(135deg, rgba(245, 243, 255, 0.98), rgba(255, 255, 255, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.ds-report-panel {
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
}

.ds-report-panel--amber {
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.48), rgba(255, 255, 255, 0.98));
}

.ds-report-panel--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.48), rgba(255, 255, 255, 0.98));
}

.ds-report-table :deep(th) {
  background: rgba(241, 245, 249, 0.95);
  color: #334155;
  font-weight: 800;
}
</style>
