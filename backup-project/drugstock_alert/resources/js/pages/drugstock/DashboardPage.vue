<template>
  <div class="dashboard-page">
    <!-- Hero Section -->
    <v-card class="mb-4 dashboard-hero-premium" rounded="xl" elevation="0">
      <div class="hero-bg-glow"></div>
      <v-card-text class="d-flex align-center justify-space-between flex-wrap gap-4 pa-6 relative-z1">
        <div class="hero-content">
          <div class="text-subtitle-2 font-weight-bold text-primary mb-1 d-flex align-center">
            <v-icon icon="mdi-view-dashboard-outline" class="mr-2" size="18" /> 
            ภาพรวมระบบ (System Overview)
          </div>
          <div class="text-h4 font-weight-black text-slate-900 mb-2 hero-title">
            ระบบคลังยาและแจ้งเตือนสต็อก
          </div>
          <div class="text-body-2 text-slate-600 mb-0 max-w-2xl">
            ติดตามสถานะคลังย่อย ยาใกล้หมดอายุ และเหตุการณ์เคลื่อนไหวล่าสุดได้ในที่เดียวแบบเรียลไทม์ 
            พร้อมระบบวิเคราะห์แนวโน้มเพื่อการตัดสินใจที่แม่นยำยิ่งขึ้น
          </div>
        </div>
        
        <!-- Right Actions -->
        <div class="d-flex align-center gap-3 flex-wrap mt-4 mt-md-0">
          <v-select
            v-model="selectedFiscalYear"
            :items="fiscalYearOptions"
            item-title="label"
            item-value="value"
            variant="outlined"
            density="compact"
            hide-details
            bg-color="white"
            class="font-weight-bold text-primary"
            style="width: 200px"
            prepend-inner-icon="mdi-calendar-blank"
            @update:modelValue="fetchDashboardData"
          ></v-select>
          <v-btn color="primary" rounded="pill" elevation="2" to="/warehouses" prepend-icon="mdi-warehouse">
            จัดการคลังย่อย
          </v-btn>
          <v-btn variant="outlined" color="primary" rounded="pill" to="/alerts" prepend-icon="mdi-bell-outline" class="bg-white">
            ดูการแจ้งเตือนทั้งหมด
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <div class="text-h6 font-weight-black mb-4 text-slate-800 px-1 d-flex align-center gap-2">
      <v-icon icon="mdi-chart-arc" color="primary" /> สรุปข้อมูลสำคัญ (Key Metrics)
    </div>
    
    <!-- KPI Grid -->
    <v-row class="mb-6 dashboard-kpis" dense>
      <v-col v-for="card in metricCards" :key="card.title" cols="12" sm="6" lg="4">
        <KpiCard
          :title="card.title"
          :value="card.value"
          :subtitle="card.subtitle"
          :icon="card.icon"
          :color="card.color"
          :trend-icon="card.trendIcon"
          :trend-color="card.trendColor"
        />
      </v-col>
    </v-row>

    <v-row dense class="dashboard-panels">
      <!-- Main Content Area -->
      <v-col cols="12" lg="8">
        <v-card rounded="xl" elevation="0" class="h-100 premium-panel">
          <v-card-title class="pa-6 pb-2 d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-3">
              <div class="icon-box icon-box--rose"><v-icon icon="mdi-shield-alert" size="24" /></div>
              <span class="text-h6 font-weight-black text-slate-900">สรุปการแจ้งเตือน 4 ระยะ</span>
            </div>
            <v-chip color="rose" variant="tonal" size="small" class="font-weight-bold">
              ตรวจสอบประจำวัน
            </v-chip>
          </v-card-title>
          
          <v-card-text class="pa-6 pt-4">
            <div class="alert-stages mb-8">
               <div class="stage-card stage-normal">
                 <div class="stage-icon"><v-icon icon="mdi-check-circle" size="28" /></div>
                 <div class="stage-content">
                   <div class="stage-title">ปกติ (Normal)</div>
                   <div class="stage-desc">ยาในสต็อกมีเพียงพอ</div>
                 </div>
               </div>
               <div class="stage-card stage-warning">
                 <div class="stage-icon"><v-icon icon="mdi-alert" size="28" /></div>
                 <div class="stage-content">
                   <div class="stage-title">ใกล้หมด/หมดอายุ (Warning)</div>
                   <div class="stage-desc">ต้องเริ่มเฝ้าระวัง</div>
                 </div>
               </div>
               <div class="stage-card stage-orange">
                 <div class="stage-icon"><v-icon icon="mdi-cart-arrow-down" size="28" /></div>
                 <div class="stage-content">
                   <div class="stage-title">จุดสั่งซื้อ (Reorder)</div>
                   <div class="stage-desc">ต่ำกว่าเกณฑ์กำหนด</div>
                 </div>
               </div>
               <div class="stage-card stage-critical">
                 <div class="stage-icon"><v-icon icon="mdi-close-octagon" size="28" /></div>
                 <div class="stage-content">
                   <div class="stage-title">วิกฤต (Critical)</div>
                   <div class="stage-desc">สต็อกขาด/หมดอายุ</div>
                 </div>
               </div>
             </div>

             <div class="d-flex align-center justify-space-between mt-8 mb-4">
               <div class="text-subtitle-1 font-weight-black text-slate-800">
                 รายการแจ้งเตือนล่าสุด
               </div>
               <v-btn variant="text" color="primary" size="small" to="/alerts">ดูทั้งหมด <v-icon icon="mdi-chevron-right" right /></v-btn>
             </div>

            <v-table class="dashboard-table premium-table">
              <thead>
                <tr>
                  <th>ประเภท</th>
                  <th>ชื่อยา / เวชภัณฑ์</th>
                  <th>คลังย่อย</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in alerts" :key="item.id">
                  <td>
                    <v-chip size="small" :color="alertTypeColor(item.notification_type)" variant="tonal" class="font-weight-bold">
                      {{ item.notification_type }}
                    </v-chip>
                  </td>
                  <td class="font-weight-bold text-slate-800">{{ item.drug?.name || '-' }}</td>
                  <td class="text-slate-600">{{ item.warehouse?.name || '-' }}</td>
                  <td>
                    <v-chip
                      size="small"
                      :color="item.status === 'resolved' ? 'success' : 'warning'"
                      variant="flat"
                      class="text-white font-weight-bold"
                    >
                      <v-icon :icon="item.status === 'resolved' ? 'mdi-check' : 'mdi-clock-outline'" size="14" class="mr-1"/>
                      {{ item.status === 'resolved' ? 'แก้ไขแล้ว' : 'ยังไม่แก้ไข' }}
                    </v-chip>
                  </td>
                </tr>
                <tr v-if="!alerts.length">
                  <td colspan="4" class="text-center text-medium-emphasis py-12">
                    <v-icon icon="mdi-check-decagram" size="40" color="success" class="mb-2 opacity-50" /><br>
                    ยังไม่มีการแจ้งเตือนในขณะนี้ ยอดเยี่ยมมาก!
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Sidebar Area -->
      <v-col cols="12" lg="4">
        <v-card rounded="xl" elevation="0" class="h-100 premium-panel bg-slate-50 d-flex flex-column">
          <v-card-title class="pa-6 pb-2 d-flex align-center gap-3">
            <div class="icon-box icon-box--teal"><v-icon icon="mdi-chart-timeline-variant" size="24" /></div>
            <span class="text-h6 font-weight-black text-slate-900">แนวโน้มย้อนหลัง 30 วัน</span>
          </v-card-title>
          
          <v-card-text class="pa-6 pt-4 flex-grow-0">
            <!-- Mock Chart Area (Aesthetics) -->
            <div class="mock-chart-container mb-6">
              <div class="mock-chart">
                <div class="chart-bar" style="height: 35%"></div>
                <div class="chart-bar" style="height: 50%"></div>
                <div class="chart-bar" style="height: 20%"></div>
                <div class="chart-bar" style="height: 65%"></div>
                <div class="chart-bar chart-bar--warning" style="height: 85%"></div>
                <div class="chart-bar" style="height: 40%"></div>
                <div class="chart-bar chart-bar--active" style="height: 60%">
                  <div class="chart-tooltip">วันนี้</div>
                </div>
              </div>
              <div class="chart-labels">
                <span>ต้นเดือน</span>
                <span>กลางเดือน</span>
                <span>ปัจจุบัน</span>
              </div>
            </div>
          </v-card-text>
          
          <v-divider class="mx-6 border-slate-200"></v-divider>

          <v-card-title class="pa-6 pb-2 d-flex align-center justify-space-between mt-2">
            <div class="d-flex align-center gap-3">
              <div class="icon-box icon-box--indigo"><v-icon icon="mdi-history" size="20" /></div>
              <span class="text-subtitle-1 font-weight-black text-slate-900">เคลื่อนไหวล่าสุด</span>
            </div>
          </v-card-title>

          <v-card-text class="pa-6 pt-2 flex-grow-1">
            <v-list density="compact" class="bg-transparent pa-0 premium-list">
              <v-list-item
                v-for="movement in movements"
                :key="movement.id"
                class="premium-list__item mb-3 pa-3"
              >
                <template #prepend>
                  <v-avatar size="42" :color="movementTypeColor(movement.type)" variant="flat" class="text-white shadow-sm mr-3">
                    <v-icon :icon="movementTypeIcon(movement.type)" size="20" />
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold text-slate-800 mb-1">
                  {{ movement.movement_no }}
                </v-list-item-title>
                <v-list-item-subtitle class="text-slate-500 d-flex align-center gap-1">
                  <v-chip size="x-small" :color="movementTypeColor(movement.type)" variant="outlined" class="mr-1">
                    {{ movement.type }}
                  </v-chip>
                  {{ movement.warehouse?.name || '-' }}
                </v-list-item-subtitle>
              </v-list-item>
              <div v-if="!movements.length" class="text-center py-8 text-slate-400">
                ไม่มีรายการเคลื่อนไหว
              </div>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import KpiCard from '@/components/KpiCard.vue'

const metrics = ref({})
const alerts = ref([])
const movements = ref([])

const selectedFiscalYear = ref(null)
const fiscalYearOptions = ref([])

const generateFiscalYearOptions = () => {
  const options = []
  const now = new Date()
  let currentFy = now.getFullYear()
  // Fiscal year starts in October (month index 9)
  if (now.getMonth() >= 9) {
    currentFy += 1
  }
  
  selectedFiscalYear.value = currentFy
  
  for (let i = 0; i < 4; i++) {
    const fy = currentFy - i
    const thaiYear = fy + 543
    options.push({
      label: `ปีงบประมาณ ${thaiYear}`,
      value: fy
    })
  }
  fiscalYearOptions.value = options
}

const metricCards = computed(() => [
  {
    title: 'จำนวนคลังย่อย',
    value: metrics.value.warehouse_count ?? 0,
    subtitle: 'คลังที่เปิดใช้งานทั้งหมด',
    icon: 'mdi-warehouse',
    color: 'primary',
    trendIcon: 'mdi-check-circle',
    trendColor: 'success',
  },
  {
    title: 'รายการยาทั้งหมด',
    value: metrics.value.drug_count ?? 0,
    subtitle: 'รายการเวชภัณฑ์ในระบบ',
    icon: 'mdi-pill',
    color: 'success',
    trendIcon: 'mdi-information',
    trendColor: 'success',
  },
  {
    title: 'ยาใกล้หมดอายุ',
    value: metrics.value.near_expiry_count ?? 0,
    subtitle: 'ล็อตที่ต้องติดตามด่วน',
    icon: 'mdi-alert-circle',
    color: 'amber',
    trendIcon: 'mdi-alert',
    trendColor: 'warning',
  },
  {
    title: 'ยาหมดอายุแล้ว',
    value: metrics.value.expired_count ?? 0,
    subtitle: 'รอการตัดจำหน่าย (Stock)',
    icon: 'mdi-close-circle',
    color: 'red',
    trendIcon: 'mdi-close',
    trendColor: 'error',
  },
  {
    title: 'เคลื่อนไหว (ปีงบฯ)',
    value: metrics.value.movement_in_fy ?? 0,
    subtitle: 'รับเข้า / เบิก / ปรับปรุง',
    icon: 'mdi-sync',
    color: 'teal',
    trendIcon: 'mdi-arrow-right',
    trendColor: 'teal',
  },
  {
    title: 'ต่ำกว่าจุดสั่งซื้อ',
    value: metrics.value.low_stock_count ?? 0,
    subtitle: 'ถึงเกณฑ์ Reorder Point',
    icon: 'mdi-chart-box-outline',
    color: 'orange',
    trendIcon: 'mdi-arrow-down',
    trendColor: 'orange',
  },
])

const alertTypeColor = (type) => {
  const normalized = String(type || '').toLowerCase()
  if (normalized.includes('expired')) return 'red'
  if (normalized.includes('near')) return 'amber'
  if (normalized.includes('low')) return 'orange'
  if (normalized.includes('reorder')) return 'purple'
  return 'primary'
}

const movementTypeColor = (type) => {
  const normalized = String(type || '').toLowerCase()
  if (normalized.includes('receive') || normalized.includes('in')) return 'success'
  if (normalized.includes('issue') || normalized.includes('out')) return 'warning'
  if (normalized.includes('adjust') || normalized.includes('stock')) return 'primary'
  return 'teal'
}

const movementTypeIcon = (type) => {
  const normalized = String(type || '').toLowerCase()
  if (normalized.includes('receive') || normalized.includes('in')) return 'mdi-arrow-down-bold'
  if (normalized.includes('issue') || normalized.includes('out')) return 'mdi-arrow-up-bold'
  if (normalized.includes('adjust') || normalized.includes('stock')) return 'mdi-scissors-cutting'
  return 'mdi-sync'
}

const fetchDashboardData = async () => {
  try {
    const { data } = await api.get('/drugstock/dashboard', {
      params: { fiscal_year: selectedFiscalYear.value }
    })
    const payload = data.data
    metrics.value = payload.metrics || {}
    alerts.value = payload.alerts || []
    movements.value = payload.recent_movements || []
  } catch (err) {
    console.error('Failed to load dashboard data', err)
  }
}

onMounted(() => {
  generateFiscalYearOptions()
  fetchDashboardData()
})
</script>

<style scoped>
/* Color Palette Extensions */
.text-slate-900 { color: #0f172a; }
.text-slate-800 { color: #1e293b; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-200 { border-color: #e2e8f0; }

.bg-blue-light { background-color: #eff6ff; }
.text-blue { color: #2563eb; }
.bg-amber-light { background-color: #fffbeb; }
.text-amber { color: #d97706; }

.dashboard-page {
  display: flex;
  flex-direction: column;
}

/* Premium Hero Section */
.dashboard-hero-premium {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(148, 163, 184, 0.2);
  box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
}

.hero-bg-glow {
  position: absolute;
  top: -100px;
  right: -100px;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.relative-z1 {
  position: relative;
  z-index: 1;
}

.hero-title {
  letter-spacing: -0.02em;
  font-size: clamp(1.5rem, 3vw, 2.125rem) !important;
  line-height: 1.2;
}

.max-w-2xl {
  max-width: 42rem;
  line-height: 1.6;
}

/* Icons & Panels */
.icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.icon-box--rose { background: linear-gradient(135deg, #f43f5e, #e11d48); box-shadow: 0 6px 16px rgba(225, 29, 72, 0.25); }
.icon-box--teal { background: linear-gradient(135deg, #14b8a6, #0d9488); box-shadow: 0 6px 16px rgba(13, 148, 136, 0.25); }
.icon-box--indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25); }

.premium-panel {
  border: 1px solid rgba(148, 163, 184, 0.15);
  box-shadow: 0 16px 32px -12px rgba(15, 23, 42, 0.06);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.premium-panel:hover {
  box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.08);
}

/* Alert 4 Stages UI */
.alert-stages {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
}
.stage-card {
  padding: 16px;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}
.stage-card:hover {
  transform: translateY(-4px);
}
.stage-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stage-title {
  font-weight: 800;
  font-size: 15px;
  margin-bottom: 2px;
  color: #0f172a;
}
.stage-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.4;
}

.stage-normal {
  background: #f0fdf4;
  border-color: #bbf7d0;
}
.stage-normal .stage-icon { background: #dcfce7; color: #16a34a; }

.stage-warning {
  background: #fefce8;
  border-color: #fef08a;
}
.stage-warning .stage-icon { background: #fef08a; color: #ca8a04; }

.stage-orange {
  background: #fff7ed;
  border-color: #fed7aa;
}
.stage-orange .stage-icon { background: #ffedd5; color: #ea580c; }

.stage-critical {
  background: #fff1f2;
  border-color: #fecdd3;
}
.stage-critical .stage-icon { background: #ffe4e6; color: #e11d48; }

/* Premium Table */
.premium-table {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.8);
}
.premium-table :deep(th) {
  background: #f8fafc !important;
  color: #475569 !important;
  font-weight: 800 !important;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 12px;
  padding-top: 12px !important;
  padding-bottom: 12px !important;
}
.premium-table :deep(td) {
  padding-top: 14px !important;
  padding-bottom: 14px !important;
  border-bottom: 1px solid #f1f5f9;
}
.premium-table :deep(tr:hover td) {
  background: #f8fafc;
}

/* Mock Chart */
.mock-chart-container {
  padding: 16px;
  background: white;
  border-radius: 20px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.mock-chart {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 120px;
  padding-bottom: 8px;
  border-bottom: 2px dashed #e2e8f0;
}
.chart-bar {
  width: 12%;
  background: linear-gradient(180deg, #93c5fd, #3b82f6);
  border-radius: 6px 6px 0 0;
  position: relative;
  transition: height 1s ease;
}
.chart-bar:hover {
  filter: brightness(1.1);
}
.chart-bar--warning {
  background: linear-gradient(180deg, #fca5a5, #ef4444);
}
.chart-bar--active {
  background: linear-gradient(180deg, #67e8f9, #06b6d4);
}
.chart-tooltip {
  position: absolute;
  top: -30px;
  left: 50%;
  transform: translateX(-50%);
  background: #1e293b;
  color: white;
  font-size: 11px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
}
.chart-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 12px;
  color: #94a3b8;
  font-weight: 600;
}

/* Premium List */
.premium-list__item {
  background: white;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px !important;
  transition: all 0.2s ease;
}
.premium-list__item:hover {
  border-color: #cbd5e1;
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
</style>
