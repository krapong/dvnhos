<template>
  <v-app>
    <v-main class="d-flex align-center justify-center auth-shell">
      <v-container style="max-width: 440px">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.auth-shell {
  min-height: 100vh;
  background:
    radial-gradient(circle at top, rgba(15, 118, 110, 0.18), transparent 30%),
    linear-gradient(180deg, #eef6f5 0%, #f7fafc 100%);
}
</style>
