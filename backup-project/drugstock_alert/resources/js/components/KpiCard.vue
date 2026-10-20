<template>
  <v-hover v-slot="{ isHovering, props }">
    <v-card
      v-bind="props"
      class="pa-5 kpi-card metric-card"
      :elevation="isHovering ? 4 : 0"
      :class="[`kpi-card--${color}`, { 'on-hover': isHovering }]"
      :style="cardStyle"
    >
      <div class="kpi-card__inner">
        <div class="kpi-card__top">
          <div class="kpi-card__icon-wrap" :style="iconWrapStyle">
            <v-icon :icon="icon" size="28" :style="iconStyle" />
          </div>
          <div class="text-caption font-weight-medium kpi-card__label">{{ title }}</div>
        </div>

        <div class="kpi-card__body">
          <div class="text-h4 font-weight-bold kpi-card__value" :style="{ color: theme.text }">{{ value }}</div>
          <div class="text-body-2 kpi-card__subtitle d-flex align-center" :style="{ color: theme.textMutted }">
            <v-icon
              v-if="trendIcon"
              :icon="trendIcon"
              size="small"
              class="mr-1 kpi-card__trend"
              :style="trendStyle"
            />
            {{ subtitle }}
          </div>
        </div>
      </div>
    </v-card>
  </v-hover>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: { type: String, default: 'mdi-chart-box-outline' },
  color: { type: String, default: 'primary' },
  trendIcon: String,
  trendColor: String,
})

const palette = {
  primary: {
    card: ['#eff6ff', '#dbeafe'], // Blue 50-100
    surface: ['#bfdbfe', '#93c5fd'],
    accent: '#2563eb', // Blue 600
    text: '#1e3a8a',
    textMutted: '#3b82f6',
    border: '#bfdbfe',
  },
  success: {
    card: ['#f0fdf4', '#dcfce7'], // Green 50-100
    surface: ['#bbf7d0', '#86efac'],
    accent: '#16a34a', // Green 600
    text: '#14532d',
    textMutted: '#22c55e',
    border: '#bbf7d0',
  },
  warning: {
    card: ['#fffbeb', '#fef3c7'], // Amber 50-100
    surface: ['#fde68a', '#fcd34d'],
    accent: '#d97706', // Amber 600
    text: '#78350f',
    textMutted: '#f59e0b',
    border: '#fde68a',
  },
  amber: {
    card: ['#fffbeb', '#fef3c7'], 
    surface: ['#fde68a', '#fcd34d'],
    accent: '#d97706', 
    text: '#78350f',
    textMutted: '#f59e0b',
    border: '#fde68a',
  },
  error: {
    card: ['#fef2f2', '#fee2e2'], // Red 50-100
    surface: ['#fecaca', '#fca5a5'],
    accent: '#dc2626', // Red 600
    text: '#7f1d1d',
    textMutted: '#ef4444',
    border: '#fecaca',
  },
  red: {
    card: ['#fef2f2', '#fee2e2'], 
    surface: ['#fecaca', '#fca5a5'],
    accent: '#dc2626', 
    text: '#7f1d1d',
    textMutted: '#ef4444',
    border: '#fecaca',
  },
  teal: {
    card: ['#f0fdfa', '#ccfbf1'], // Teal 50-100
    surface: ['#99f6e4', '#5eead4'],
    accent: '#0d9488', // Teal 600
    text: '#134e4a',
    textMutted: '#14b8a6',
    border: '#99f6e4',
  },
  orange: {
    card: ['#fff7ed', '#ffedd5'], // Orange 50-100
    surface: ['#fed7aa', '#fdba74'],
    accent: '#ea580c', // Orange 600
    text: '#7c2d12',
    textMutted: '#f97316',
    border: '#fed7aa',
  },
  info: {
    card: ['#ecfeff', '#cffafe'], // Cyan 50-100
    surface: ['#a5f3fc', '#67e8f9'],
    accent: '#0891b2', // Cyan 600
    text: '#164e63',
    textMutted: '#06b6d4',
    border: '#a5f3fc',
  }
}

const theme = computed(() => palette[props.color] || palette.primary)

const cardStyle = computed(() => ({
  background: `linear-gradient(135deg, ${theme.value.card[0]}, ${theme.value.card[1]})`,
  borderColor: theme.value.border,
  boxShadow: '0 4px 20px rgba(0, 0, 0, 0.03)',
}))

const iconWrapStyle = computed(() => ({
  background: `linear-gradient(135deg, ${theme.value.surface[0]}, ${theme.value.surface[1]})`,
  color: theme.value.accent,
  boxShadow: `0 4px 12px ${theme.value.surface[0]}`,
}))

const iconStyle = computed(() => ({
  color: theme.value.accent,
}))

const trendStyle = computed(() => {
  const trendTheme = palette[props.trendColor] || theme.value
  return {
    color: trendTheme.accent,
  }
})
</script>

<style scoped>
.kpi-card {
  transition: all 0.3s ease;
  border: 1px solid transparent;
  overflow: hidden;
  border-radius: 20px;
}

.kpi-card.on-hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
}

.kpi-card__inner {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.kpi-card__top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.kpi-card__icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-card__label {
  color: #475569;
  font-size: 0.9rem;
  letter-spacing: 0.2px;
}

.kpi-card__value {
  line-height: 1.1;
  margin-bottom: 6px;
}

.kpi-card__subtitle {
  line-height: 1.4;
  font-size: 0.85rem;
}

.kpi-card__trend {
  color: currentColor;
}
</style>

