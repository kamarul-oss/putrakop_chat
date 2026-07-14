<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ChatMessage from '@/Pages/components/ChatMessage.vue'
import StatusBadge from '@/Pages/components/StatusBadge.vue'
import {
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  PaperClipIcon,
  FaceSmileIcon,
  ChatBubbleLeftRightIcon,
  ArrowPathIcon,
  UserGroupIcon,
  XMarkIcon,
  EllipsisVerticalIcon,
  InformationCircleIcon,
  PencilSquareIcon,
  ArrowRightCircleIcon,
  CheckCircleIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  conversations: { type: Array, default: () => [] },
  activeConversation: { type: Object, default: null },
})

// State
const conversations = ref(props.conversations || [])
const selectedConversation = ref(props.activeConversation)
const messages = ref([])
const newMessage = ref('')
const isInternalNote = ref(false)
const isTyping = ref(false)
const searchQuery = ref('')
const activeTab = ref('mine')
const agentStatus = ref('online')
const showStatusMenu = ref(false)
const showRightPanel = ref(false)
const showMobileMenu = ref(false)
const isSending = ref(false)
const messagesContainer = ref(null)
const internalNotes = ref([])
const newNote = ref('')
const rightPanelTab = ref('info')

// Mock data for demo
const mockConversations = [
  {
    id: 1,
    customer: { name: 'Ahmad bin Ali', email: 'ahmad@email.com', phone: '+60 12-345-6789' },
    last_message: 'I need help with my account balance',
    last_message_time: new Date(Date.now() - 120000).toISOString(),
    unread_count: 2,
    status: 'active',
    department: 'Account Services',
    duration: 300,
  },
  {
    id: 2,
    customer: { name: 'Siti Aminah', email: 'siti@email.com', phone: '+60 19-876-5432' },
    last_message: 'Thank you for the help!',
    last_message_time: new Date(Date.now() - 900000).toISOString(),
    unread_count: 0,
    status: 'active',
    department: 'Billing',
    duration: 600,
  },
  {
    id: 3,
    customer: { name: 'Mohd Faizal', email: 'faizal@email.com', phone: '+60 11-234-5678' },
    last_message: 'When will my loan be approved?',
    last_message_time: new Date(Date.now() - 3600000).toISOString(),
    unread_count: 0,
    status: 'queued',
    department: 'Loans',
    duration: 0,
  },
]

const mockMessages = [
  {
    id: 'sys-1',
    sender_type: 'system',
    content: 'Chat started at 2:30 PM',
    created_at: new Date(Date.now() - 300000).toISOString(),
    type: 'text',
  },
  {
    id: 'c-1',
    sender_type: 'customer',
    sender_name: 'Ahmad bin Ali',
    content: 'Hi, I need help with my account balance. It shows a different amount than expected.',
    created_at: new Date(Date.now() - 280000).toISOString(),
    type: 'text',
    status: 'read',
  },
  {
    id: 'a-1',
    sender_type: 'agent',
    sender_name: props.user?.name || 'Agent',
    content: 'Hello Ahmad! I\'d be happy to help you with your account balance. Can you provide your account number so I can look into this?',
    created_at: new Date(Date.now() - 260000).toISOString(),
    type: 'text',
  },
  {
    id: 'c-2',
    sender_type: 'customer',
    sender_name: 'Ahmad bin Ali',
    content: 'Sure, it\'s 1234567890',
    created_at: new Date(Date.now() - 240000).toISOString(),
    type: 'text',
    status: 'read',
  },
]

const mockNotes = [
  {
    id: 'n-1',
    author: props.user?.name || 'Agent',
    content: 'Account verified, balance issue confirmed - Tier 2',
    created_at: new Date(Date.now() - 250000).toISOString(),
  },
]

onMounted(() => {
  conversations.value = mockConversations
  if (conversations.value.length > 0 && !selectedConversation.value) {
    selectConversation(conversations.value[0])
  }

  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})

const handleKeydown = (e) => {
  // Ctrl+K for search
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    // Focus search
  }
  // Ctrl+/ for quick replies
  if ((e.ctrlKey || e.metaKey) && e.key === '/') {
    e.preventDefault()
    // Show quick replies
  }
}

const filteredConversations = computed(() => {
  let filtered = conversations.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (c) =>
        c.customer.name.toLowerCase().includes(query) ||
        c.last_message.toLowerCase().includes(query)
    )
  }

  if (activeTab.value === 'mine') {
    filtered = filtered.filter((c) => c.agent_id === props.user?.id)
  } else if (activeTab.value === 'queue') {
    filtered = filtered.filter((c) => c.status === 'queued')
  }

  return filtered
})

const selectConversation = (conversation) => {
  selectedConversation.value = conversation
  messages.value = mockMessages
  internalNotes.value = mockNotes
  showMobileMenu.value = false

  // Mark as read
  conversation.unread_count = 0
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return

  const content = newMessage.value.trim()
  newMessage.value = ''
  isSending.value = true

  if (isInternalNote.value) {
    internalNotes.value.push({
      id: 'n-' + Date.now(),
      author: props.user?.name || 'Agent',
      content,
      created_at: new Date().toISOString(),
    })
    newNote.value = ''
    isSending.value = false
    return
  }

  const tempMessage = {
    id: 'temp-' + Date.now(),
    sender_type: 'agent',
    sender_name: props.user?.name || 'Agent',
    content,
    created_at: new Date().toISOString(),
    status: 'sending',
    type: 'text',
  }

  messages.value.push(tempMessage)
  scrollToBottom()

  try {
    await new Promise((resolve) => setTimeout(resolve, 400))

    const msgIndex = messages.value.findIndex((m) => m.id === tempMessage.id)
    if (msgIndex !== -1) {
      messages.value[msgIndex].status = 'sent'
    }

    // Update conversation preview
    if (selectedConversation.value) {
      selectedConversation.value.last_message = content
      selectedConversation.value.last_message_time = new Date().toISOString()
    }
  } catch {
    const msgIndex = messages.value.findIndex((m) => m.id === tempMessage.id)
    if (msgIndex !== -1) {
      messages.value[msgIndex].status = 'failed'
    }
  } finally {
    isSending.value = false
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const isOwnMessage = (message) => {
  return message.sender_type === 'agent'
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

const relativeTime = (timestamp) => {
  if (!timestamp) return ''
  const diff = Date.now() - new Date(timestamp).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  const mins = Math.floor(seconds / 60)
  if (mins < 60) return `${mins}m`
  return `${Math.floor(mins / 60)}h ${mins % 60}m`
}

const changeStatus = (status) => {
  agentStatus.value = status
  showStatusMenu.value = false
}

const acceptConversation = (conversation) => {
  conversation.status = 'active'
  conversation.agent_id = props.user?.id
}

const transferConversation = (conversation) => {
  // Open transfer modal
  console.log('Transfer conversation', conversation.id)
}

const closeConversation = (conversation) => {
  conversation.status = 'closed'
}

const addNote = () => {
  if (!newNote.value.trim()) return
  internalNotes.value.push({
    id: 'n-' + Date.now(),
    author: props.user?.name || 'Agent',
    content: newNote.value.trim(),
    created_at: new Date().toISOString(),
  })
  newNote.value = ''
}

watch(
  () => messages.value.length,
  () => {
    scrollToBottom()
  }
)
</script>

<template>
  <div class="flex h-screen bg-gray-50 overflow-hidden">
    <!-- Left Sidebar - Conversation List -->
    <div
      class="w-[280px] bg-white border-r border-gray-200 flex flex-col flex-shrink-0"
      :class="{
        'fixed inset-y-0 left-0 z-40 w-72 shadow-xl lg:relative lg:shadow-none': showMobileMenu,
        'hidden lg:flex': !showMobileMenu,
      }"
    >
      <!-- Sidebar Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
        <h1 class="text-base font-semibold text-gray-900">Conversations</h1>
        <button
          class="lg:hidden p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
          @click="showMobileMenu = false"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Search -->
      <div class="px-3 py-2 border-b border-gray-100">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search conversations..."
            class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200">
        <button
          v-for="tab in [
            { id: 'all', label: 'All', count: conversations.length },
            { id: 'mine', label: 'Mine', count: conversations.filter((c) => c.agent_id === user?.id).length },
            { id: 'queue', label: 'Queue', count: conversations.filter((c) => c.status === 'queued').length },
          ]"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="flex-1 px-3 py-2 text-xs font-medium text-center transition-colors border-b-2"
          :class="
            activeTab === tab.id
              ? 'text-primary-700 border-primary-700'
              : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'
          "
        >
          {{ tab.label }}
          <span
            v-if="tab.count > 0"
            class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
            :class="activeTab === tab.id ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="filteredConversations.length === 0" class="p-6 text-center">
          <ChatBubbleLeftRightIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
          <p class="text-sm text-gray-500">No conversations found</p>
        </div>

        <button
          v-for="conversation in filteredConversations"
          :key="conversation.id"
          @click="selectConversation(conversation)"
          class="w-full flex items-start gap-3 px-3 py-3 text-left hover:bg-gray-50 transition-colors border-b border-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
          :class="{
            'bg-primary-50 border-l-2 border-l-primary-700': selectedConversation?.id === conversation.id,
          }"
        >
          <!-- Avatar -->
          <div class="relative flex-shrink-0">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-medium text-primary-700">
                {{ conversation.customer.name.charAt(0) }}
              </span>
            </div>
            <span
              v-if="conversation.unread_count > 0"
              class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
            >
              {{ conversation.unread_count > 9 ? '9+' : conversation.unread_count }}
            </span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p
                class="text-sm font-medium text-gray-900 truncate"
                :class="{ 'font-bold': conversation.unread_count > 0 }"
              >
                {{ conversation.customer.name }}
              </p>
              <span class="text-xs text-gray-400 flex-shrink-0">
                {{ relativeTime(conversation.last_message_time) }}
              </span>
            </div>
            <p class="text-xs text-gray-500 truncate mt-0.5">
              {{ conversation.last_message }}
            </p>
            <div class="flex items-center gap-2 mt-1">
              <StatusBadge :status="conversation.status" size="sm" />
              <span class="text-xs text-gray-400">{{ conversation.department }}</span>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div
      v-if="showMobileMenu"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="showMobileMenu = false"
    />

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Chat Header -->
      <div class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-3">
          <button
            class="lg:hidden p-1.5 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
            @click="showMobileMenu = true"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div v-if="selectedConversation" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-medium text-primary-700">
                {{ selectedConversation.customer.name.charAt(0) }}
              </span>
            </div>
            <div>
              <h2 class="text-sm font-semibold text-gray-900">
                {{ selectedConversation.customer.name }}
              </h2>
              <div class="flex items-center gap-2">
                <StatusBadge :status="selectedConversation.status" size="sm" />
                <span class="text-xs text-gray-500">
                  {{ selectedConversation.department }} · {{ formatDuration(selectedConversation.duration) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else>
            <h2 class="text-sm font-semibold text-gray-900">Select a conversation</h2>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <button
            v-if="selectedConversation?.status === 'queued'"
            @click="acceptConversation(selectedConversation)"
            class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
          >
            Accept
          </button>
          <button
            v-if="selectedConversation?.status === 'active'"
            @click="transferConversation(selectedConversation)"
            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Transfer
          </button>
          <button
            v-if="selectedConversation?.status === 'active'"
            @click="closeConversation(selectedConversation)"
            class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-md hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            Close
          </button>
          <button
            @click="showRightPanel = !showRightPanel"
            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
            :class="{ 'text-primary-700 bg-primary-50': showRightPanel }"
          >
            <InformationCircleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Messages Area -->
      <div
        v-if="selectedConversation"
        ref="messagesContainer"
        class="flex-1 overflow-y-auto px-4 py-3 space-y-1 bg-gray-50"
      >
        <template v-for="(message, index) in messages" :key="message.id">
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

        <!-- Internal Notes Inline -->
        <div v-if="internalNotes.length > 0" class="mt-4 space-y-2">
          <div class="flex items-center gap-2 text-xs text-gray-500 font-medium">
            <PencilSquareIcon class="w-3.5 h-3.5" />
            Internal Notes
          </div>
          <div
            v-for="note in internalNotes"
            :key="note.id"
            class="bg-yellow-50 border border-dashed border-yellow-300 rounded-lg px-3 py-2"
          >
            <p class="text-xs text-gray-700 italic">{{ note.content }}</p>
            <p class="text-[10px] text-gray-400 mt-1">
              {{ note.author }} · {{ formattedTime(note.created_at) }}
            </p>
          </div>
        </div>

        <!-- Typing Indicator -->
        <div v-if="isTyping" class="flex items-end gap-2 mt-2">
          <div class="w-7 h-7 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-xs font-medium text-primary-700">C</span>
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

      <!-- Empty State -->
      <div v-else class="flex-1 flex items-center justify-center bg-gray-50">
        <div class="text-center">
          <ChatBubbleLeftRightIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <h3 class="text-base font-medium text-gray-900 mb-1">No conversation selected</h3>
          <p class="text-sm text-gray-500">Choose a conversation from the sidebar</p>
        </div>
      </div>

      <!-- Input Area -->
      <div v-if="selectedConversation" class="flex-shrink-0 border-t border-gray-200 bg-white">
        <!-- Quick Reply Bar -->
        <div class="flex items-center gap-1 px-3 pt-2 pb-1 overflow-x-auto">
          <button class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-primary-50 hover:text-primary-700 transition-colors whitespace-nowrap">
            Greeting
          </button>
          <button class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-primary-50 hover:text-primary-700 transition-colors whitespace-nowrap">
            FAQ
          </button>
          <button class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-primary-50 hover:text-primary-700 transition-colors whitespace-nowrap">
            Escalate
          </button>
          <button class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full hover:bg-primary-50 hover:text-primary-700 transition-colors whitespace-nowrap">
            + Custom
          </button>
        </div>

        <!-- Input -->
        <div class="flex items-end gap-2 px-3 py-2">
          <button
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 flex-shrink-0"
            aria-label="Attach file"
          >
            <PaperClipIcon class="w-5 h-5" />
          </button>

          <!-- Internal Note Toggle -->
          <button
            @click="isInternalNote = !isInternalNote"
            class="p-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 flex-shrink-0"
            :class="
              isInternalNote
                ? 'text-yellow-600 bg-yellow-50 hover:bg-yellow-100'
                : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'
            "
            :title="isInternalNote ? 'Internal note mode (visible only to agents)' : 'Switch to internal note mode'"
          >
            <PencilSquareIcon class="w-5 h-5" />
          </button>

          <div class="flex-1 relative">
            <textarea
              v-model="newMessage"
              @keydown.enter.exact.prevent="sendMessage"
              :placeholder="isInternalNote ? 'Add an internal note...' : 'Type a message...'"
              rows="1"
              class="w-full resize-none border rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
              :class="
                isInternalNote
                  ? 'border-yellow-300 bg-yellow-50 focus:border-yellow-400'
                  : 'border-gray-200 focus:border-primary-500'
              "
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
    </div>

    <!-- Right Panel - Customer Info -->
    <div
      v-if="showRightPanel && selectedConversation"
      class="w-[320px] bg-white border-l border-gray-200 flex flex-col flex-shrink-0 hidden lg:flex"
    >
      <!-- Panel Tabs -->
      <div class="flex border-b border-gray-200">
        <button
          v-for="tab in [
            { id: 'info', label: 'Info', icon: InformationCircleIcon },
            { id: 'notes', label: 'Notes', icon: PencilSquareIcon },
          ]"
          :key="tab.id"
          @click="rightPanelTab = tab.id"
          class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-medium border-b-2 transition-colors"
          :class="
            rightPanelTab === tab.id
              ? 'text-primary-700 border-primary-700'
              : 'text-gray-500 border-transparent hover:text-gray-700'
          "
        >
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Customer Info Tab -->
      <div v-if="rightPanelTab === 'info'" class="flex-1 overflow-y-auto p-4 space-y-6">
        <!-- Customer Details -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            Customer Details
          </h3>
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-lg font-medium text-primary-700">
                {{ selectedConversation.customer.name.charAt(0) }}
              </span>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">{{ selectedConversation.customer.name }}</p>
              <p class="text-xs text-gray-500">{{ selectedConversation.customer.email }}</p>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Phone:</span>
              <span class="text-gray-900">{{ selectedConversation.customer.phone }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Department:</span>
              <span class="text-gray-900">{{ selectedConversation.department }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Member since:</span>
              <span class="text-gray-900">Jan 2024</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Previous chats:</span>
              <span class="text-gray-900">5</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Last rating:</span>
              <span class="text-gray-900">4/5 ⭐</span>
            </div>
          </div>
        </div>

        <!-- Tags -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            Tags
          </h3>
          <div class="flex flex-wrap gap-1.5">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">
              Billing
              <button class="text-gray-400 hover:text-gray-600">×</button>
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">
              Urgent
              <button class="text-gray-400 hover:text-gray-600">×</button>
            </span>
            <button class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium text-gray-500 hover:text-gray-700 border border-dashed border-gray-300 rounded hover:border-gray-400 transition-colors">
              <PlusIcon class="w-3 h-3" />
              Add
            </button>
          </div>
        </div>

        <!-- Quick Actions -->
        <div>
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            Quick Actions
          </h3>
          <div class="space-y-2">
            <button class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-left">
              <ArrowPathIcon class="w-4 h-4 text-gray-400" />
              Transfer Chat
            </button>
            <button class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-left">
              <ChatBubbleLeftRightIcon class="w-4 h-4 text-gray-400" />
              View History
            </button>
            <button class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors text-left">
              <XMarkIcon class="w-4 h-4" />
              Block User
            </button>
          </div>
        </div>
      </div>

      <!-- Notes Tab -->
      <div v-if="rightPanelTab === 'notes'" class="flex-1 overflow-y-auto p-4">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
          Internal Notes
        </h3>
        <p class="text-xs text-gray-400 mb-3">
          Not visible to the customer
        </p>

        <div class="space-y-3 mb-4">
          <div
            v-for="note in internalNotes"
            :key="note.id"
            class="bg-yellow-50 border border-dashed border-yellow-300 rounded-lg p-3"
          >
            <p class="text-xs text-gray-700 italic">{{ note.content }}</p>
            <p class="text-[10px] text-gray-400 mt-2">
              {{ note.author }} · {{ formattedTime(note.created_at) }}
            </p>
          </div>

          <div v-if="internalNotes.length === 0" class="text-center py-4">
            <PencilSquareIcon class="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p class="text-xs text-gray-500">No notes yet</p>
          </div>
        </div>

        <!-- Add Note -->
        <div class="border-t border-gray-200 pt-3">
          <textarea
            v-model="newNote"
            placeholder="Add a note..."
            rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none mb-2"
          />
          <button
            @click="addNote"
            :disabled="!newNote.trim()"
            class="w-full px-3 py-1.5 text-xs font-medium text-white bg-primary-700 rounded-md hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
          >
            Add Note
          </button>
        </div>
      </div>
    </div>
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
