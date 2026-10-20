<template>
  <CrudPage
    title="ยา"
    subtitle="จัดการ master ยา หน่วย และหมวดหมู่"
    eyebrow="ข้อมูลหลัก"
    endpoint="/drugstock/drugs"
    icon="mdi-pill"
    tone="green"
    :columns="columns"
    :fields="fields"
  />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import CrudPage from '@/components/CrudPage.vue'

const categories = ref([])
const units = ref([])

const columns = [
  { title: 'รหัส', key: 'code' },
  { title: 'ชื่อยา', key: 'name' },
  { title: 'หมวด', key: 'category.name' },
  { title: 'หน่วย', key: 'unit.name' },
  { title: 'จำนวน Stock', key: 'min_stock_qty' },
  { title: 'ใช้งาน', key: 'is_active' },
]

const fields = computed(() => [
  { key: 'code', label: 'รหัสยา', type: 'text', cols: 6 },
  { key: 'name', label: 'ชื่อยา', type: 'text', cols: 6 },
  { key: 'generic_name', label: 'ชื่อสามัญ', type: 'text', cols: 6 },
  { key: 'drug_category_id', label: 'หมวดหมู่', type: 'select', items: categories.value, cols: 6 },
  { key: 'unit_id', label: 'หน่วย', type: 'select', items: units.value, cols: 6 },
  { key: 'min_stock_qty', label: 'จำนวน Stock', type: 'number', cols: 6 },
  { key: 'description', label: 'รายละเอียด', type: 'textarea' },
  { key: 'is_controlled', label: 'ยาควบคุม', type: 'switch' },
  { key: 'is_active', label: 'ใช้งาน', type: 'switch' },
])

onMounted(async () => {
  const { data } = await api.get('/drugstock/drugs/options')
  const payload = data.data
  categories.value = payload.categories || []
  units.value = payload.units || []
})
</script>
