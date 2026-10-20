<template>
  <article class="glass-card overflow-hidden rounded-3xl p-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm text-slate-600">{{ title }}</p>
        <div class="mt-2 text-3xl font-semibold text-slate-950">{{ value }}</div>
        <p class="mt-2 text-xs text-slate-500">{{ note }}</p>
      </div>
      <div :class="['h-12 w-12 rounded-2xl bg-gradient-to-br', accent]"></div>
    </div>
  </article>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number],
  note: String,
  accent: String,
});
</script>
