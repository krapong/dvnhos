<template>
  <v-app>
    <v-navigation-drawer
      v-model="ui.drawer"
      :rail="ui.sidebarRail"
      rail-width="88"
      width="280"
      :theme="ui.darkMode ? 'dark' : 'light'"
      class="border-0 shadow-sm ds-app-drawer"
      style="background-color: rgb(var(--v-theme-surface));"
    >

      <div class="pa-5 pb-3 ds-drawer-brand" data-ds-sidebar-brand>
        <div class="d-flex align-center gap-3 mb-4">
          <v-avatar color="primary" size="48" variant="tonal" class="ds-brand-avatar">
            <v-icon icon="mdi-pill" size="28"></v-icon>
          </v-avatar>
          <div class="ds-brand-copy">
            <div class="text-h6 font-weight-bold ds-brand-title">ระบบคลังยา</div>
            <div class="text-caption text-medium-emphasis">Drug Stock Alert</div>
          </div>
        </div>
      </div>

      <v-list density="comfortable" nav class="px-3 ds-nav-list">
        <v-list-item
          v-for="item in primaryNavItems"
          :key="item.to"
          :to="item.to"
          :active-color="item.activeColor || 'primary'"
          rounded="lg"
          class="mb-1 ds-nav-item"
        >
          <template #prepend>
            <v-avatar :color="item.color" size="30" variant="tonal" class="ds-nav-icon" :class="item.chipClass">
              <v-icon :icon="item.icon" size="18" :style="{ color: item.colorText }"></v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="font-weight-medium">{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>

      <div class="flex-grow-1"></div>

      <div class="px-6 pt-2 pb-1 text-caption font-weight-medium text-medium-emphasis ds-nav-section-title">
        ข้อมูลระบบ
      </div>

      <v-list density="comfortable" nav class="px-3 ds-nav-list ds-nav-list--bottom">
        <v-list-item
          v-for="item in systemNavItems"
          :key="item.to"
          :to="item.to"
          :active-color="item.activeColor || 'primary'"
          rounded="lg"
          class="mb-1 ds-nav-item"
        >
          <template #prepend>
            <v-avatar :color="item.color" size="30" variant="tonal" class="ds-nav-icon" :class="item.chipClass">
              <v-icon :icon="item.icon" size="18" :style="{ color: item.colorText }"></v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="font-weight-medium">{{ item.title }}</v-list-item-title>
        </v-list-item>

        <v-list-item
          @click="handleLogout"
          rounded="lg"
          class="mb-1 ds-nav-item mt-2"
        >
          <template #prepend>
            <v-avatar color="red-lighten-4" size="30" variant="tonal" class="ds-nav-icon ds-nav-icon--alert">
              <v-icon icon="mdi-logout" size="18" color="#dc2626"></v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="font-weight-medium text-red-darken-1">ออกจากระบบ</v-list-item-title>
        </v-list-item>
      </v-list>

      <template v-slot:append>
        <div class="pa-4 ds-drawer-footer" data-ds-sidebar-footer>
          <v-card color="primary" variant="tonal" rounded="lg" class="pa-3 text-center ds-footer-card">
            <v-icon icon="mdi-information-outline" class="mb-2"></v-icon>
            <div class="text-caption mb-2">อัปเดตระบบล่าสุด V 1.2</div>
            <v-btn variant="flat" color="primary" size="small" block rounded="lg" to="/about">ดูรายละเอียด</v-btn>
          </v-card>
        </div>
      </template>
    </v-navigation-drawer>

    <v-app-bar
      color="surface"
      elevation="0"
      class="border-b ds-app-bar"
      :style="{ backgroundColor: ui.darkMode ? 'rgba(30, 41, 59, 0.8) !important' : 'rgba(255, 255, 255, 0.8) !important', backdropFilter: 'blur(10px)' }"
    >
      <v-app-bar-nav-icon @click="ui.toggleDrawer" variant="text" rounded="lg" class="ml-2 ds-topbar-icon ds-topbar-menu" />
      <v-btn
        :icon="ui.sidebarRail ? 'mdi-format-align-left' : 'mdi-chevron-left'"
        variant="text"
        rounded="lg"
        class="ml-1 ds-topbar-icon ds-topbar-rail"
        :aria-label="ui.sidebarRail ? 'ขยาย sidebar' : 'ยุบ sidebar'"
        @click="ui.toggleSidebarRail"
      />

      <div class="d-flex align-center justify-space-between flex-grow-1 ga-4 ds-topbar-row px-4">
        <div class="d-flex flex-column justify-center min-w-0">
          <div class="text-body-1 font-weight-medium text-truncate ds-app-title">
            {{ hospitalName }}
          </div>
          <div class="text-caption text-medium-emphasis text-truncate">
            {{ appName }}
          </div>
        </div>

        <div class="d-flex align-center justify-end ga-2 flex-shrink-0 ds-user-badge">
          <div class="text-right d-none d-sm-flex flex-column">
            <div class="text-caption text-medium-emphasis">ผู้ใช้งาน</div>
            <div class="text-body-2 font-weight-semibold text-truncate">
              {{ currentUserName }}
            </div>
          </div>
          <v-avatar color="primary" size="36" variant="tonal">
            <span class="text-button">{{ userInitials }}</span>
          </v-avatar>
        </div>
      </div>

      <v-btn variant="text" icon="mdi-bell-outline" class="mr-2 ds-topbar-icon ds-topbar-bell" />
      <v-btn variant="text" icon="mdi-theme-light-dark" @click="ui.toggleTheme" class="mr-2 ds-topbar-icon ds-topbar-theme" />
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6 app-shell">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const ui = useUiStore()
const auth = useAuthStore()
const theme = useTheme()
const router = useRouter()

const hospitalName = ref('โรงพยาบาลเทพรัตนเวชชานุกูล')
const appName = ref('Drug Stock Alert System')

function handleLogout() {
  auth.logout()
  router.push('/login')
}

const currentUserName = computed(() => auth.user?.name || 'System Administrator')
const userInitials = computed(() => {
  const name = String(currentUserName.value || '').trim()
  if (!name) return 'U'
  const parts = name.split(/\s+/).filter(Boolean)
  if (!parts.length) return 'U'
  return parts.slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('')
})

async function loadSettings() {
  try {
    const { data } = await api.get('/drugstock/settings')
    const payload = data?.data?.general || {}
    if (payload.hospital_name) hospitalName.value = payload.hospital_name
    if (payload.app_name) appName.value = payload.app_name
  } catch (error) {
    // Keep fallback labels when settings are unavailable.
  }
}

const primaryNavItems = [
  { title: 'ภาพรวม', to: '/', icon: 'mdi-view-grid-outline', color: 'blue-lighten-4', colorText: '#2563eb', chipClass: 'ds-nav-icon--blue' },
  { title: 'คลังย่อย', to: '/warehouses', icon: 'mdi-factory', color: 'green-lighten-4', colorText: '#16a34a', chipClass: 'ds-nav-icon--green' },
  { title: 'ยา', to: '/drugs', icon: 'mdi-medication-outline', color: 'indigo-lighten-4', colorText: '#4f46e5', chipClass: 'ds-nav-icon--indigo' },
  { title: 'สถานที่', to: '/locations', icon: 'mdi-map-marker-radius-outline', color: 'amber-lighten-4', colorText: '#d97706', chipClass: 'ds-nav-icon--amber' },
  { title: 'แจ้งเตือน', to: '/alerts', icon: 'mdi-bell-ring-outline', color: 'red-lighten-4', colorText: '#dc2626', chipClass: 'ds-nav-icon--alert' },
  { title: 'รายงาน', to: '/reports', icon: 'mdi-chart-line', color: 'teal-lighten-4', colorText: '#0f766e', chipClass: 'ds-nav-icon--teal' },
]

const systemNavItems = [
  { title: 'การตั้งค่า', to: '/settings', icon: 'mdi-cog', color: 'cyan-lighten-4', colorText: '#0891b2', chipClass: 'ds-nav-icon--cyan' },
  { title: 'วิธีใช้งาน', to: '/help', icon: 'mdi-book-open-variant', color: 'pink-lighten-4', colorText: '#e11d48', chipClass: 'ds-nav-icon--rose' },
]

watch(
  () => ui.darkMode,
  (dark) => {
    theme.global.name.value = dark ? 'hospitalDark' : 'hospitalLight'
  },
  { immediate: true },
)

onMounted(loadSettings)
</script>

<style scoped>
.app-shell {
  min-height: calc(100vh - 64px);
  background:
    radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 40%),
    radial-gradient(circle at top left, rgba(129, 140, 248, 0.05), transparent 30%);
}

.v-list-item--active {
  background-color: rgb(var(--v-theme-primary), 0.1) !important;
  font-weight: 600;
}

.ds-nav-icon {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.45);
}

.ds-nav-icon--blue { background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(191, 219, 254, 0.92)); }
.ds-nav-icon--green { background: linear-gradient(180deg, rgba(240, 253, 244, 0.98), rgba(187, 247, 208, 0.92)); }
.ds-nav-icon--indigo { background: linear-gradient(180deg, rgba(238, 242, 255, 0.98), rgba(199, 210, 254, 0.92)); }
.ds-nav-icon--amber { background: linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(254, 240, 138, 0.92)); }
.ds-nav-icon--orange { background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(253, 186, 116, 0.92)); }
.ds-nav-icon--alert { background: linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(254, 202, 202, 0.92)); }
.ds-nav-icon--violet { background: linear-gradient(180deg, rgba(245, 243, 255, 0.98), rgba(221, 214, 254, 0.92)); }
.ds-nav-icon--teal { background: linear-gradient(180deg, rgba(240, 253, 250, 0.98), rgba(153, 246, 228, 0.92)); }
.ds-nav-icon--cyan { background: linear-gradient(180deg, rgba(236, 254, 255, 0.98), rgba(165, 243, 252, 0.92)); }
.ds-nav-icon--slate { background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.92)); }
.ds-nav-icon--rose { background: linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(251, 207, 232, 0.92)); }

.ds-app-drawer :deep(.v-list-item__prepend) {
  margin-inline-end: 12px;
}

.ds-app-drawer :deep(.v-list-item--nav .v-list-item-title) {
  font-size: 0.95rem;
}

.ds-app-drawer :deep(.v-list-item) {
  min-height: 46px;
}

.ds-app-drawer :deep(.v-navigation-drawer__content) {
  display: flex;
  flex-direction: column;
}

.ds-nav-section-title {
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.ds-topbar-icon {
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(241, 245, 249, 0.96));
  border: 1px solid rgba(148, 163, 184, 0.18);
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
}

.ds-topbar-icon :deep(.v-btn__content) {
  gap: 8px;
}

.ds-topbar-row {
  min-width: 0;
}

.ds-app-title {
  line-height: 1.2;
}

.ds-user-badge {
  margin-left: auto;
}

.ds-topbar-menu {
  color: #0f766e;
}

.ds-topbar-rail {
  color: #2563eb;
}

.ds-topbar-bell {
  color: #f97316;
}

.ds-topbar-theme {
  color: #7c3aed;
}

@media (max-width: 959px) {
  .ds-brand-copy,
  .ds-drawer-footer {
    display: block;
  }
}

@media (min-width: 960px) {
  .ds-app-drawer.v-navigation-drawer--rail .ds-brand-copy,
  .ds-app-drawer.v-navigation-drawer--rail .ds-drawer-footer {
    display: none;
  }

  .ds-app-drawer.v-navigation-drawer--rail :deep(.v-list-item__prepend) {
    margin-inline-end: 0;
  }

  .ds-app-drawer.v-navigation-drawer--rail :deep(.v-list-item) {
    justify-content: center;
    padding-inline: 0;
  }

  .ds-app-drawer.v-navigation-drawer--rail :deep(.v-list-item-title) {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }
}
</style>


