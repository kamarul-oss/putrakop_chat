<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import {
  ChatBubbleLeftRightIcon,
  PaperAirplaneIcon,
  StarIcon,
  UsersIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  ArrowDownTrayIcon,
  CalendarDaysIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
})

const dateRange = ref('30d')
const chartCanvasConversations = ref(null)
const chartCanvasMessages = ref(null)
const chartCanvasRatings = ref(null)
const chartCanvasAgents = ref(null)
let chartsInitialized = false

// Stats
const stats = ref({
  totalConversations: 1247,
  totalMessages: 8432,
  avgRating: 4.6,
  totalAgents: 12,
  conversationsTrend: 12.5,
  messagesTrend: 8.3,
  ratingTrend: 0.3,
  agentsTrend: 2,
})

// Department comparison data
const departmentStats = ref([
  { name: 'Account Services', conversations: 485, messages: 3210, avgRating: 4.7, avgResponseTime: '1m 30s' },
  { name: 'Billing', conversations: 312, messages: 2156, avgRating: 4.5, avgResponseTime: '2m 10s' },
  { name: 'Loans', conversations: 267, messages: 1890, avgRating: 4.4, avgResponseTime: '2m 45s' },
  { name: 'Technical Support', conversations: 183, messages: 1176, avgRating: 4.8, avgResponseTime: '1m 15s' },
])

// Chart data generators
const getConversationData = () => {
  const days = dateRange.value === '7d' ? 7 : dateRange.value === '30d' ? 30 : 90
  const labels = []
  const data = []
  const now = new Date()
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date(now)
    date.setDate(date.getDate() - i)
    labels.push(date.toLocaleDateString('en-MY', { month: 'short', day: 'numeric' }))
    data.push(Math.floor(Math.random() * 40) + 20)
  }
  return { labels, data }
}

const getMessageData = () => {
  const days = dateRange.value === '7d' ? 7 : dateRange.value === '30d' ? 30 : 90
  const labels = []
  const data = []
  const now = new Date()
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date(now)
    date.setDate(date.getDate() - i)
    labels.push(date.toLocaleDateString('en-MY', { month: 'short', day: 'numeric' }))
    data.push(Math.floor(Math.random() * 200) + 100)
  }
  return { labels, data }
}

const getRatingData = () => ({
  labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
  data: [5, 12, 45, 180, 258],
})

const getAgentData = () => ({
  labels: ['Siti A.', 'Ahmad K.', 'Fatimah H.', 'Mohd R.', 'Nurul I.', 'Azman A.'],
  data: [89, 76, 82, 91, 68, 85],
})

const initCharts = async () => {
  if (typeof Chart === 'undefined') return

  await nextTick()

  // Conversations Chart
  if (chartCanvasConversations.value) {
    const convData = getConversationData()
    new Chart(chartCanvasConversations.value, {
      type: 'line',
      data: {
        labels: convData.labels,
        datasets: [{
          label: 'Conversations',
          data: convData.data,
          borderColor: '#1D4ED8',
          backgroundColor: 'rgba(29, 78, 216, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
          y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } },
        },
      },
    })
  }

  // Messages Chart
  if (chartCanvasMessages.value) {
    const msgData = getMessageData()
    new Chart(chartCanvasMessages.value, {
      type: 'line',
      data: {
        labels: msgData.labels,
        datasets: [{
          label: 'Messages',
          data: msgData.data,
          borderColor: '#059669',
          backgroundColor: 'rgba(5, 150, 105, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
          y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } },
        },
      },
    })
  }

  // Rating Chart
  if (chartCanvasRatings.value) {
    const ratingData = getRatingData()
    new Chart(chartCanvasRatings.value, {
      type: 'bar',
      data: {
        labels: ratingData.labels,
        datasets: [{
          label: 'Responses',
          data: ratingData.data,
          backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#3B82F6', '#10B981'],
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } },
        },
      },
    })
  }

  // Agent Performance Chart
  if (chartCanvasAgents.value) {
    const agentData = getAgentData()
    new Chart(chartCanvasAgents.value, {
      type: 'bar',
      data: {
        labels: agentData.labels,
        datasets: [{
          label: 'CSAT Score',
          data: agentData.data,
          backgroundColor: '#1D4ED8',
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: { beginAtZero: true, max: 100, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } },
        },
      },
    })
  }
}

const destroyCharts = () => {
  // Charts will be garbage collected with the DOM
}

const changeDateRange = (range) => {
  dateRange.value = range
  destroyCharts()
  chartsInitialized = false
  initCharts()
}

onMounted(() => {
  // Load Chart.js dynamically
  if (typeof Chart === 'undefined') {
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js'
    script.onload = () => {
      initCharts()
      chartsInitialized = true
    }
    document.head.appendChild(script)
  } else {
    initCharts()
    chartsInitialized = true
  }
})

const exportReport = () => {
  const report = {
    generated_at: new Date().toISOString(),
    date_range: dateRange.value,
    summary: stats.value,
    departments: departmentStats.value,
  }
  const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `analytics-report-${dateRange.value}-${new Date().toISOString().split('T')[0]}.json`
  link.click()
}
</script>

<template>
  <AdminLayout :user="user" title="Analytics">
    <div class="px-6 py-6 max-w-[1400px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Analytics</h1>
          <p class="mt-1 text-sm text-gray-600">Performance insights and system metrics</p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Date Range Selector -->
          <div class="flex items-center bg-white border border-gray-200 rounded-lg p-0.5">
            <button
              @click="changeDateRange('7d')"
              class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="
                dateRange === '7d'
                  ? 'bg-primary-700 text-white'
                  : 'text-gray-600 hover:text-gray-900'
              "
            >
              7 Days
            </button>
            <button
              @click="changeDateRange('30d')"
              class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="
                dateRange === '30d'
                  ? 'bg-primary-700 text-white'
                  : 'text-gray-600 hover:text-gray-900'
              "
            >
              30 Days
            </button>
            <button
              @click="changeDateRange('90d')"
              class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="
                dateRange === '90d'
                  ? 'bg-primary-700 text-white'
                  : 'text-gray-600 hover:text-gray-900'
              "
            >
              90 Days
            </button>
          </div>
          <button
            @click="exportReport"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export Report
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Conversations -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
              <ChatBubbleLeftRightIcon class="w-5 h-5 text-primary-700" />
            </div>
            <div class="flex items-center gap-0.5 text-xs font-medium text-green-700">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.conversationsTrend }}%
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Conversations</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.totalConversations.toLocaleString() }}</p>
        </div>

        <!-- Total Messages -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
              <PaperAirplaneIcon class="w-5 h-5 text-green-700" />
            </div>
            <div class="flex items-center gap-0.5 text-xs font-medium text-green-700">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.messagesTrend }}%
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Messages</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.totalMessages.toLocaleString() }}</p>
        </div>

        <!-- Avg Rating -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
              <StarIcon class="w-5 h-5 text-amber-700" />
            </div>
            <div class="flex items-center gap-0.5 text-xs font-medium text-green-700">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              +{{ stats.ratingTrend }}
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Rating</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.avgRating }}<span class="text-lg text-gray-400">/5</span></p>
        </div>

        <!-- Active Agents -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
              <UsersIcon class="w-5 h-5 text-purple-700" />
            </div>
            <div class="flex items-center gap-0.5 text-xs font-medium text-green-700">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              {{ stats.agentsTrend }}
            </div>
          </div>
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active Agents</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.totalAgents }}</p>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Conversations Over Time -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">Conversations Over Time</h3>
          <div class="h-[280px]">
            <canvas ref="chartCanvasConversations" />
          </div>
        </div>

        <!-- Messages Over Time -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">Messages Over Time</h3>
          <div class="h-[280px]">
            <canvas ref="chartCanvasMessages" />
          </div>
        </div>

        <!-- Rating Distribution -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">Rating Distribution</h3>
          <div class="h-[280px]">
            <canvas ref="chartCanvasRatings" />
          </div>
        </div>

        <!-- Agent Performance -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">Agent Performance (CSAT)</h3>
          <div class="h-[280px]">
            <canvas ref="chartCanvasAgents" />
          </div>
        </div>
      </div>

      <!-- Department Comparison Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900">Department Comparison</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Department
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Conversations
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Messages
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Avg Rating
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Avg Response Time
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="dept in departmentStats"
                :key="dept.name"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-5 py-4">
                  <span class="text-sm font-medium text-gray-900">{{ dept.name }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-700">{{ dept.conversations.toLocaleString() }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-700">{{ dept.messages.toLocaleString() }}</span>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-1.5">
                    <StarIcon class="w-4 h-4 text-amber-500" />
                    <span class="text-sm font-medium text-gray-900">{{ dept.avgRating }}</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-700">{{ dept.avgResponseTime }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
