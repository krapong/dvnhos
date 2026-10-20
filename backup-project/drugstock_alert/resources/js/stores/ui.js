import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({
    drawer: true,
    sidebarRail: false,
    darkMode: false,
  }),
  actions: {
    toggleDrawer() {
      this.drawer = !this.drawer
    },
    toggleSidebarRail() {
      this.sidebarRail = !this.sidebarRail
      if (this.sidebarRail) {
        this.drawer = true
      }
    },
    toggleTheme() {
      this.darkMode = !this.darkMode
    },
  },
})
