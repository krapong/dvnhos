<template>
  <div class="rounded-lg border border-slate-200 bg-white/80 p-3 shadow-sm">
    <div class="flex items-center justify-between text-sm">
      <span class="text-slate-600">{{ label }}</span>
      <span class="font-semibold text-slate-950">{{ value ?? 0 }}</span>
    </div>
    <div class="mt-3 h-2 rounded-full bg-slate-100">
      <div :class="['h-2 rounded-full', toneClass]" :style="{ width: width }"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: String,
  value: [String, Number],
  tone: {
    type: String,
    default: 'cyan',
  },
});

const toneClass = computed(() => ({
  cyan: 'bg-cyan-400',
  amber: 'bg-amber-400',
  emerald: 'bg-emerald-400',
  blue: 'bg-blue-400',
  rose: 'bg-rose-400',
}[props.tone] ?? 'bg-cyan-400'));

const width = computed(() => `${Math.min(100, Number(props.value ?? 0) * 12 + 10)}%`);
</script>
