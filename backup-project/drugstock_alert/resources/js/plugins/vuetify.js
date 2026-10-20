import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

export default createVuetify({
  defaults: {
    VCard: {
      rounded: 'xl',
      elevation: 0,
    },
    VBtn: {
      rounded: 'lg',
      elevation: 0,
    },
  },
  theme: {
    defaultTheme: 'hospitalLight',
    themes: {
      hospitalLight: {
        dark: false,
        colors: {
          background: '#F4F7F6',
          surface: '#FFFFFF',
          primary: '#2DD4BF',
          'primary-darken-1': '#14B8A6',
          secondary: '#818CF8',
          accent: '#FBBF24',
          error: '#F87171',
          info: '#60A5FA',
          success: '#34D399',
          warning: '#FBBF24',
        },
      },
      hospitalDark: {
        dark: true,
        colors: {
          background: '#0F172A',
          surface: '#1E293B',
          primary: '#14B8A6',
          'primary-darken-1': '#0D9488',
          secondary: '#6366F1',
          accent: '#F59E0B',
          error: '#EF4444',
          info: '#3B82F6',
          success: '#10B981',
          warning: '#F59E0B',
        },
      },
    },
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
})
