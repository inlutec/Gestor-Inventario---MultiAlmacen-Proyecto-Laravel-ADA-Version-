import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    mode: (localStorage.getItem('theme:mode') || 'system'), // 'light' | 'dark' | 'system'
  }),
  getters: {
    current(state) {
      if (state.mode === 'system') {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
      }
      return state.mode
    }
  },
  actions: {
    setMode(mode) {
      this.mode = mode
      localStorage.setItem('theme:mode', mode)
      this.apply()
    },
    toggle() {
      this.setMode(this.current === 'dark' ? 'light' : 'dark')
    },
    apply() {
      const cls = document.documentElement.classList
      if (this.current === 'dark') cls.add('dark'); else cls.remove('dark')
    },
    init() {
      this.apply()
      if (this.mode === 'system' && window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)')
        mq.addEventListener?.('change', () => this.apply())
      }
    }
  }
})
