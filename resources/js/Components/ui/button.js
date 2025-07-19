import { h } from 'vue'

const buttonVariants = {
  default: 'bg-primary text-primary-foreground hover:bg-primary/90',
  destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
  outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
  secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
  ghost: 'hover:bg-accent hover:text-accent-foreground',
  link: 'text-primary underline-offset-4 hover:underline'
}

const buttonSizes = {
  default: 'h-10 px-4 py-2',
  sm: 'h-9 rounded-md px-3 text-sm',
  lg: 'h-11 rounded-md px-8',
  icon: 'h-10 w-10'
}

export const Button = {
  name: 'Button',
  props: {
    variant: {
      type: String,
      default: 'default',
      validator: (value) => Object.keys(buttonVariants).includes(value)
    },
    size: {
      type: String,
      default: 'default',
      validator: (value) => Object.keys(buttonSizes).includes(value)
    },
    class: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  setup(props, { slots, emit, attrs }) {
    const handleClick = (event) => {
      if (!props.disabled) {
        emit('click', event)
      }
    }

    return () => h('button', {
      ...attrs,
      class: [
        'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-offset-2',
        'disabled:opacity-50 disabled:pointer-events-none',
        buttonVariants[props.variant],
        buttonSizes[props.size],
        props.class
      ].join(' '),
      disabled: props.disabled,
      onClick: handleClick
    }, slots.default?.())
  }
}
