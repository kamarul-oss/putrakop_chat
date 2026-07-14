<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  HomeIcon,
  BuildingOffice2Icon,
  UsersIcon,
  BookOpenIcon,
  Cog6ToothIcon,
  ClipboardDocumentListIcon,
  ArrowLeftOnRectangleIcon,
  ChevronDownIcon,
  Bars3Icon,
  XMarkIcon,
  ChartBarIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  title: { type: String, default: '' },
})

const isSidebarOpen = ref(true)
const isMobileMenuOpen = ref(false)
const isUserMenuOpen = ref(false)

const navigation = [
  { name: 'Dashboard', href: '/admin', icon: HomeIcon, current: page.url === '/admin' },
  { name: 'Departments', href: '/admin/departments', icon: BuildingOffice2Icon, current: page.url.startsWith('/admin/departments') },
  { name: 'Users', href: '/admin/users', icon: UsersIcon, current: page.url.startsWith('/admin/users') },
  { name: 'Knowledge Base', href: '/admin/knowledge-base', icon: BookOpenIcon, current: page.url.startsWith('/admin/knowledge-base') },
  { name: 'Analytics', href: '/admin/analytics', icon: ChartBarIcon, current: page.url.startsWith('/admin/analytics') },
  { name: 'Routing Config', href: '/admin/routing-config', icon: ArrowsRightLeftIcon, current: page.url.startsWith('/admin/routing-config') },
  { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon, current: page.url.startsWith('/admin/settings') },
  { name: 'Audit Log', href: '/admin/audit-log', icon: ClipboardDocumentListIcon, current: page.url.startsWith('/admin/audit-log') },
]

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const logout = () => {
  router.post('/api/v1/auth/logout', {}, {
    onSuccess: () => {
      router.visit('/login')
    },
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Mobile Menu Overlay -->
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="toggleMobileMenu"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 bg-white border-r border-gray-200 transition-all duration-200 flex flex-col"
      :class="{
        'w-64': isSidebarOpen,
        'w-16': !isSidebarOpen,
        'translate-x-0': isMobileMenuOpen,
        '-translate-x-full lg:translate-x-0': !isMobileMenuOpen,
      }"
    >
      <!-- Sidebar Header -->
      <div class="flex items-center justify-between h-14 px-4 border-b border-gray-200 flex-shrink-0">
        <div v-if="isSidebarOpen" class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary-900 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" fill="currentColor" opacity="0.15" />
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" stroke="currentColor" stroke-width="2.5" fill="none" />
              <text x="24" y="30" text-anchor="middle" fill="currentColor" font-size="14" font-weight="700" font-family="Inter, sans-serif">PK</text>
            </svg>
          </div>
          <span class="text-sm font-semibold text-gray-900">PutraKop Admin</span>
        </div>

        <button
          @click="toggleSidebar"
          class="hidden lg:flex p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
          :aria-label="isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <button
          @click="toggleMobileMenu"
          class="lg:hidden p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-1">
        <a
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
          :class="
            item.current
              ? 'bg-primary-50 text-primary-700 border-l-2 border-primary-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          "
          :title="!isSidebarOpen ? item.name : undefined"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-if="isSidebarOpen">{{ item.name }}</span>
        </a>
      </nav>

      <!-- User Section -->
      <div class="border-t border-gray-200 p-2 flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2">
          <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-xs font-medium text-primary-700">
              {{ user.name?.charAt(0) || 'A' }}
            </span>
          </div>
          <div v-if="isSidebarOpen" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
          </div>
          <button
            v-if="isSidebarOpen"
            @click="logout"
            class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
            title="Logout"
          >
            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div
      class="flex-1 flex flex-col min-h-screen transition-all duration-200"
      :class="{
        'lg:ml-64': isSidebarOpen,
        'lg:ml-16': !isSidebarOpen,
      }"
    >
      <!-- Top Bar -->
      <header class="sticky top-0 z-30 bg-white border-b border-gray-200 h-14 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
        <div class="flex items-center gap-3">
          <button
            @click="toggleMobileMenu"
            class="lg:hidden p-1.5 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <Bars3Icon class="w-5 h-5" />
          </button>
          <h1 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h1>
        </div>

        <div class="flex items-center gap-3">
          <!-- Language Toggle -->
          <button class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500">
            EN
          </button>

          <!-- Notifications -->
          <button class="relative p-2 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
          </button>

          <!-- User Menu -->
          <div class="relative">
            <button
              @click="isUserMenuOpen = !isUserMenuOpen"
              class="flex items-center gap-2 p-1 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-xs font-medium text-primary-700">
                  {{ user.name?.charAt(0) || 'A' }}
                </span>
              </div>
              <ChevronDownIcon class="w-4 h-4 text-gray-400 hidden sm:block" />
            </button>

            <!-- Dropdown -->
            <div
              v-if="isUserMenuOpen"
              class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50"
            >
              <div class="px-4 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                <p class="text-xs text-gray-500">{{ user.email }}</p>
              </div>
              <a href="/admin/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Settings
              </a>
              <button
                @click="logout"
                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
              >
                Sign out
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
