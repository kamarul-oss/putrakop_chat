<script setup>
import { computed } from 'vue'
import {
  CheckIcon,
  CheckCheckIcon,
  DocumentIcon,
  PhotoIcon,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  message: {
    type: Object,
    required: true,
    validator: (value) => {
      return value && typeof value.sender_type === 'string' && typeof value.content === 'string'
    },
  },
  isOwn: {
    type: Boolean,
    default: false,
  },
})

const isSystemMessage = computed(() => props.message.sender_type === 'system')
const isAiMessage = computed(() => props.message.sender_type === 'ai')
const isCustomerMessage = computed(() => props.message.sender_type === 'customer')
const isAgentMessage = computed(() => props.message.sender_type === 'agent')

const messageTypes = {
  text: 'text',
  image: 'image',
  file: 'file',
  system: 'system',
}

const isImage = computed(() => props.message.type === 'image')
const isFile = computed(() => props.message.type === 'file')

const formattedTime = computed(() => {
  if (!props.message.created_at) return ''
  const date = new Date(props.message.created_at)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
})

const readStatusIcon = computed(() => {
  if (!props.isOwn) return null
  switch (props.message.status) {
    case 'sending':
      return null
    case 'sent':
      return 'sent'
    case 'delivered':
      return 'delivered'
    case 'read':
      return 'read'
    case 'failed':
      return 'failed'
    default:
      return null
  }
})

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>

<template>
  <!-- System Message -->
  <div v-if="isSystemMessage" class="flex justify-center my-4">
    <div class="bg-primary-50 text-gray-600 text-xs px-3 py-1.5 rounded-lg text-center max-w-[80%]">
      {{ message.content }}
    </div>
  </div>

  <!-- Chat Message -->
  <div
    v-else
    class="flex gap-2 mt-2"
    :class="{
      'flex-row-reverse': isOwn,
      'flex-row': !isOwn,
    }"
  >
    <!-- Avatar (Agent/AI) -->
    <div v-if="!isOwn" class="flex-shrink-0">
      <div
        class="w-7 h-7 rounded-full flex items-center justify-center"
        :class="{
          'bg-primary-100': isAgentMessage,
          'bg-green-100': isAiMessage,
          'bg-gray-100': !isAgentMessage && !isAiMessage,
        }"
      >
        <span
          class="text-xs font-medium"
          :class="{
            'text-primary-700': isAgentMessage,
            'text-green-700': isAiMessage,
            'text-gray-600': !isAgentMessage && !isAiMessage,
          }"
        >
          {{ isAiMessage ? 'AI' : getInitials(message.sender_name) }}
        </span>
      </div>
    </div>

    <!-- Message Content -->
    <div
      class="max-w-[80%]"
      :class="{ 'ml-auto': isOwn, 'mr-auto': !isOwn }"
    >
      <!-- Sender Name -->
      <p
        v-if="!isOwn && message.sender_name"
        class="text-xs font-medium text-gray-500 mb-1"
        :class="{ 'text-green-600': isAiMessage }"
      >
        {{ message.sender_name }}
      </p>

      <!-- Text Message -->
      <div
        v-if="message.type === 'text' || !message.type"
        class="px-3.5 py-2.5 text-sm leading-relaxed break-words"
        :class="{
          'bg-primary-700 text-white rounded-2xl rounded-br-sm': isOwn,
          'bg-white text-gray-900 border border-gray-200 rounded-2xl rounded-bl-sm': !isOwn && !isAiMessage,
          'bg-green-50 text-gray-900 border border-green-200 border-l-4 border-l-green-700 rounded-2xl rounded-bl-sm': isAiMessage,
        }"
      >
        {{ message.content }}
      </div>

      <!-- Image Message -->
      <div
        v-else-if="isImage"
        class="overflow-hidden rounded-xl"
        :class="{
          'bg-primary-700 rounded-br-sm': isOwn,
          'bg-white border border-gray-200 rounded-bl-sm': !isOwn,
        }"
      >
        <img
          :src="message.content"
          :alt="message.file_name || 'Image'"
          class="max-w-full max-h-[300px] object-cover"
          loading="lazy"
        />
        <div
          v-if="message.file_name"
          class="px-3 py-2 text-xs"
          :class="{ 'text-white/80': isOwn, 'text-gray-500': !isOwn }"
        >
          {{ message.file_name }}
        </div>
      </div>

      <!-- File Message -->
      <div
        v-else-if="isFile"
        class="flex items-center gap-3 px-4 py-3 rounded-xl"
        :class="{
          'bg-primary-700 text-white rounded-br-sm': isOwn,
          'bg-white border border-gray-200 rounded-bl-sm': !isOwn,
        }"
      >
        <div
          class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="{
            'bg-white/20': isOwn,
            'bg-gray-100': !isOwn,
          }"
        >
          <DocumentIcon
            class="w-5 h-5"
            :class="{ 'text-white': isOwn, 'text-gray-500': !isOwn }"
          />
        </div>
        <div class="min-w-0">
          <p
            class="text-sm font-medium truncate"
            :class="{ 'text-white': isOwn, 'text-gray-900': !isOwn }"
          >
            {{ message.file_name || 'File' }}
          </p>
          <p
            class="text-xs"
            :class="{ 'text-white/70': isOwn, 'text-gray-500': !isOwn }"
          >
            {{ message.file_size || 'File' }}
          </p>
        </div>
      </div>

      <!-- Timestamp & Read Receipt -->
      <div
        class="flex items-center gap-1.5 mt-1"
        :class="{
          'justify-end': isOwn,
          'justify-start': !isOwn,
        }"
      >
        <span class="text-[10px] text-gray-400">{{ formattedTime }}</span>

        <!-- Read Receipt (own messages only) -->
        <span v-if="readStatusIcon" class="flex items-center">
          <CheckIcon
            v-if="readStatusIcon === 'sent'"
            class="w-3.5 h-3.5 text-gray-400"
          />
          <CheckCheckIcon
            v-else-if="readStatusIcon === 'delivered'"
            class="w-3.5 h-3.5 text-gray-400"
          />
          <CheckCheckIcon
            v-else-if="readStatusIcon === 'read'"
            class="w-3.5 h-3.5 text-primary-500"
          />
          <ExclamationCircleIcon
            v-else-if="readStatusIcon === 'failed'"
            class="w-3.5 h-3.5 text-red-500"
          />
        </span>
      </div>
    </div>
  </div>
</template>
