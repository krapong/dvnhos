<template>
  <div>
    <v-card class="mb-4 page-hero ds-crud-hero" :class="`ds-crud-hero--${tone}`" rounded="xl" elevation="0">
      <v-card-text class="d-flex flex-column flex-md-row align-center justify-space-between ga-4">
        <div class="d-flex align-center ga-3">
          <v-avatar :color="tone" variant="tonal" size="44" rounded="lg" class="ds-crud-hero__icon">
            <v-icon :icon="icon" :color="tone" size="22" />
          </v-avatar>
          <div>
            <div class="text-overline text-medium-emphasis">{{ eyebrow }}</div>
            <div class="text-h5 font-weight-bold">{{ title }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ subtitle }}</div>
          </div>
        </div>
        <div class="d-flex ga-2">
          <v-text-field
            v-model="search"
            density="comfortable"
            hide-details
            variant="outlined"
            label="ค้นหา"
            prepend-inner-icon="mdi-magnify"
            style="min-width: 260px"
          />
          <v-btn :color="tone" size="large" prepend-icon="mdi-plus" class="ds-crud-create-btn" @click="openCreate">เพิ่ม</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-card rounded="xl" elevation="0" class="ds-crud-panel" :class="`ds-crud-panel--${tone}`">
      <v-card-text>
        <v-data-table-server
          :headers="headers"
          :items="items"
          :loading="loading"
          :items-per-page="pagination.per_page"
          :page="pagination.current_page"
          :items-length="pagination.total"
          class="drugstock-table ds-crud-table"
          @update:options="onOptionsUpdate"
        >
          <template #item.actions="{ item }">
            <v-btn
              v-if="detailPath"
              size="small"
              variant="text"
              color="info"
              icon="mdi-open-in-new"
              class="ds-crud-action"
              @click="openDetail(resolveRow(item))"
            />
            <v-btn size="small" variant="text" color="primary" icon="mdi-pencil" class="ds-crud-action" @click="openEdit(resolveRow(item))" />
            <v-btn size="small" variant="text" color="error" icon="mdi-delete" class="ds-crud-action" @click="removeItem(resolveRow(item))" />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="760">
      <v-card rounded="xl">
        <v-card-title class="text-h6 font-weight-bold">
          {{ form.id ? `แก้ไข${title}` : `เพิ่ม${title}` }}
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col v-for="field in fields" :key="field.key" cols="12" :md="field.cols || 12">
              <v-text-field
                v-if="field.type === 'text' || field.type === 'number' || field.type === 'date'"
                v-model="form[field.key]"
                :label="field.label"
                :type="field.type === 'number' ? 'number' : field.type"
                variant="outlined"
                density="comfortable"
              />
              <v-textarea
                v-else-if="field.type === 'textarea'"
                v-model="form[field.key]"
                :label="field.label"
                variant="outlined"
                density="comfortable"
                rows="4"
              />
              <v-switch
                v-else-if="field.type === 'switch'"
                v-model="form[field.key]"
                :label="field.label"
                inset
              />
              <v-select
                v-else-if="field.type === 'select'"
                v-model="form[field.key]"
                :label="field.label"
                :items="field.items || []"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                clearable
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">ยกเลิก</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">บันทึก</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  eyebrow: { type: String, default: 'คลังข้อมูล' },
  endpoint: { type: String, required: true },
  fields: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  detailPath: { type: String, default: '' },
  icon: { type: String, default: 'mdi-folder-open-outline' },
  tone: { type: String, default: 'blue' },
})

const router = useRouter()
const items = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const search = ref('')
const pagination = reactive({ current_page: 1, per_page: 15, total: 0 })
const form = reactive({ id: null })

const headers = computed(() => [...props.columns, { title: 'จัดการ', key: 'actions', sortable: false }])

function blankForm() {
  const value = { id: null }
  props.fields.forEach((field) => {
    value[field.key] = field.type === 'switch' ? true : field.default ?? ''
  })
  return value
}

function fillForm(source) {
  const value = blankForm()
  value.id = source?.id || null
  props.fields.forEach((field) => {
    value[field.key] = source?.[field.key] ?? value[field.key]
  })
  return value
}

function resolveRow(item) {
  return item?.raw ?? item ?? {}
}

function openCreate() {
  Object.assign(form, fillForm())
  dialog.value = true
}

function openEdit(item) {
  Object.assign(form, fillForm(item))
  dialog.value = true
}

function openDetail(item) {
  if (!props.detailPath) return
  router.push(`${props.detailPath}/${item.id}`)
}

function onOptionsUpdate({ page, itemsPerPage, sortBy }) {
  pagination.current_page = page
  pagination.per_page = itemsPerPage
  // You might also want to handle sortBy here if needed
  fetchPage(page)
}

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get(props.endpoint, {
      params: { page, per_page: pagination.per_page, search: search.value || undefined },
    })
    const payload = data.data
    items.value = payload.data ?? payload.items?.data ?? []
    pagination.current_page = payload.current_page ?? payload.items?.current_page ?? 1
    pagination.per_page = payload.per_page ?? payload.items?.per_page ?? 15
    pagination.total = payload.total ?? payload.items?.total ?? items.value.length
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const payload = { ...form }
    delete payload.id
    if (form.id) {
      await api.put(`${props.endpoint}/${form.id}`, payload)
    } else {
      await api.post(props.endpoint, payload)
    }
    dialog.value = false
    await fetchPage(pagination.current_page)
  } finally {
    saving.value = false
  }
}

async function removeItem(item) {
  if (!window.confirm(`ต้องการลบรายการ "${item.name || item.code}" ใช่หรือไม่`)) {
    return
  }
  await api.delete(`${props.endpoint}/${item.id}`)
  await fetchPage(pagination.current_page)
}

watch(search, () => fetchPage(1))
onMounted(() => fetchPage())
</script>

<style scoped>
.ds-crud-hero {
  overflow: hidden;
}

.ds-crud-hero__icon {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
}

.ds-crud-hero--blue {
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96));
}

.ds-crud-hero--green {
  background: linear-gradient(135deg, rgba(240, 253, 244, 0.98), rgba(255, 255, 255, 0.96));
}

.ds-crud-hero--orange {
  background: linear-gradient(135deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.96));
}

.ds-crud-hero--violet {
  background: linear-gradient(135deg, rgba(245, 243, 255, 0.98), rgba(255, 255, 255, 0.96));
}

.ds-crud-panel {
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
}

.ds-crud-panel--blue {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
}

.ds-crud-panel--green {
  background: linear-gradient(180deg, rgba(240, 253, 244, 0.42), rgba(255, 255, 255, 0.98));
}

.ds-crud-panel--orange {
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.42), rgba(255, 255, 255, 0.98));
}

.ds-crud-panel--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.42), rgba(255, 255, 255, 0.98));
}

.ds-crud-create-btn {
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.16);
}

.ds-crud-table :deep(thead th) {
  background: rgba(248, 250, 252, 0.96);
  color: #334155;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.ds-crud-table :deep(tbody tr:hover td) {
  background: rgba(239, 246, 255, 0.6);
}

.ds-crud-action {
  opacity: 0.92;
}
</style>
