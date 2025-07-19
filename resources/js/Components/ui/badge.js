import { h } from 'vue'

const badgeVariants = {
  default: 'bg-primary text-primary-foreground hover:bg-primary/80',
  secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
  destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/80',
  outline: 'text-foreground border border-input bg-background hover:bg-accent',
  success: 'bg-green-500 text-white hover:bg-green-600',
  warning: 'bg-yellow-500 text-white hover:bg-yellow-600'
}

export const Badge = {
  name: 'Badge',
  props: {
    variant: {
      type: String,
      default: 'default',
      validator: (value) => Object.keys(badgeVariants).includes(value)
    },
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('span', {
      class: [
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors',
        badgeVariants[props.variant],
        props.class
      ].join(' ')
    }, slots.default?.())
  }
}
