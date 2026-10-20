<template>
  <div>
    <v-card class="mb-4 page-hero ds-settings-hero" rounded="xl" elevation="0">
      <v-card-text class="d-flex flex-column flex-md-row justify-space-between ga-4 ds-settings-hero__content">
        <div class="d-flex flex-column">
          <div class="d-flex align-center ga-3 mb-2">
            <v-avatar color="cyan-lighten-4" size="42" variant="tonal" class="ds-hero-icon ds-hero-icon--cyan">
              <v-icon icon="mdi-cog-outline" color="#0891b2" size="22" />
            </v-avatar>
            <div class="text-overline text-medium-emphasis">การตั้งค่า</div>
          </div>
          <div class="text-h5 font-weight-bold">จัดการระบบ สิทธิ์ และ Telegram</div>
          <div class="text-body-2 text-medium-emphasis">
            รวมการตั้งค่าระบบ บทบาท และการแจ้งเตือนออก Telegram ไว้ในหน้าเดียว
          </div>
        </div>
        <div class="d-flex ga-2 flex-wrap">
          <v-btn
            variant="outlined"
            prepend-icon="mdi-send"
            color="info"
            :loading="testing"
            @click="testTelegram"
          >
            ทดสอบ Telegram
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-content-save" :loading="saving" @click="save">
            บันทึกการตั้งค่า
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-alert v-if="message" class="mb-4" variant="tonal" type="info" rounded="xl">
      {{ message }}
    </v-alert>

    <v-card rounded="xl" elevation="0" class="mb-4 ds-settings-panel ds-settings-panel--blue">
        <v-tabs v-model="tab" color="primary" class="px-4">
          <v-tab value="general">ข้อมูลระบบ</v-tab>
          <v-tab value="users">ผู้ใช้งาน</v-tab>
          <v-tab value="access">บทบาท/สิทธิ์</v-tab>
          <v-tab value="stock">นโยบายคลัง</v-tab>
          <v-tab value="telegram">Telegram</v-tab>
        </v-tabs>
      <v-divider />

      <v-card-text v-if="tab === 'general'">
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.general.hospital_name" label="ชื่อโรงพยาบาล" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.general.app_name" label="ชื่อระบบ" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12">
            <v-textarea v-model="form.general.footer_text" label="ข้อความท้ายหน้า" variant="outlined" density="comfortable" rows="3" />
          </v-col>
        </v-row>
      </v-card-text>

      <div v-else-if="tab === 'users'" class="pa-4">
        <UsersManagement />
      </div>

      <v-card-text v-else-if="tab === 'access'">
        <v-row class="mb-4">
          <v-col cols="12" md="4">
            <v-card variant="tonal" color="primary" rounded="lg" class="ds-settings-mini ds-settings-mini--blue">
              <v-card-text>
                <div class="text-caption text-medium-emphasis">จำนวนบทบาท</div>
                <div class="text-h4 font-weight-bold">{{ accessSummary.roleCount }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card variant="tonal" color="secondary" rounded="lg" class="ds-settings-mini ds-settings-mini--violet">
              <v-card-text>
                <div class="text-caption text-medium-emphasis">จำนวนสิทธิ์ทั้งหมด</div>
                <div class="text-h4 font-weight-bold">{{ accessSummary.permissionCount }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card variant="tonal" color="success" rounded="lg" class="ds-settings-mini ds-settings-mini--green">
              <v-card-text>
                <div class="text-caption text-medium-emphasis">บทบาทสมัครสมาชิก</div>
                <div class="text-h6 font-weight-bold">{{ accessSummary.defaultRole || '-' }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="mb-4" variant="tonal" rounded="lg">
          <v-card-text>
            <v-select
              v-model="form.access.default_role"
              :items="roleItems"
              item-title="title"
              item-value="value"
              label="บทบาทเริ่มต้นเมื่อสมัครสมาชิก"
              variant="outlined"
              density="comfortable"
            />
          </v-card-text>
        </v-card>

        <v-list lines="two" rounded="lg" density="comfortable">
          <v-list-subheader>บทบาทในระบบ</v-list-subheader>
          <v-list-item
            v-for="role in form.access.roles"
            :key="role.id"
            :title="role.name"
            :subtitle="`สิทธิ์ ${role.permissions_count || 0} รายการ`"
            prepend-icon="mdi-shield-account-outline"
            prepend-icon-color="success"
          />
        </v-list>
      </v-card-text>

      <v-card-text v-else-if="tab === 'stock'">
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field v-model="form.stock_policy.low_stock_threshold" label="เกณฑ์คงเหลือต่ำ" type="number" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field v-model="form.stock_policy.expiry_alert_days" label="แจ้งเตือนใกล้หมดอายุ (วัน)" type="number" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select v-model="form.stock_policy.default_allocation_rule" :items="allocationItems" label="วิธีตัด stock เริ่มต้น" variant="outlined" density="comfortable" />
          </v-col>
        </v-row>
        <v-alert class="mt-2" type="info" variant="tonal" density="comfortable">
          ค่านี้มีผลกับ dashboard, การเบิกจ่าย และการแจ้งเตือนอัตโนมัติของระบบ
        </v-alert>
      </v-card-text>

      <v-card-text v-else>
        <v-row>
          <v-col cols="12" md="4">
            <v-select v-model="form.telegram.enabled" :items="enabledItems" label="เปิดใช้งาน" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12" md="8">
            <v-text-field v-model="form.telegram.base_url" label="Base URL" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.telegram.bot_token"
              label="Bot Token"
              type="password"
              placeholder="เว้นว่างเพื่อคงค่าเดิม"
              variant="outlined"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.telegram.chat_id" label="Chat ID" variant="outlined" density="comfortable" />
          </v-col>
        </v-row>
        <v-alert class="mt-2" type="info" variant="tonal" density="comfortable">
          สถานะ: {{ form.telegram.configured ? 'พร้อมใช้งาน' : 'ยังไม่พร้อมใช้งาน' }} | หากไม่ต้องการเปลี่ยน Bot Token ให้เว้นว่างไว้
        </v-alert>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '@/services/api'
import UsersManagement from '@/components/UsersManagement.vue'

const tab = ref('general')
const saving = ref(false)
const testing = ref(false)
const message = ref('')
const enabledItems = [
  { title: 'เปิดใช้งาน', value: '1' },
  { title: 'ปิดใช้งาน', value: '0' },
]
const form = reactive({
  general: {
    hospital_name: '',
    app_name: '',
    footer_text: '',
  },
  access: {
    roles: [],
    permissions_count: 0,
    default_role: '',
  },
  stock_policy: {
    low_stock_threshold: 10,
    expiry_alert_days: 90,
    default_allocation_rule: 'FEFO',
  },
  telegram: {
    enabled: '0',
    bot_token: '',
    chat_id: '',
    base_url: '',
    configured: false,
  },
})

async function loadSettings() {
  const { data } = await api.get('/drugstock/settings')
  const payload = data?.data || {}
  form.general = {
    hospital_name: payload.general?.hospital_name || '',
    app_name: payload.general?.app_name || '',
    footer_text: payload.general?.footer_text || '',
  }
  form.access = {
    roles: Array.isArray(payload.access?.roles) ? payload.access.roles : [],
    permissions_count: Number(payload.access?.permissions_count || 0),
    default_role: payload.access?.default_role || '',
  }
  form.stock_policy = {
    low_stock_threshold: Number(payload.stock_policy?.low_stock_threshold || 10),
    expiry_alert_days: Number(payload.stock_policy?.expiry_alert_days || 90),
    default_allocation_rule: payload.stock_policy?.default_allocation_rule || 'FEFO',
  }
  form.telegram = {
    enabled: payload.telegram?.enabled ? '1' : '0',
    bot_token: '',
    chat_id: payload.telegram?.chat_id || '',
    base_url: payload.telegram?.base_url || '',
    configured: !!payload.telegram?.configured,
  }
}

const accessSummary = computed(() => ({
  roleCount: form.access.roles.length,
  permissionCount: form.access.permissions_count,
  defaultRole: form.access.default_role,
}))

const allocationItems = [
  { title: 'FEFO', value: 'FEFO' },
  { title: 'FIFO', value: 'FIFO' },
]

async function save() {
  saving.value = true
  message.value = ''
  try {
    await api.put('/drugstock/settings', {
      hospital_name: form.general.hospital_name,
      app_name: form.general.app_name,
      footer_text: form.general.footer_text,
      registration_default_role: form.access.default_role,
      low_stock_threshold: form.stock_policy.low_stock_threshold,
      expiry_alert_days: form.stock_policy.expiry_alert_days,
      default_allocation_rule: form.stock_policy.default_allocation_rule,
      telegram_enabled: String(form.telegram.enabled) === '1',
      ...(form.telegram.bot_token ? { telegram_bot_token: form.telegram.bot_token } : {}),
      telegram_chat_id: form.telegram.chat_id,
      telegram_base_url: form.telegram.base_url,
    })
    message.value = 'บันทึกการตั้งค่าเรียบร้อย'
    await loadSettings()
  } finally {
    saving.value = false
  }
}

const roleItems = computed(() => form.access.roles.map((role) => ({
  title: role.name,
  value: role.name,
})))

async function testTelegram() {
  testing.value = true
  message.value = ''
  try {
    await api.post('/drugstock/settings/telegram/test')
    message.value = 'ส่งข้อความทดสอบ Telegram แล้ว'
  } finally {
    testing.value = false
  }
}

onMounted(loadSettings)
</script>

<style scoped>
.ds-settings-hero {
  background:
    radial-gradient(circle at top right, rgba(34, 211, 238, 0.16), transparent 30%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(240, 253, 250, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.ds-settings-hero__content {
  padding-top: 4px;
  padding-bottom: 4px;
}

.ds-hero-icon {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
}

.ds-hero-icon--cyan {
  background: linear-gradient(180deg, rgba(236, 254, 255, 0.98), rgba(165, 243, 252, 0.92));
}

.ds-settings-panel {
  border: 1px solid rgba(148, 163, 184, 0.14);
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.ds-settings-panel .v-tabs {
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.96));
}

.ds-settings-panel--blue {
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.48), rgba(255, 255, 255, 0.98));
}

.ds-settings-mini {
  border: 1px solid rgba(255, 255, 255, 0.55);
}

.ds-settings-mini--blue {
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.92), rgba(191, 219, 254, 0.78));
}

.ds-settings-mini--violet {
  background: linear-gradient(180deg, rgba(245, 243, 255, 0.92), rgba(221, 214, 254, 0.78));
}

.ds-settings-mini--green {
  background: linear-gradient(180deg, rgba(240, 253, 244, 0.92), rgba(187, 247, 208, 0.78));
}
</style>

