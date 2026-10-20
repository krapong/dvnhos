<template>
  <CrudPage
    title="คลังย่อย"
    subtitle="คลังย่อยหรือจุดเก็บยาที่เชื่อมกับสถานที่"
    eyebrow="ข้อมูลหลัก"
    endpoint="/drugstock/warehouses"
    detail-path="/warehouses"
    icon="mdi-warehouse"
    tone="blue"
    :columns="columns"
    :fields="fields"
  />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import CrudPage from '@/components/CrudPage.vue'

const locations = ref([])

const columns = [
  { title: 'รหัส', key: 'code' },
  { title: 'ชื่อ', key: 'name' },
  { title: 'สถานที่', key: 'location.name' },
  { title: 'สถานะ', key: 'is_active' },
]

const fields = computed(() => [
  { key: 'code', label: 'รหัสคลัง', type: 'text', cols: 6 },
  { key: 'name', label: 'ชื่อคลัง', type: 'text', cols: 6 },
  { key: 'location_id', label: 'สถานที่', type: 'select', items: locations.value },
  { key: 'description', label: 'รายละเอียด', type: 'textarea' },
  { key: 'is_active', label: 'ใช้งาน', type: 'switch' },
])

onMounted(async () => {
  const { data } = await api.get('/drugstock/locations', { params: { per_page: 100 } })
  locations.value = (data.data.data || []).map((item) => ({ title: item.name, value: item.id }))
})
</script>
