<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ChatMessage from '@/Pages/components/ChatMessage.vue'
import StatusBadge from '@/Pages/components/StatusBadge.vue'
import {
  PaperAirplaneIcon,
  PaperClipIcon,
  FaceSmileIcon,
  XMarkIcon,
  ChatBubbleLeftRightIcon,
  StarIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  departments: { type: Array, default: () => [] },
  user: { type: Object, default: null },
})

const emit = defineEmits(['close', 'minimize'])

// State
const messages = ref([])
const newMessage = ref('')
const selectedDepartment = ref(null)
const conversationId = ref(null)
const isTyping = ref(false)
const isConnected = ref(false)
const isQueued = ref(false)
const queuePosition = ref(0)
const estimatedWait = ref(0)
const showRatingModal = ref(false)
const ratingScore = ref(0)
const ratingComment = ref('')
const isRatingSubmitted = ref(false)
const isSending = ref(false)
const messagesContainer = ref(null)
const isMobile = ref(window.innerWidth < 768)
const showDepartmentPicker = ref(true)

// Fetch departments on mount
onMounted(async () => {
  try {
    const response = await fetch('/api/v1/customer/departments')
    const data = await response.json()
    if (data.data) {
      // Departments prop will override if provided
    }
  } catch {
    // Use prop departments if API fails
  }

  window.addEventListener('resize', handleResize)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('keydown', handleKeydown)
})

const handleResize = () => {
  isMobile.value = window.innerWidth < 768
}

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    emit('minimize')
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const isOwnMessage = (message) => {
  return message.sender_type === 'customer'
}

const formattedTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

const startChat = async (departmentId) => {
  selectedDepartment.value = departmentId
  showDepartmentPicker.value = false

  // Add system message
  messages.value.push({
    id: 'system-' + Date.now(),
    sender_type: 'system',
    content: 'Connecting you to an agent...',
    created_at: new Date().toISOString(),
    type: 'text',
  })

  scrollToBottom()

  // Simulate connection (in real app, this would use WebSocket)
  setTimeout(() => {
    isConnected.value = true
    isQueued.value = true
    queuePosition.value = Math.floor(Math.random() * 5) + 1
    estimatedWait.value = Math.floor(Math.random() * 5) + 1

    messages.value.push({
      id: 'system-' + Date.now(),
      sender_type: 'system',
      content: `You are number ${queuePosition.value} in the queue. Estimated wait: ~${estimatedWait.value} min`,
      created_at: new Date().toISOString(),
      type: 'text',
    })
    scrollToBottom()
  }, 1500)
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return

  const messageContent = newMessage.value.trim()
  newMessage.value = ''
  isSending.value = true

  const tempMessage = {
    id: 'temp-' + Date.now(),
    sender_type: 'customer',
    sender_name: props.user?.name || 'You',
    content: messageContent,
    created_at: new Date().toISOString(),
    status: 'sending',
    type: 'text',
  }

  messages.value.push(tempMessage)
  scrollToBottom()

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 500))

    // Update message status
    const msgIndex = messages.value.findIndex((m) => m.id === tempMessage.id)
    if (msgIndex !== -1) {
      messages.value[msgIndex].status = 'sent'
    }

    // Simulate agent typing
    setTimeout(() => {
      isTyping.value = true
      scrollToBottom()

      // Simulate agent response
      setTimeout(() => {
        isTyping.value = false
        messages.value.push({
          id: 'agent-' + Date.now(),
          sender_type: 'agent',
          sender_name: 'Agent Siti',
          content: 'Thank you for your message. How can I assist you further?',
          created_at: new Date().toISOString(),
          type: 'text',
        })
        scrollToBottom()
      }, 2000)
    }, 1000)
  } catch {
    const msgIndex = messages.value.findIndex((m) => m.id === tempMessage.id)
    if (msgIndex !== -1) {
      messages.value[msgIndex].status = 'failed'
    }
  } finally {
    isSending.value = false
  }
}

const submitRating = async () => {
  if (!ratingScore.value) return

  try {
    await new Promise((resolve) => setTimeout(resolve, 800))
    isRatingSubmitted.value = true
    setTimeout(() => {
      showRatingModal.value = false
      emit('close')
    }, 2000)
  } catch {
    // Handle error
  }
}

const closeRating = () => {
  showRatingModal.value = false
  emit('close')
}

const endChat = () => {
  showRatingModal.value = true
}

watch(
  () => messages.value.length,
  () => {
    scrollToBottom()
  }
)
</script>

<template>
  <div
    class="flex flex-col h-full bg-white rounded-xl shadow-xl overflow-hidden"
    :class="{
      'fixed inset-0 z-50 rounded-none': isMobile,
      'w-[360px] h-[560px]': !isMobile,
    }"
  >
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 bg-primary-900 text-white flex-shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
          <ChatBubbleLeftRightIcon class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-sm font-semibold">PutraKop Support</h2>
          <p v-if="isConnected && !isQueued" class="text-xs text-white/70">Connected</p>
          <p v-else-if="isQueued" class="text-xs text-white/70">Queue: #{{ queuePosition }}</p>
          <p v-else class="text-xs text-white/70">Start a conversation</p>
        </div>
      </div>
      <button
        @click="emit('minimize')"
        class="p-1.5 rounded-md hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
        aria-label="Close chat"
      >
        <XMarkIcon class="w-5 h-5" />
      </button>
    </div>

    <!-- Department Picker -->
    <div v-if="showDepartmentPicker" class="flex-1 flex flex-col items-center justify-center p-6">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <ChatBubbleLeftRightIcon class="w-8 h-8 text-primary-700" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">How can we help?</h3>
        <p class="text-sm text-gray-600">Select a department to get started</p>
      </div>

      <div class="w-full space-y-2">
        <button
          v-for="dept in departments"
          :key="dept.id"
          @click="startChat(dept.id)"
          class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 border border-gray-200 rounded-lg hover:bg-primary-50 hover:border-primary-300 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <span class="text-lg">{{ dept.icon || '💬' }}</span>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ dept.name }}</p>
            <p class="text-xs text-gray-500">{{ dept.description || 'Get help from our team' }}</p>
          </div>
          <ChevronDownIcon class="w-4 h-4 text-gray-400 ml-auto -rotate-90" />
        </button>
      </div>
    </div>

    <!-- Messages Area -->
    <div
      v-else
      ref="messagesContainer"
      class="flex-1 overflow-y-auto px-4 py-3 space-y-1 bg-gray-50"
    >
      <template v-for="(message, index) in messages" :key="message.id">
        <!-- Date Separator -->
        <div
          v-if="index === 0 || new Date(message.created_at).toDateString() !== new Date(messages[index - 1]?.created_at).toDateString()"
          class="flex items-center gap-3 my-4"
        >
          <div class="flex-1 h-px bg-gray-200" />
          <span class="text-xs text-gray-400 font-medium">
            {{ new Date(message.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
          </span>
          <div class="flex-1 h-px bg-gray-200" />
        </div>

        <ChatMessage
          :message="message"
          :is-own="isOwnMessage(message)"
        />
      </template>

      <!-- Typing Indicator -->
      <div v-if="isTyping" class="flex items-end gap-2 mt-2">
        <div class="w-7 h-7 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
          <span class="text-xs font-medium text-primary-700">A</span>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-sm px-4 py-3">
          <div class="flex gap-1">
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
          </div>
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div v-if="!showDepartmentPicker" class="flex-shrink-0 border-t border-gray-200 bg-white">
      <!-- Quick Actions Bar -->
      <div class="flex items-center gap-1 px-3 pt-2 pb-1">
        <button
          v-for="quick in ['Account inquiry', 'Billing', 'General question']"
          :key="quick"
          @click="newMessage = quick"
          class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-primary-50 hover:text-primary-700 transition-colors"
        >
          {{ quick }}
        </button>
      </div>

      <div class="flex items-end gap-2 px-3 py-2">
        <button
          class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 flex-shrink-0"
          aria-label="Attach file"
        >
          <PaperClipIcon class="w-5 h-5" />
        </button>

        <div class="flex-1 relative">
          <textarea
            v-model="newMessage"
            @keydown.enter.exact.prevent="sendMessage"
            placeholder="Type a message..."
            rows="1"
            class="w-full resize-none border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
            :class="{ 'pr-10': newMessage.trim() }"
            style="max-height: 120px; min-height: 38px"
          />
          <button
            v-if="newMessage.trim()"
            @click="sendMessage"
            :disabled="isSending"
            class="absolute right-2 bottom-2 p-1.5 text-white bg-primary-700 rounded-md hover:bg-primary-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 disabled:opacity-50"
            aria-label="Send message"
          >
            <PaperAirplaneIcon class="w-4 h-4" />
          </button>
        </div>

        <button
          class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 flex-shrink-0"
          aria-label="Add emoji"
        >
          <FaceSmileIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- End Chat Button -->
    <div
      v-if="!showDepartmentPicker && isConnected && !isQueued"
      class="flex-shrink-0 px-3 pb-2"
    >
      <button
        @click="endChat"
        class="w-full text-xs font-medium text-gray-500 hover:text-red-600 py-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
      >
        End conversation
      </button>
    </div>

    <!-- Rating Modal -->
    <Teleport to="body">
      <div
        v-if="showRatingModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="closeRating"
      >
        <div
          class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden"
          role="dialog"
          aria-modal="true"
          aria-labelledby="rating-title"
        >
          <!-- Thank You State -->
          <div v-if="isRatingSubmitted" class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Thank you!</h3>
            <p class="text-sm text-gray-600">Your feedback helps us improve.</p>
          </div>

          <!-- Rating Form -->
          <div v-else class="p-6">
            <div class="text-center mb-6">
              <h3 id="rating-title" class="text-lg font-semibold text-gray-900 mb-1">
                How was your experience?
              </h3>
              <p class="text-sm text-gray-600">Your feedback helps us serve you better</p>
            </div>

            <!-- Rating Emojis -->
            <div class="flex justify-center gap-2 mb-6">
              <button
                v-for="(emoji, index) in ['😡', '😕', '😐', '🙂', '😊']"
                :key="index"
                @click="ratingScore = index + 1"
                class="flex flex-col items-center gap-1 p-2 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary-500"
                :class="{
                  'scale-110 ring-2 ring-primary-500 bg-primary-50': ratingScore === index + 1,
                  'hover:bg-gray-50': ratingScore !== index + 1,
                }"
                :aria-label="`Rate ${index + 1} out of 5`"
              >
                <span class="text-3xl">{{ emoji }}</span>
                <span class="text-xs text-gray-500">{{ index + 1 }}</span>
              </button>
            </div>
            <div class="flex justify-between px-4 mb-6">
              <span class="text-xs text-gray-400">Very poor</span>
              <span class="text-xs text-gray-400">Excellent</span>
            </div>

            <!-- Comment -->
            <div class="mb-6">
              <label for="rating-comment" class="block text-sm font-medium text-gray-800 mb-1.5">
                Tell us more <span class="text-gray-400">(optional)</span>
              </label>
              <textarea
                id="rating-comment"
                v-model="ratingComment"
                rows="3"
                maxlength="500"
                placeholder="What could we improve?"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
              />
              <p class="mt-1 text-xs text-gray-400 text-right">{{ ratingComment.length }}/500</p>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
              <button
                @click="submitRating"
                :disabled="!ratingScore"
                class="w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Submit feedback
              </button>
              <button
                @click="closeRating"
                class="w-full px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg"
              >
                Skip — Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

.animate-bounce {
  animation: bounce 1s ease-in-out infinite;
}
</style>
