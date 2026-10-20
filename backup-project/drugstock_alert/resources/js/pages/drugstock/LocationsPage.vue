<template>
  <CrudPage
    title="สถานที่"
    subtitle="จัดการสถานที่ใช้งานและจุดเก็บยาของระบบ"
    eyebrow="ข้อมูลหลัก"
    endpoint="/drugstock/locations"
    icon="mdi-map-marker"
    tone="orange"
    :columns="columns"
    :fields="fields"
  />
</template>

<script setup>
import CrudPage from '@/components/CrudPage.vue'

const columns = [
  { title: 'รหัส', key: 'code' },
  { title: 'ชื่อ', key: 'name' },
  { title: 'รายละเอียด', key: 'description' },
  { title: 'สถานะ', key: 'is_active' },
]

const fields = [
  { key: 'code', label: 'รหัส', type: 'text', cols: 6 },
  { key: 'name', label: 'ชื่อ', type: 'text', cols: 6 },
  { key: 'description', label: 'รายละเอียด', type: 'textarea' },
  { key: 'is_active', label: 'ใช้งาน', type: 'switch' },
]
</script>
