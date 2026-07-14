<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => {
      const validStatuses = [
        // Agent statuses
        'online',
        'away',
        'busy',
        'offline',
        // Conversation statuses
        'pending',
        'queued',
        'active',
        'transferred',
        'closed',
        // General statuses
        'active',
        'inactive',
      ]
      return validStatuses.includes(value)
    },
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
})

const statusConfig = computed(() => {
  const configs = {
    // Agent statuses
    online: {
      label: 'Online',
      dotClass: 'bg-green-500',
      bgClass: 'bg-green-100',
      textClass: 'text-green-700',
      borderClass: 'border-green-200',
    },
    away: {
      label: 'Away',
      dotClass: 'bg-amber-500',
      bgClass: 'bg-amber-100',
      textClass: 'text-amber-700',
      borderClass: 'border-amber-200',
    },
    busy: {
      label: 'Busy',
      dotClass: 'bg-red-500',
      bgClass: 'bg-red-100',
      textClass: 'text-red-700',
      borderClass: 'border-red-200',
    },
    offline: {
      label: 'Offline',
      dotClass: 'bg-gray-400',
      bgClass: 'bg-gray-100',
      textClass: 'text-gray-600',
      borderClass: 'border-gray-200',
    },
    // Conversation statuses
    pending: {
      label: 'Pending',
      dotClass: 'bg-amber-500',
      bgClass: 'bg-amber-100',
      textClass: 'text-amber-700',
      borderClass: 'border-amber-200',
    },
    queued: {
      label: 'Queued',
      dotClass: 'bg-blue-500',
      bgClass: 'bg-blue-100',
      textClass: 'text-blue-700',
      borderClass: 'border-blue-200',
    },
    active: {
      label: 'Active',
      dotClass: 'bg-green-500',
      bgClass: 'bg-green-100',
      textClass: 'text-green-700',
      borderClass: 'border-green-200',
    },
    transferred: {
      label: 'Transferred',
      dotClass: 'bg-orange-500',
      bgClass: 'bg-orange-100',
      textClass: 'text-orange-700',
      borderClass: 'border-orange-200',
    },
    closed: {
      label: 'Closed',
      dotClass: 'bg-gray-400',
      bgClass: 'bg-gray-100',
      textClass: 'text-gray-600',
      borderClass: 'border-gray-200',
    },
    // General statuses
    inactive: {
      label: 'Inactive',
      dotClass: 'bg-gray-400',
      bgClass: 'bg-gray-100',
      textClass: 'text-gray-600',
      borderClass: 'border-gray-200',
    },
  }

  return configs[props.status] || configs.offline
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: {
      container: 'px-2 py-0.5 text-[10px]',
      dot: 'w-1.5 h-1.5',
    },
    md: {
      container: 'px-2.5 py-1 text-xs',
      dot: 'w-2 h-2',
    },
    lg: {
      container: 'px-3 py-1.5 text-sm',
      dot: 'w-2.5 h-2.5',
    },
  }
  return sizes[props.size]
})
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 font-medium rounded-full border"
    :class="[
      sizeClasses.container,
      statusConfig.bgClass,
      statusConfig.textClass,
      statusConfig.borderClass,
    ]"
    :aria-label="`Status: ${statusConfig.label}`"
    role="status"
  >
    <span
      class="rounded-full flex-shrink-0"
      :class="[sizeClasses.dot, statusConfig.dotClass]"
      aria-hidden="true"
    />
    {{ statusConfig.label }}
  </span>
</template>
