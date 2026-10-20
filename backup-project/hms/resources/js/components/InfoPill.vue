<template>
  <div class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm">
    <div class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ label }}</div>
    <div class="mt-1 text-base font-medium text-slate-950">{{ value }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
});
</script>
