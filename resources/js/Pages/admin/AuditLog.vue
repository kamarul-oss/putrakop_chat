<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import {
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ArrowDownTrayIcon,
  ClipboardDocumentListIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  logs: { type: Array, default: () => [] },
})

// Mock data
const logs = ref([
  {
    id: 1,
    timestamp: '2024-12-12 14:32:10',
    user: 'Admin User',
    event: 'created',
    model: 'Department',
    model_id: 6,
    ip_address: '192.168.1.100',
    old_values: null,
    new_values: { name: 'VIP Services', description: 'Priority support for VIP members', status: 'active' },
  },
  {
    id: 2,
    timestamp: '2024-12-12 14:28:45',
    user: 'Kamal Abdullah',
    event: 'updated',
    model: 'User',
    model_id: 3,
    ip_address: '192.168.1.105',
    old_values: { department: 'Account Services', role: 'agent' },
    new_values: { department: 'Loans', role: 'agent' },
  },
  {
    id: 3,
    timestamp: '2024-12-12 13:15:22',
    user: 'Admin User',
    event: 'deleted',
    model: 'KnowledgeBase',
    model_id: 8,
    ip_address: '192.168.1.100',
    old_values: { title_en: 'Old FAQ Article', category: 'General', status: 'inactive' },
    new_values: null,
  },
  {
    id: 4,
    timestamp: '2024-12-12 11:05:00',
    user: 'Kamal Abdullah',
    event: 'updated',
    model: 'Setting',
    model_id: 1,
    ip_address: '192.168.1.105',
    old_values: { value: '30' },
    new_values: { value: '60' },
  },
  {
    id: 5,
    timestamp: '2024-12-12 09:42:33',
    user: 'Admin User',
    event: 'created',
    model: 'User',
    model_id: 8,
    ip_address: '192.168.1.100',
    old_values: null,
    new_values: { name: 'Nurul Izzah', email: 'nurul@putrakop.com', role: 'agent', department: 'Account Services' },
  },
  {
    id: 6,
    timestamp: '2024-12-11 16:20:11',
    user: 'Siti Aminah',
    event: 'updated',
    model: 'Conversation',
    model_id: 42,
    ip_address: '192.168.1.110',
    old_values: { status: 'active', agent_id: 2 },
    new_values: { status: 'closed', agent_id: 2 },
  },
  {
    id: 7,
    timestamp: '2024-12-11 14:08:55',
    user: 'Admin User',
    event: 'updated',
    model: 'Department',
    model_id: 4,
    ip_address: '192.168.1.100',
    old_values: { status: 'active' },
    new_values: { status: 'inactive' },
  },
  {
    id: 8,
    timestamp: '2024-12-11 10:30:00',
    user: 'Kamal Abdullah',
    event: 'created',
    model: 'KnowledgeBase',
    model_id: 5,
    ip_address: '192.168.1.105',
    old_values: null,
    new_values: { title_en: 'Mobile App Troubleshooting', category: 'Technical', status: 'active' },
  },
  {
    id: 9,
    timestamp: '2024-12-10 15:45:22',
    user: 'Admin User',
    event: 'deleted',
    model: 'User',
    model_id: 9,
    ip_address: '192.168.1.100',
    old_values: { name: 'Deleted User', email: 'deleted@putrakop.com', role: 'agent' },
    new_values: null,
  },
  {
    id: 10,
    timestamp: '2024-12-10 12:18:44',
    user: 'Kamal Abdullah',
    event: 'updated',
    model: 'Setting',
    model_id: 3,
    ip_address: '192.168.1.105',
    old_values: { value: 'true' },
    new_values: { value: 'false' },
  },
])

const searchQuery = ref('')
const eventFilter = ref('all')
const userFilter = ref('all')
const modelFilter = ref('all')
const dateFrom = ref('')
const dateTo = ref('')
const expandedRows = ref(new Set())
const currentPage = ref(1)
const perPage = ref(10)

const eventTypes = ['created', 'updated', 'deleted']
const modelTypes = ['Department', 'User', 'KnowledgeBase', 'Setting', 'Conversation']
const users = ['Admin User', 'Kamal Abdullah', 'Siti Aminah']

const filteredLogs = computed(() => {
  let filtered = logs.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (l) =>
        l.user.toLowerCase().includes(query) ||
        l.model.toLowerCase().includes(query) ||
        l.event.toLowerCase().includes(query) ||
        l.ip_address.includes(query)
    )
  }

  if (eventFilter.value !== 'all') {
    filtered = filtered.filter((l) => l.event === eventFilter.value)
  }

  if (userFilter.value !== 'all') {
    filtered = filtered.filter((l) => l.user === userFilter.value)
  }

  if (modelFilter.value !== 'all') {
    filtered = filtered.filter((l) => l.model === modelFilter.value)
  }

  if (dateFrom.value) {
    filtered = filtered.filter((l) => l.timestamp.split(' ')[0] >= dateFrom.value)
  }

  if (dateTo.value) {
    filtered = filtered.filter((l) => l.timestamp.split(' ')[0] <= dateTo.value)
  }

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredLogs.value.length / perPage.value))

const paginatedLogs = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredLogs.value.slice(start, start + perPage.value)
})

const toggleRow = (id) => {
  if (expandedRows.value.has(id)) {
    expandedRows.value.delete(id)
  } else {
    expandedRows.value.add(id)
  }
}

const isRowExpanded = (id) => expandedRows.value.has(id)

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const getEventClass = (event) => {
  switch (event) {
    case 'created':
      return 'bg-green-100 text-green-700 border-green-200'
    case 'updated':
      return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'deleted':
      return 'bg-red-100 text-red-700 border-red-200'
    default:
      return 'bg-gray-100 text-gray-600 border-gray-200'
  }
}

const formatJson = (obj) => {
  if (!obj) return '—'
  return JSON.stringify(obj, null, 2)
}

const exportCSV = () => {
  const headers = ['Timestamp', 'User', 'Event', 'Model', 'Model ID', 'IP Address']
  const rows = filteredLogs.value.map((l) => [
    l.timestamp,
    l.user,
    l.event,
    l.model,
    l.model_id,
    l.ip_address,
  ])

  const csvContent = [headers, ...rows].map((row) => row.map((cell) => `"${cell}"`).join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `audit-log-${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

const exportJSON = () => {
  const jsonContent = JSON.stringify(filteredLogs.value, null, 2)
  const blob = new Blob([jsonContent], { type: 'application/json' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `audit-log-${new Date().toISOString().split('T')[0]}.json`
  link.click()
}
</script>

<template>
  <AdminLayout :user="user" title="Audit Log">
    <div class="px-6 py-6 max-w-[1400px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Audit Log</h1>
          <p class="mt-1 text-sm text-gray-600">Track all system changes and user actions</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="exportCSV"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export CSV
          </button>
          <button
            @click="exportJSON"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export JSON
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative flex-1 min-w-[220px]">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search logs..."
              class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <select
            v-model="eventFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Events</option>
            <option v-for="event in eventTypes" :key="event" :value="event" class="capitalize">
              {{ event }}
            </option>
          </select>
          <select
            v-model="userFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Users</option>
            <option v-for="u in users" :key="u" :value="u">{{ u }}</option>
          </select>
          <select
            v-model="modelFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Models</option>
            <option v-for="m in modelTypes" :key="m" :value="m">{{ m }}</option>
          </select>
          <div class="flex items-center gap-2">
            <input
              v-model="dateFrom"
              type="date"
              class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              aria-label="From date"
            />
            <span class="text-sm text-gray-400">to</span>
            <input
              v-model="dateTo"
              type="date"
              class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              aria-label="To date"
            />
          </div>
        </div>
      </div>

      <!-- Results count -->
      <div class="mb-3">
        <p class="text-sm text-gray-500">
          Showing {{ paginatedLogs.length }} of {{ filteredLogs.length }} entries
        </p>
      </div>

      <!-- Audit Log Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8" />
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Timestamp
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  User
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Event
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Model
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  IP Address
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template v-for="log in paginatedLogs" :key="log.id">
                <tr
                  class="hover:bg-gray-50 transition-colors cursor-pointer"
                  @click="toggleRow(log.id)"
                >
                  <td class="px-5 py-3">
                    <button
                      class="p-0.5 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                      :aria-label="isRowExpanded(log.id) ? 'Collapse row' : 'Expand row'"
                    >
                      <ChevronUpIcon
                        v-if="isRowExpanded(log.id)"
                        class="w-4 h-4"
                      />
                      <ChevronDownIcon
                        v-else
                        class="w-4 h-4"
                      />
                    </button>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-sm text-gray-700 font-mono">{{ log.timestamp }}</span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-sm font-medium text-gray-900">{{ log.user }}</span>
                  </td>
                  <td class="px-5 py-3">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize"
                      :class="getEventClass(log.event)"
                    >
                      {{ log.event }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-700">{{ log.model }}</span>
                      <span class="text-xs text-gray-400">#{{ log.model_id }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-sm text-gray-500 font-mono">{{ log.ip_address }}</span>
                  </td>
                </tr>
                <!-- Expanded Row -->
                <tr v-if="isRowExpanded(log.id)">
                  <td colspan="6" class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <!-- Old Values -->
                      <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                          Old Values
                        </h4>
                        <pre
                          v-if="log.old_values"
                          class="text-xs text-gray-700 bg-white border border-gray-200 rounded-lg p-3 overflow-x-auto font-mono leading-relaxed"
                        >{{ formatJson(log.old_values) }}</pre>
                        <p v-else class="text-xs text-gray-400 italic">No previous values</p>
                      </div>
                      <!-- New Values -->
                      <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                          New Values
                        </h4>
                        <pre
                          v-if="log.new_values"
                          class="text-xs text-gray-700 bg-white border border-gray-200 rounded-lg p-3 overflow-x-auto font-mono leading-relaxed"
                        >{{ formatJson(log.new_values) }}</pre>
                        <p v-else class="text-xs text-gray-400 italic">No new values</p>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredLogs.length === 0" class="px-5 py-12 text-center">
          <ClipboardDocumentListIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <p class="text-sm text-gray-500">No audit logs found matching your criteria</p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between px-5 py-3 border-t border-gray-200 bg-gray-50">
          <p class="text-sm text-gray-500">
            Page {{ currentPage }} of {{ totalPages }}
          </p>
          <div class="flex items-center gap-1">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <ArrowLeftIcon class="w-4 h-4" />
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              @click="goToPage(p)"
              class="w-8 h-8 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
              :class="
                p === currentPage
                  ? 'bg-primary-700 text-white'
                  : 'text-gray-700 hover:bg-gray-200'
              "
            >
              {{ p }}
            </button>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <ArrowRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
