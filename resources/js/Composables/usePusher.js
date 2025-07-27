import { ref, onMounted, onUnmounted } from 'vue'
import Pusher from 'pusher-js'

export function usePusher() {
  const pusher = ref(null)
  const connected = ref(false)
  const error = ref(null)
  const channels = ref(new Map())

  /**
   * Initialize Pusher connection
   */
  const initialize = () => {
    try {
      // Enable pusher logging for development
      if (import.meta.env.DEV) {
        Pusher.logToConsole = true
      }

      pusher.value = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          }
        }
      })

      // Connection event handlers
      pusher.value.connection.bind('connected', () => {
        connected.value = true
        error.value = null
        console.log('Pusher connected')
      })

      pusher.value.connection.bind('disconnected', () => {
        connected.value = false
        console.log('Pusher disconnected')
      })

      pusher.value.connection.bind('error', (err) => {
        error.value = err
        console.error('Pusher connection error:', err)
      })

      pusher.value.connection.bind('unavailable', () => {
        error.value = 'Pusher connection unavailable'
        console.error('Pusher connection unavailable')
      })

    } catch (err) {
      error.value = err.message
      console.error('Failed to initialize Pusher:', err)
    }
  }

  /**
   * Subscribe to a channel
   */
  const subscribe = (channelName, isPrivate = false) => {
    if (!pusher.value) {
      console.error('Pusher not initialized')
      return null
    }

    try {
      let channel
      
      if (isPrivate) {
        channel = pusher.value.subscribe(`private-${channelName}`)
      } else {
        channel = pusher.value.subscribe(channelName)
      }

      channels.value.set(channelName, channel)
      
      console.log(`Subscribed to channel: ${channelName}`)
      return channel
    } catch (err) {
      console.error(`Failed to subscribe to channel ${channelName}:`, err)
      return null
    }
  }

  /**
   * Unsubscribe from a channel
   */
  const unsubscribe = (channelName) => {
    if (!pusher.value) {
      return
    }

    try {
      pusher.value.unsubscribe(channelName)
      channels.value.delete(channelName)
      console.log(`Unsubscribed from channel: ${channelName}`)
    } catch (err) {
      console.error(`Failed to unsubscribe from channel ${channelName}:`, err)
    }
  }

  /**
   * Listen to an event on a channel
   */
  const listen = (channelName, eventName, callback) => {
    const channel = channels.value.get(channelName)
    
    if (!channel) {
      console.error(`Channel ${channelName} not found. Make sure to subscribe first.`)
      return
    }

    try {
      channel.bind(eventName, callback)
      console.log(`Listening to event ${eventName} on channel ${channelName}`)
    } catch (err) {
      console.error(`Failed to listen to event ${eventName} on channel ${channelName}:`, err)
    }
  }

  /**
   * Stop listening to an event on a channel
   */
  const stopListening = (channelName, eventName, callback = null) => {
    const channel = channels.value.get(channelName)
    
    if (!channel) {
      return
    }

    try {
      if (callback) {
        channel.unbind(eventName, callback)
      } else {
        channel.unbind(eventName)
      }
      console.log(`Stopped listening to event ${eventName} on channel ${channelName}`)
    } catch (err) {
      console.error(`Failed to stop listening to event ${eventName} on channel ${channelName}:`, err)
    }
  }

  /**
   * Get channel by name
   */
  const getChannel = (channelName) => {
    return channels.value.get(channelName)
  }

  /**
   * Disconnect Pusher
   */
  const disconnect = () => {
    if (pusher.value) {
      try {
        // Unsubscribe from all channels
        channels.value.forEach((channel, channelName) => {
          unsubscribe(channelName)
        })

        pusher.value.disconnect()
        pusher.value = null
        connected.value = false
        channels.value.clear()
        console.log('Pusher disconnected')
      } catch (err) {
        console.error('Failed to disconnect Pusher:', err)
      }
    }
  }

  /**
   * Reconnect Pusher
   */
  const reconnect = () => {
    disconnect()
    initialize()
  }

  // Auto-initialize on mount
  onMounted(() => {
    initialize()
  })

  // Cleanup on unmount
  onUnmounted(() => {
    disconnect()
  })

  return {
    // State
    pusher,
    connected,
    error,
    channels,

    // Methods
    initialize,
    subscribe,
    unsubscribe,
    listen,
    stopListening,
    getChannel,
    disconnect,
    reconnect
  }
}
