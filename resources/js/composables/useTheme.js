import { useColorMode } from '@vueuse/core'
import { computed } from 'vue'

/**
 * Global theme configuration for the entire application
 * This ensures consistent theme behavior across all components
 */
export function useTheme() {
  const mode = useColorMode({
    attribute: 'class',
    modes: {
      light: '',
      dark: 'dark',
    },
    initialValue: 'light', // Default to light mode
    storageKey: 'dccphub-theme', // Custom storage key for the app
  })

  return {
    mode,
    isDark: computed(() => mode.value === 'dark'),
    isLight: computed(() => mode.value === 'light'),
    toggleTheme: () => {
      mode.value = mode.value === 'dark' ? 'light' : 'dark'
    },
    setLight: () => {
      mode.value = 'light'
    },
    setDark: () => {
      mode.value = 'dark'
    }
  }
}
