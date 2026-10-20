<template>
  <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
    <p class="text-xs font-semibold uppercase text-slate-500">{{ label }}</p>
    <p class="mt-2 text-sm font-medium text-slate-950">{{ value }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
});
</script>
