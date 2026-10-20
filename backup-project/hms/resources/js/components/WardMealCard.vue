<template>
  <article class="metric-card">
    <div :class="['metric-accent', accentClass]"></div>
    <p class="relative text-sm font-medium text-slate-600">{{ title }}</p>

    <div class="relative mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <button
        v-for="item in items"
        :key="item.key"
        type="button"
        class="min-h-[96px] rounded-xl border px-4 py-3 text-left transition hover:-translate-y-0.5 focus:outline-none focus:ring-2"
        :class="[item.buttonClass, item.ringClass]"
        @click="emit('select', { wardGroup, metric: item.key })"
      >
        <p :class="item.labelClass">{{ item.label }}</p>
        <p :class="item.valueClass">{{ item.count }}</p>
      </button>
    </div>

    <p class="relative mt-3 text-xs leading-5 text-slate-500">{{ helper }}</p>
  </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  helper: String,
  wardGroup: String,
  orderedCount: {
    type: [String, Number],
    default: 0,
  },
  withoutCount: {
    type: [String, Number],
    default: 0,
  },
  npoCount: {
    type: [String, Number],
    default: 0,
  },
  bmiCount: {
    type: [String, Number],
    default: 0,
  },
  orderedLabel: {
    type: String,
    default: 'สั่งอาหาร',
  },
  withoutLabel: {
    type: String,
    default: 'ไม่ได้สั่งอาหาร',
  },
  npoLabel: {
    type: String,
    default: 'NPO',
  },
  bmiLabel: {
    type: String,
    default: 'BMI เสี่ยง',
  },
  tone: {
    type: String,
    default: 'sky',
  },
});

const emit = defineEmits(['select']);

const accentClass = computed(() => ({
  sky: 'bg-sky-400/80',
  rose: 'bg-rose-400/80',
  emerald: 'bg-emerald-400/80',
  amber: 'bg-amber-400/80',
}[props.tone] ?? 'bg-sky-400/80'));

const items = computed(() => [
  {
    key: 'ordered',
    label: props.orderedLabel,
    count: props.orderedCount,
    buttonClass: 'border-emerald-200 bg-emerald-50 hover:bg-emerald-100',
    ringClass: 'focus:ring-emerald-300',
    labelClass: 'text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700',
    valueClass: 'mt-2 text-3xl font-semibold tracking-tight text-emerald-950',
  },
  {
    key: 'without',
    label: props.withoutLabel,
    count: props.withoutCount,
    buttonClass: 'border-amber-200 bg-amber-50 hover:bg-amber-100',
    ringClass: 'focus:ring-amber-300',
    labelClass: 'text-xs font-semibold uppercase tracking-[0.18em] text-amber-700',
    valueClass: 'mt-2 text-3xl font-semibold tracking-tight text-amber-950',
  },
  {
    key: 'npo',
    label: props.npoLabel,
    count: props.npoCount,
    buttonClass: 'border-rose-200 bg-rose-50 hover:bg-rose-100',
    ringClass: 'focus:ring-rose-300',
    labelClass: 'text-xs font-semibold uppercase tracking-[0.18em] text-rose-700',
    valueClass: 'mt-2 text-3xl font-semibold tracking-tight text-rose-950',
  },
  {
    key: 'bmi',
    label: props.bmiLabel,
    count: props.bmiCount,
    buttonClass: 'border-violet-200 bg-violet-50 hover:bg-violet-100',
    ringClass: 'focus:ring-violet-300',
    labelClass: 'text-xs font-semibold uppercase tracking-[0.18em] text-violet-700',
    valueClass: 'mt-2 text-3xl font-semibold tracking-tight text-violet-950',
  },
]);
</script>
