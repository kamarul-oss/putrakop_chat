<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  CpuChipIcon,
  CircleStackIcon,
  ServerIcon,
  ClockIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
  TrashIcon,
  FireIcon,
  DocumentArrowDownIcon,
  BoltIcon,
  SignalIcon,
  BugAntIcon,
  UsersIcon,
  ChartBarIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  BoltSlashIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  PlayIcon,
  PauseIcon,
  EyeIcon,
  WifiIcon,
  LockClosedIcon,
  KeyIcon
} from '@heroicons/vue/24/outline'
import {
  CheckCircleIcon as CheckCircleSolid,
  ExclamationTriangleIcon as ExclamationTriangleSolid,
  XCircleIcon as XCircleSolid
} from '@heroicons/vue/24/solid'
import AdminLayout from './Layout.vue'

const props = defineProps({
  systemHealth: {
    type: Object,
    default: () => ({
      status: 'healthy',
      lastUpdated: new Date().toISOString(),
      services: {
        database: { status: 'up', latency: 12 },
        cache: { status: 'up', latency: 3 },
        queue: { status: 'up', pendingJobs: 0 },
        storage: { status: 'up', usagePercent: 45 },
        memory: { status: 'up', usagePercent: 62 }
      },
      performance: {
        responseTime: [],
        requestsPerMinute: 0,
        errorRate: 0,
        activeConnections: 0
      },
      security: {
        lastAuditDate: '2024-01-15',
        vulnerabilitiesFound: 0,
        failedLoginAttempts: 0,
        activeSessions: 0
      }
    })
  }
})

// State
const isLoading = ref(false)
const isRefreshing = ref(false)
const autoRefresh = ref(true)
const refreshInterval = ref(null)
const refreshIntervalMs = 30000
const showPerformanceChart = ref(true)

// Service status data
const serviceStatus = ref({
  database: { status: 'up', latency: 12, name: 'Database' },
  cache: { status: 'up', latency: 3, name: 'Cache' },
  queue: { status: 'up', pendingJobs: 0, name: 'Queue' },
  storage: { status: 'up', usagePercent: 45, name: 'Storage' },
  memory: { status: 'up', usagePercent: 62, name: 'Memory' }
})

// Performance metrics
const performanceMetrics = ref({
  responseTime: [45, 52, 48, 55, 42, 58, 51, 47, 53, 49, 46, 54, 48, 50, 47],
  requestsPerMinute: 128,
  errorRate: 0.5,
  activeConnections: 24
})

// Security data
const securityData = ref({
  lastAuditDate: '2024-01-15',
  vulnerabilitiesFound: 0,
  failedLoginAttempts: 3,
  activeSessions: 12
})

// Overall system status
const overallStatus = computed(() => {
  const statuses = Object.values(serviceStatus.value).map(s => s.status)
  if (statuses.every(s => s === 'up')) return 'healthy'
  if (statuses.some(s => s === 'down')) return 'critical'
  return 'warning'
})

const statusConfig = computed(() => ({
  healthy: { color: 'text-emerald-500', bg: 'bg-emerald-50', border: 'border-emerald-200', label: 'All Systems Operational' },
  warning: { color: 'text-amber-500', bg: 'bg-amber-50', border: 'border-amber-200', label: 'Degraded Performance' },
  critical: { color: 'text-red-500', bg: 'bg-red-50', border: 'border-red-200', label: 'System Outage' }
}))

const formattedLastUpdated = computed(() => {
  const date = new Date()
  return date.toLocaleString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  })
})

// Chart helpers
const chartWidth = 400
const chartHeight = 120
const maxResponseTime = computed(() => Math.max(...performanceMetrics.value.responseTime, 100))

const chartPath = computed(() => {
  const data = performanceMetrics.value.responseTime
  const stepX = chartWidth / (data.length - 1)
  const points = data.map((value, index) => {
    const x = index * stepX
    const y = chartHeight - (value / maxResponseTime.value) * chartHeight
    return `${x},${y}`
  })
  return `M${points.join(' L')}`
})

const chartAreaPath = computed(() => {
  const data = performanceMetrics.value.responseTime
  const stepX = chartWidth / (data.length - 1)
  const points = data.map((value, index) => {
    const x = index * stepX
    const y = chartHeight - (value / maxResponseTime.value) * chartHeight
    return `${x},${y}`
  })
  return `M0,${chartHeight} L${points.join(' L')} L${chartWidth},${chartHeight} Z`
})

// Methods
const fetchSystemData = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  
  try {
    const response = await fetch('/api/admin/system-monitor')
    if (response.ok) {
      const data = await response.json()
      serviceStatus.value = data.services || serviceStatus.value
      performanceMetrics.value = data.performance || performanceMetrics.value
      securityData.value = data.security || securityData.value
    }
  } catch (error) {
    console.error('Failed to fetch system data:', error)
  } finally {
    isRefreshing.value = false
  }
}

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value
  if (autoRefresh.value) {
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
}

const startAutoRefresh = () => {
  if (refreshInterval.value) return
  refreshInterval.value = setInterval(fetchSystemData, refreshIntervalMs)
}

const stopAutoRefresh = () => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
}

const clearCache = async () => {
  isLoading.value = true
  try {
    await router.post('/admin/system-monitor/clear-cache')
    await fetchSystemData()
  } catch (error) {
    console.error('Failed to clear cache:', error)
  } finally {
    isLoading.value = false
  }
}

const warmCache = async () => {
  isLoading.value = true
  try {
    await router.post('/admin/system-monitor/warm-cache')
    await fetchSystemData()
  } catch (error) {
    console.error('Failed to warm cache:', error)
  } finally {
    isLoading.value = false
  }
}

const runSecurityAudit = async () => {
  isLoading.value = true
  try {
    await router.post('/admin/system-monitor/security-audit')
    await fetchSystemData()
  } catch (error) {
    console.error('Failed to run security audit:', error)
  } finally {
    isLoading.value = false
  }
}

const exportReport = async () => {
  isLoading.value = true
  try {
    window.location.href = '/admin/system-monitor/export'
  } catch (error) {
    console.error('Failed to export report:', error)
  } finally {
    isLoading.value = false
  }
}

const getServiceStatusIcon = (status) => {
  return status === 'up' ? CheckCircleIcon : status === 'warning' ? ExclamationTriangleIcon : XCircleIcon
}

const getServiceStatusColor = (status) => {
  return status === 'up' ? 'text-emerald-500' : status === 'warning' ? 'text-amber-500' : 'text-red-500'
}

const getStatusBgColor = (status) => {
  return status === 'up' ? 'bg-emerald-50' : status === 'warning' ? 'bg-amber-50' : 'bg-red-50'
}

const getUsageColor = (percent) => {
  if (percent < 50) return 'text-emerald-500'
  if (percent < 75) return 'text-amber-500'
  return 'text-red-500'
}

const getUsageBarColor = (percent) => {
  if (percent < 50) return 'bg-emerald-500'
  if (percent < 75) return 'bg-amber-500'
  return 'bg-red-500'
}

onMounted(() => {
  if (autoRefresh.value) {
    startAutoRefresh()
  }
})

onUnmounted(() => {
  stopAutoRefresh()
})
</script>

<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">System Monitor</h1>
              <p class="mt-1 text-sm text-gray-500">Real-time system health and performance monitoring</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-4">
              <!-- Auto-refresh toggle -->
              <div class="flex items-center gap-2">
                <button
                  @click="toggleAutoRefresh"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                    autoRefresh ? 'bg-indigo-600' : 'bg-gray-200'
                  ]"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      autoRefresh ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
                <span class="text-sm text-gray-600">Auto-refresh</span>
              </div>

              <!-- Last updated -->
              <div class="flex items-center gap-2 text-sm text-gray-500">
                <ClockIcon class="h-4 w-4" />
                <span>Updated {{ formattedLastUpdated }}</span>
                <button
                  @click="fetchSystemData"
                  :disabled="isRefreshing"
                  class="p-1 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <ArrowPathIcon
                    :class="['h-4 w-4', isRefreshing ? 'animate-spin' : '']"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- System Status Banner -->
          <div
            :class="[
              'mt-4 p-4 rounded-lg border flex items-center gap-3',
              statusConfig[overallStatus].bg,
              statusConfig[overallStatus].border
            ]"
          >
            <div :class="['h-3 w-3 rounded-full', overallStatus === 'healthy' ? 'bg-emerald-500' : overallStatus === 'warning' ? 'bg-amber-500' : 'bg-red-500']" />
            <span :class="['font-medium', statusConfig[overallStatus].color]">
              {{ statusConfig[overallStatus].label }}
            </span>
          </div>
        </div>

        <!-- Service Status Cards -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Service Status</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Database Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <CircleStackIcon class="h-5 w-5 text-indigo-600" />
                  <span class="font-medium text-gray-900">Database</span>
                </div>
                <component
                  :is="getServiceStatusIcon(serviceStatus.database.status)"
                  :class="['h-5 w-5', getServiceStatusColor(serviceStatus.database.status)]"
                />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Latency</span>
                  <span :class="['font-medium', serviceStatus.database.latency < 20 ? 'text-emerald-600' : 'text-amber-600']">
                    {{ serviceStatus.database.latency }}ms
                  </span>
                </div>
                <div :class="['px-2 py-1 rounded-full text-xs font-medium w-fit', getStatusBgColor(serviceStatus.database.status)]">
                  {{ serviceStatus.database.status === 'up' ? 'Operational' : 'Degraded' }}
                </div>
              </div>
            </div>

            <!-- Cache Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <BoltIcon class="h-5 w-5 text-amber-600" />
                  <span class="font-medium text-gray-900">Cache</span>
                </div>
                <component
                  :is="getServiceStatusIcon(serviceStatus.cache.status)"
                  :class="['h-5 w-5', getServiceStatusColor(serviceStatus.cache.status)]"
                />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Latency</span>
                  <span :class="['font-medium', serviceStatus.cache.latency < 5 ? 'text-emerald-600' : 'text-amber-600']">
                    {{ serviceStatus.cache.latency }}ms
                  </span>
                </div>
                <div :class="['px-2 py-1 rounded-full text-xs font-medium w-fit', getStatusBgColor(serviceStatus.cache.status)]">
                  {{ serviceStatus.cache.status === 'up' ? 'Operational' : 'Degraded' }}
                </div>
              </div>
            </div>

            <!-- Queue Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <ServerIcon class="h-5 w-5 text-purple-600" />
                  <span class="font-medium text-gray-900">Queue</span>
                </div>
                <component
                  :is="getServiceStatusIcon(serviceStatus.queue.status)"
                  :class="['h-5 w-5', getServiceStatusColor(serviceStatus.queue.status)]"
                />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Pending</span>
                  <span :class="['font-medium', serviceStatus.queue.pendingJobs === 0 ? 'text-emerald-600' : 'text-amber-600']">
                    {{ serviceStatus.queue.pendingJobs }} jobs
                  </span>
                </div>
                <div :class="['px-2 py-1 rounded-full text-xs font-medium w-fit', getStatusBgColor(serviceStatus.queue.status)]">
                  {{ serviceStatus.queue.pendingJobs === 0 ? 'Empty' : 'Processing' }}
                </div>
              </div>
            </div>

            <!-- Storage Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <CpuChipIcon class="h-5 w-5 text-cyan-600" />
                  <span class="font-medium text-gray-900">Storage</span>
                </div>
                <component
                  :is="getServiceStatusIcon(serviceStatus.storage.status)"
                  :class="['h-5 w-5', getServiceStatusColor(serviceStatus.storage.status)]"
                />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Usage</span>
                  <span :class="['font-medium', getUsageColor(serviceStatus.storage.usagePercent)]">
                    {{ serviceStatus.storage.usagePercent }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    :class="['h-2 rounded-full transition-all duration-500', getUsageBarColor(serviceStatus.storage.usagePercent)]"
                    :style="{ width: `${serviceStatus.storage.usagePercent}%` }"
                  />
                </div>
              </div>
            </div>

            <!-- Memory Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <ChartBarIcon class="h-5 w-5 text-rose-600" />
                  <span class="font-medium text-gray-900">Memory</span>
                </div>
                <component
                  :is="getServiceStatusIcon(serviceStatus.memory.status)"
                  :class="['h-5 w-5', getServiceStatusColor(serviceStatus.memory.status)]"
                />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Usage</span>
                  <span :class="['font-medium', getUsageColor(serviceStatus.memory.usagePercent)]">
                    {{ serviceStatus.memory.usagePercent }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    :class="['h-2 rounded-full transition-all duration-500', getUsageBarColor(serviceStatus.memory.usagePercent)]"
                    :style="{ width: `${serviceStatus.memory.usagePercent}%` }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Metrics -->
        <div class="mb-8">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Performance Metrics</h2>
            <button
              @click="showPerformanceChart = !showPerformanceChart"
              class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
            >
              <EyeIcon class="h-4 w-4" />
              {{ showPerformanceChart ? 'Hide Chart' : 'Show Chart' }}
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Response Time Chart -->
            <div
              v-if="showPerformanceChart"
              class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6"
            >
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-medium text-gray-900">Response Time (Last 15 minutes)</h3>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                  <span class="inline-block w-3 h-3 rounded-full bg-indigo-500" />
                  <span>Avg: {{ Math.round(performanceMetrics.responseTime.reduce((a, b) => a + b, 0) / performanceMetrics.responseTime.length) }}ms</span>
                </div>
              </div>
              <div class="relative">
                <svg
                  :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                  class="w-full h-32"
                  preserveAspectRatio="none"
                >
                  <!-- Grid lines -->
                  <line
                    v-for="i in 4"
                    :key="i"
                    :x1="0"
                    :y1="(chartHeight / 4) * i"
                    :x2="chartWidth"
                    :y2="(chartHeight / 4) * i"
                    stroke="#e5e7eb"
                    stroke-width="1"
                  />
                  
                  <!-- Area fill -->
                  <path
                    :d="chartAreaPath"
                    fill="url(#gradient)"
                    opacity="0.3"
                  />
                  
                  <!-- Line -->
                  <path
                    :d="chartPath"
                    fill="none"
                    stroke="#6366f1"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  
                  <!-- Gradient definition -->
                  <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stop-color="#6366f1" stop-opacity="0.4" />
                      <stop offset="100%" stop-color="#6366f1" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>
            </div>

            <!-- Metric Cards -->
            <div class="space-y-4">
              <!-- Requests per Minute -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 rounded-lg">
                      <ArrowTrendingUpIcon class="h-5 w-5 text-indigo-600" />
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Requests/min</p>
                      <p class="text-2xl font-bold text-gray-900">{{ performanceMetrics.requestsPerMinute }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-xs text-emerald-600 flex items-center gap-1">
                      <ArrowTrendingUpIcon class="h-3 w-3" />
                      +12%
                    </span>
                  </div>
                </div>
              </div>

              <!-- Error Rate -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div :class="['p-2 rounded-lg', performanceMetrics.errorRate < 1 ? 'bg-emerald-50' : 'bg-red-50']">
                      <BugAntIcon :class="['h-5 w-5', performanceMetrics.errorRate < 1 ? 'text-emerald-600' : 'text-red-600']" />
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Error Rate</p>
                      <p :class="['text-2xl font-bold', performanceMetrics.errorRate < 1 ? 'text-gray-900' : 'text-red-600']">
                        {{ performanceMetrics.errorRate }}%
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span :class="['text-xs flex items-center gap-1', performanceMetrics.errorRate < 1 ? 'text-emerald-600' : 'text-red-600']">
                      <ArrowTrendingDownIcon class="h-3 w-3" />
                      -0.2%
                    </span>
                  </div>
                </div>
              </div>

              <!-- Active Connections -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-cyan-50 rounded-lg">
                      <WifiIcon class="h-5 w-5 text-cyan-600" />
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Active Connections</p>
                      <p class="text-2xl font-bold text-gray-900">{{ performanceMetrics.activeConnections }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-xs text-gray-500">
                      Live
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Status -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Security Status</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Last Audit -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-indigo-50 rounded-lg">
                  <ShieldCheckIcon class="h-5 w-5 text-indigo-600" />
                </div>
                <span class="font-medium text-gray-900">Last Audit</span>
              </div>
              <p class="text-sm text-gray-600">{{ securityData.lastAuditDate }}</p>
              <p class="text-xs text-gray-400 mt-1">15 days ago</p>
            </div>

            <!-- Vulnerabilities -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="flex items-center gap-3 mb-3">
                <div :class="['p-2 rounded-lg', securityData.vulnerabilitiesFound === 0 ? 'bg-emerald-50' : 'bg-red-50']">
                  <ExclamationCircleIcon :class="['h-5 w-5', securityData.vulnerabilitiesFound === 0 ? 'text-emerald-600' : 'text-red-600']" />
                </div>
                <span class="font-medium text-gray-900">Vulnerabilities</span>
              </div>
              <p :class="['text-2xl font-bold', securityData.vulnerabilitiesFound === 0 ? 'text-emerald-600' : 'text-red-600']">
                {{ securityData.vulnerabilitiesFound }}
              </p>
              <p class="text-xs text-gray-400 mt-1">{{ securityData.vulnerabilitiesFound === 0 ? 'No issues found' : 'Requires attention' }}</p>
            </div>

            <!-- Failed Logins -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="flex items-center gap-3 mb-3">
                <div :class="['p-2 rounded-lg', securityData.failedLoginAttempts < 5 ? 'bg-amber-50' : 'bg-red-50']">
                  <KeyIcon :class="['h-5 w-5', securityData.failedLoginAttempts < 5 ? 'text-amber-600' : 'text-red-600']" />
                </div>
                <span class="font-medium text-gray-900">Failed Logins</span>
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ securityData.failedLoginAttempts }}</p>
              <p class="text-xs text-gray-400 mt-1">Last 24 hours</p>
            </div>

            <!-- Active Sessions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-purple-50 rounded-lg">
                  <UsersIcon class="h-5 w-5 text-purple-600" />
                </div>
                <span class="font-medium text-gray-900">Active Sessions</span>
              </div>
              <p class="text-2xl font-bold text-gray-900">{{ securityData.activeSessions }}</p>
              <p class="text-xs text-gray-400 mt-1">Currently logged in</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Clear Cache -->
              <button
                @click="clearCache"
                :disabled="isLoading"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-gray-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <TrashIcon class="h-5 w-5" />
                <span>Clear Cache</span>
              </button>

              <!-- Warm Cache -->
              <button
                @click="warmCache"
                :disabled="isLoading"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <FireIcon class="h-5 w-5" />
                <span>Warm Cache</span>
              </button>

              <!-- Run Security Audit -->
              <button
                @click="runSecurityAudit"
                :disabled="isLoading"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ShieldCheckIcon class="h-5 w-5" />
                <span>Security Audit</span>
              </button>

              <!-- Export Report -->
              <button
                @click="exportReport"
                :disabled="isLoading"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <DocumentArrowDownIcon class="h-5 w-5" />
                <span>Export Report</span>
              </button>
            </div>

            <!-- Loading indicator -->
            <div v-if="isLoading" class="mt-4 flex items-center justify-center gap-2 text-sm text-gray-500">
              <ArrowPathIcon class="h-4 w-4 animate-spin" />
              <span>Processing...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
