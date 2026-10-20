<template>
  <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm">
    <span class="text-sm text-slate-600">{{ title }}</span>
    <span class="text-sm font-semibold text-slate-950">{{ value ?? 0 }}</span>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number],
});
</script>
