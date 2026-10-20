<template>
  <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
    <span class="text-sm text-slate-600">{{ label }}</span>
    <span class="text-sm font-semibold text-slate-950">{{ value ?? 0 }}</span>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
});
</script>
