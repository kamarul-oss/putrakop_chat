<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import StatusBadge from '@/Pages/components/StatusBadge.vue'
import {
  ChatBubbleLeftRightIcon,
  ClockIcon,
  StarIcon,
  UsersIcon,
  ArrowPathIcon,
  ArrowRightCircleIcon,
  XCircleIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
})

// State
const stats = ref({
  activeConversations: 12,
  queueSize: 5,
  avgWaitTime: '2m 30s',
  csatScore: 4.6,
  activeConversationsTrend: 8,
  queueSizeTrend: -3,
  avgWaitTimeTrend: -12,
  csatScoreTrend: 0.2,
})

const agents = ref([
  { id: 1, name: 'Siti Aminah', status: 'online', activeChats: 3, department: 'Account Services' },
  { id: 2, name: 'Ahmad Khan', status: 'busy', activeChats: 2, department: 'Billing' },
  { id: 3, name: 'Fatimah Hassan', status: 'away', activeChats: 0, department: 'Account Services' },
  { id: 4, name: 'Mohd Rashid', status: 'online', activeChats: 4, department: 'Loans' },
  { id: 5, name: 'Nurul Izzah', status: 'offline', activeChats: 0, department: 'Technical Support' },
  { id: 6, name: 'Azman Abdullah', status: 'online', activeChats: 2, department: 'Billing' },
])

const conversations = ref([
  {
    id: 1,
    customer: 'Ahmad bin Ali',
    agent: 'Siti Aminah',
    department: 'Account Services',
    status: 'active',
    waitTime: '1m 30s',
    lastMessage: 'I need help with my account balance',
    startedAt: new Date(Date.now() - 300000).toISOString(),
  },
  {
    id: 2,
    customer: 'Siti Aminah',
    agent: 'Ahmad Khan',
    department: 'Billing',
    status: 'active',
    waitTime: '0m 45s',
    lastMessage: 'Can you send me the invoice?',
    startedAt: new Date(Date.now() - 600000).toISOString(),
  },
  {
    id: 3,
    customer: 'Mohd Faizal',
    agent: null,
    department: 'Loans',
    status: 'queued',
    waitTime: '5m 12s',
    lastMessage: 'When will my loan be approved?',
    startedAt: new Date(Date.now() - 120000).toISOString(),
  },
  {
    id: 4,
    customer: 'Nurul Aisyah',
    agent: 'Mohd Rashid',
    department: 'Account Services',
    status: 'active',
    waitTime: '0m 20s',
    lastMessage: 'Thank you for the quick response!',
    startedAt: new Date(Date.now() - 900000).toISOString(),
  },
  {
    id: 5,
    customer: 'Zainal Abidin',
    agent: null,
    department: 'Technical Support',
    status: 'queued',
    waitTime: '8m 45s',
    lastMessage: 'My mobile app is not working',
    startedAt: new Date(Date.now() - 60000).toISOString(),
  },
])

const searchQuery = ref('')
const statusFilter = ref('all')
const departmentFilter = ref('all')

const filteredConversations = computed(() => {
  let filtered = conversations.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (c) =>
        c.customer.toLowerCase().includes(query) ||
        c.lastMessage.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter((c) => c.status === statusFilter.value)
  }

  if (departmentFilter.value !== 'all') {
    filtered = filtered.filter((c) => c.department === departmentFilter.value)
  }

  return filtered
})

const onlineAgents = computed(() => agents.value.filter((a) => a.status === 'online').length)
const totalAgents = computed(() => agents.value.length)
const queuedConversations = computed(() => conversations.value.filter((c) => c.status === 'queued').length)

const interveneConversation = (conversation) => {
  console.log('Intervene in conversation', conversation.id)
}

const reassignConversation = (conversation) => {
  console.log('Reassign conversation', conversation.id)
}

const closeConversation = (conversation) => {
  conversation.status = 'closed'
}

onMounted(() => {
  // In a real app, this would fetch data from API
  // Could also set up WebSocket listeners for real-time updates
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Monitor live conversations and agent performance</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">
              {{ onlineAgents }}/{{ totalAgents }} agents online
            </span>
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
          </div>
        </div>
      </div>
    </div>

    <div class="px-6 py-6 max-w-[1400px] mx-auto space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Conversations -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
              <ChatBubbleLeftRightIcon class="w-5 h-5 text-primary-700" />
            </div>
            <div
              v-if="stats.activeConversationsTrend > 0"
              class="flex items-center gap-0.5 text-xs font-medium text-green-700"
            >
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.activeConversationsTrend }}%
            </div>
            <div
              v-else-if="stats.activeConversationsTrend < 0"
              class="flex items-center gap-0.5 text-xs font-medium text-red-700"
            >
              <ArrowTrendingDownIcon class="w-3.5 h-3.5" />
              {{ Math.abs(stats.activeConversationsTrend) }}%
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active Conversations</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.activeConversations }}</p>
        </div>

        <!-- Queue Size -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
              <ClockIcon class="w-5 h-5 text-amber-700" />
            </div>
            <div
              v-if="stats.queueSizeTrend < 0"
              class="flex items-center gap-0.5 text-xs font-medium text-green-700"
            >
              <ArrowTrendingDownIcon class="w-3.5 h-3.5" />
              {{ Math.abs(stats.queueSizeTrend) }}%
            </div>
            <div
              v-else
              class="flex items-center gap-0.5 text-xs font-medium text-red-700"
            >
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.queueSizeTrend }}%
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Queue Size</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.queueSize }}</p>
        </div>

        <!-- Avg Wait Time -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
              <ClockIcon class="w-5 h-5 text-blue-700" />
            </div>
            <div
              v-if="stats.avgWaitTimeTrend < 0"
              class="flex items-center gap-0.5 text-xs font-medium text-green-700"
            >
              <ArrowTrendingDownIcon class="w-3.5 h-3.5" />
              {{ Math.abs(stats.avgWaitTimeTrend) }}%
            </div>
            <div
              v-else
              class="flex items-center gap-0.5 text-xs font-medium text-red-700"
            >
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.avgWaitTimeTrend }}%
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Wait Time</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.avgWaitTime }}</p>
        </div>

        <!-- CSAT Score -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
              <StarIcon class="w-5 h-5 text-green-700" />
            </div>
            <div
              v-if="stats.csatScoreTrend > 0"
              class="flex items-center gap-0.5 text-xs font-medium text-green-700"
            >
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              +{{ stats.csatScoreTrend }}
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">CSAT Score</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.csatScore }}<span class="text-lg text-gray-400">/5</span></p>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Live Conversations Table -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-base font-semibold text-gray-900">Live Conversations</h2>
              <span class="text-sm text-gray-500">{{ filteredConversations.length }} conversations</span>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2">
              <div class="relative flex-1 min-w-[200px]">
                <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search conversations..."
                  class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
              <select
                v-model="statusFilter"
                class="px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="queued">Queued</option>
                <option value="closed">Closed</option>
              </select>
              <select
                v-model="departmentFilter"
                class="px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="all">All Departments</option>
                <option value="Account Services">Account Services</option>
                <option value="Billing">Billing</option>
                <option value="Loans">Loans</option>
                <option value="Technical Support">Technical Support</option>
              </select>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Customer
                  </th>
                  <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Agent
                  </th>
                  <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Wait Time
                  </th>
                  <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="conversation in filteredConversations"
                  :key="conversation.id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-5 py-3">
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ conversation.customer }}</p>
                      <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ conversation.lastMessage }}</p>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <span
                      v-if="conversation.agent"
                      class="text-sm text-gray-700"
                    >
                      {{ conversation.agent }}
                    </span>
                    <span v-else class="text-xs text-gray-400 italic">Unassigned</span>
                  </td>
                  <td class="px-5 py-3">
                    <StatusBadge :status="conversation.status" size="sm" />
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-sm text-gray-600">{{ conversation.waitTime }}</span>
                  </td>
                  <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <button
                        v-if="conversation.status === 'active'"
                        @click="interveneConversation(conversation)"
                        class="p-1.5 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                        title="Intervene"
                      >
                        <ArrowRightCircleIcon class="w-4 h-4" />
                      </button>
                      <button
                        @click="reassignConversation(conversation)"
                        class="p-1.5 text-gray-400 hover:text-amber-700 hover:bg-amber-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500"
                        title="Reassign"
                      >
                        <ArrowPathIcon class="w-4 h-4" />
                      </button>
                      <button
                        v-if="conversation.status !== 'closed'"
                        @click="closeConversation(conversation)"
                        class="p-1.5 text-gray-400 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                        title="Close"
                      >
                        <XCircleIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Agent Status Grid -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Agent Status</h2>
            <p class="mt-1 text-xs text-gray-500">{{ onlineAgents }} online · {{ totalAgents - onlineAgents }} offline/away</p>
          </div>

          <div class="p-4 space-y-2">
            <div
              v-for="agent in agents"
              :key="agent.id"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div class="relative">
                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary-700">
                    {{ agent.name.split(' ').map(n => n[0]).join('') }}
                  </span>
                </div>
                <span
                  class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white"
                  :class="{
                    'bg-green-500': agent.status === 'online',
                    'bg-amber-500': agent.status === 'away',
                    'bg-red-500': agent.status === 'busy',
                    'bg-gray-400': agent.status === 'offline',
                  }"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ agent.name }}</p>
                <p class="text-xs text-gray-500">{{ agent.department }}</p>
              </div>
              <div class="text-right">
                <StatusBadge :status="agent.status" size="sm" />
                <p v-if="agent.activeChats > 0" class="text-xs text-gray-500 mt-0.5">
                  {{ agent.activeChats }} chats
                </p>
              </div>
            </div>
          </div>

          <!-- Queue Visualization -->
          <div class="border-t border-gray-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Queue</h3>
            <div v-if="queuedConversations > 0" class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Customers waiting</span>
                <span class="font-medium text-gray-900">{{ queuedConversations }}</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-2">
                <div
                  class="bg-primary-700 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${Math.min((queuedConversations / 10) * 100, 100)}%` }"
                />
              </div>
              <p class="text-xs text-gray-500">
                Estimated wait: ~{{ Math.ceil(queuedConversations * 2) }} min
              </p>
            </div>
            <div v-else class="text-center py-4">
              <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <p class="text-sm text-gray-600">All caught up!</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
