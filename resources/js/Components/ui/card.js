import { h } from 'vue'

export const Card = {
  name: 'Card',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('div', {
      class: `rounded-lg border border-border bg-card text-card-foreground shadow-sm ${props.class}`
    }, slots.default?.())
  }
}

export const CardHeader = {
  name: 'CardHeader',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('div', {
      class: `flex flex-col space-y-1.5 p-6 ${props.class}`
    }, slots.default?.())
  }
}

export const CardTitle = {
  name: 'CardTitle',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('h3', {
      class: `text-lg font-semibold leading-none tracking-tight ${props.class}`
    }, slots.default?.())
  }
}

export const CardDescription = {
  name: 'CardDescription',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('p', {
      class: `text-sm text-muted-foreground ${props.class}`
    }, slots.default?.())
  }
}

export const CardContent = {
  name: 'CardContent',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('div', {
      class: `p-6 pt-0 ${props.class}`
    }, slots.default?.())
  }
}

export const CardFooter = {
  name: 'CardFooter',
  props: {
    class: {
      type: String,
      default: ''
    }
  },
  setup(props, { slots }) {
    return () => h('div', {
      class: `flex items-center p-6 pt-0 ${props.class}`
    }, slots.default?.())
  }
}
