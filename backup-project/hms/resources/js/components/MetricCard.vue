<template>
  <article class="metric-card">
    <div :class="['metric-accent', accentClass]"></div>
    <p class="relative text-sm font-medium text-slate-600">{{ title }}</p>
    <div class="relative mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ value }}</div>
    <p class="relative mt-2 text-xs leading-5 text-slate-500">{{ helper }}</p>
  </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  value: [String, Number],
  helper: String,
  tone: {
    type: String,
    default: 'cyan',
  },
});

const accentClass = computed(() => ({
  cyan: 'bg-cyan-400/80',
  amber: 'bg-amber-400/80',
  rose: 'bg-rose-400/80',
  emerald: 'bg-emerald-400/80',
  blue: 'bg-blue-400/80',
}[props.tone] ?? 'bg-cyan-400/80'));
</script>
