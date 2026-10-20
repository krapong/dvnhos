<template>
  <div :class="chipClass">
    <p :class="labelClass">{{ label }}</p>
    <p :class="valueClass">{{ value }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: String,
  value: [String, Number],
  tone: {
    type: String,
    default: 'slate',
  },
});

const toneClasses = {
  slate: 'border-slate-200 bg-slate-50',
  sky: 'border-sky-200 bg-sky-50',
  rose: 'border-rose-200 bg-rose-50',
  emerald: 'border-emerald-200 bg-emerald-50',
  amber: 'border-amber-200 bg-amber-50',
};

const labelColors = {
  slate: 'text-slate-500',
  sky: 'text-sky-700',
  rose: 'text-rose-700',
  emerald: 'text-emerald-700',
  amber: 'text-amber-700',
};

const valueColors = {
  slate: 'text-slate-950',
  sky: 'text-sky-950',
  rose: 'text-rose-950',
  emerald: 'text-emerald-950',
  amber: 'text-amber-950',
};

const chipClass = computed(() => [
  'rounded-lg border px-4 py-3',
  toneClasses[props.tone] ?? toneClasses.slate,
]);

const labelClass = computed(() => [
  'text-xs font-semibold uppercase',
  labelColors[props.tone] ?? labelColors.slate,
]);

const valueClass = computed(() => [
  'mt-2 text-sm font-medium',
  valueColors[props.tone] ?? valueColors.slate,
]);
</script>
