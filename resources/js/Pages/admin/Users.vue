<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import StatusBadge from '@/Pages/components/StatusBadge.vue'
import {
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
  UsersIcon,
  EnvelopeIcon,
  PhoneIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  users: { type: Array, default: () => [] },
})

// Mock data for demo
const users = ref([
  {
    id: 1,
    name: 'Ahmad Khan',
    email: 'ahmad@putrakop.com',
    phone: '+60 12-345-6789',
    role: 'agent',
    department: 'Account Services',
    status: 'active',
    created_at: '2024-01-15',
  },
  {
    id: 2,
    name: 'Siti Aminah',
    email: 'siti@putrakop.com',
    phone: '+60 19-876-5432',
    role: 'agent',
    department: 'Billing',
    status: 'active',
    created_at: '2024-02-10',
  },
  {
    id: 3,
    name: 'Mohd Rashid',
    email: 'rashid@putrakop.com',
    phone: '+60 11-234-5678',
    role: 'agent',
    department: 'Loans',
    status: 'active',
    created_at: '2024-03-05',
  },
  {
    id: 4,
    name: 'Fatimah Hassan',
    email: 'fatimah@putrakop.com',
    phone: '+60 17-654-3210',
    role: 'agent',
    department: 'Technical Support',
    status: 'active',
    created_at: '2024-03-20',
  },
  {
    id: 5,
    name: 'Nurul Izzah',
    email: 'nurul@putrakop.com',
    phone: '+60 13-987-6543',
    role: 'agent',
    department: 'Account Services',
    status: 'inactive',
    created_at: '2024-04-01',
  },
  {
    id: 6,
    name: 'Kamal Abdullah',
    email: 'kamal@putrakop.com',
    phone: '+60 16-123-4567',
    role: 'manager',
    department: 'Account Services',
    status: 'active',
    created_at: '2024-01-01',
  },
  {
    id: 7,
    name: 'Admin User',
    email: 'admin@putrakop.com',
    phone: '+60 10-000-0000',
    role: 'admin',
    department: null,
    status: 'active',
    created_at: '2024-01-01',
  },
])

const departments = ref([
  { id: 1, name: 'Account Services' },
  { id: 2, name: 'Billing' },
  { id: 3, name: 'Loans' },
  { id: 4, name: 'Technical Support' },
])

const searchQuery = ref('')
const roleFilter = ref('all')
const departmentFilter = ref('all')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingUser = ref(null)
const isLoading = ref(false)
const showPassword = ref(false)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'agent',
  department: '',
  password: '',
  password_confirmation: '',
})

const filteredUsers = computed(() => {
  let filtered = users.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (u) =>
        u.name.toLowerCase().includes(query) ||
        u.email.toLowerCase().includes(query)
    )
  }

  if (roleFilter.value !== 'all') {
    filtered = filtered.filter((u) => u.role === roleFilter.value)
  }

  if (departmentFilter.value !== 'all') {
    filtered = filtered.filter((u) => u.department === departmentFilter.value)
  }

  return filtered
})

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.role = 'agent'
  form.department = ''
  form.password = ''
  form.password_confirmation = ''
  showPassword.value = false
}

const openCreateModal = () => {
  resetForm()
  showCreateModal.value = true
}

const openEditModal = (user) => {
  editingUser.value = user
  form.name = user.name
  form.email = user.email
  form.phone = user.phone || ''
  form.role = user.role
  form.department = user.department || ''
  form.password = ''
  form.password_confirmation = ''
  showEditModal.value = true
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingUser.value = null
  resetForm()
}

const handleCreate = async () => {
  if (!form.name || !form.email || !form.password) return
  if (form.password !== form.password_confirmation) return

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    users.value.push({
      id: users.value.length + 1,
      name: form.name,
      email: form.email,
      phone: form.phone,
      role: form.role,
      department: form.department || null,
      status: 'active',
      created_at: new Date().toISOString().split('T')[0],
    })

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const handleUpdate = async () => {
  if (!editingUser.value) return
  if (!form.name || !form.email) return

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    const index = users.value.findIndex((u) => u.id === editingUser.value.id)
    if (index !== -1) {
      users.value[index] = {
        ...users.value[index],
        name: form.name,
        email: form.email,
        phone: form.phone,
        role: form.role,
        department: form.department || null,
      }
    }

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const toggleStatus = async (user) => {
  user.status = user.status === 'active' ? 'inactive' : 'active'
}

const deleteUser = async (user) => {
  if (!confirm(`Are you sure you want to delete "${user.name}"?`)) return

  users.value = users.value.filter((u) => u.id !== user.id)
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'admin':
      return 'bg-purple-100 text-purple-700'
    case 'manager':
      return 'bg-blue-100 text-blue-700'
    case 'agent':
      return 'bg-green-100 text-green-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}
</script>

<template>
  <AdminLayout :user="user" title="Users">
    <div class="px-6 py-6 max-w-[1400px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Users</h1>
          <p class="mt-1 text-sm text-gray-600">Manage your team members and their access</p>
        </div>
        <button
          @click="openCreateModal"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="w-4 h-4" />
          Add User
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative flex-1 min-w-[250px]">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name or email..."
              class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <select
            v-model="roleFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Roles</option>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="agent">Agent</option>
          </select>
          <select
            v-model="departmentFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.name">
              {{ dept.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  User
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Role
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Department
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Created
                </th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="userItem in filteredUsers"
                :key="userItem.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <span class="text-sm font-medium text-primary-700">
                        {{ userItem.name.split(' ').map(n => n[0]).join('') }}
                      </span>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-900">{{ userItem.name }}</p>
                      <p class="text-xs text-gray-500 flex items-center gap-1">
                        <EnvelopeIcon class="w-3 h-3" />
                        {{ userItem.email }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                    :class="getRoleBadgeClass(userItem.role)"
                  >
                    {{ userItem.role }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-700">
                    {{ userItem.department || '—' }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <button
                    @click="toggleStatus(userItem)"
                    class="focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                  >
                    <StatusBadge :status="userItem.status" size="md" />
                  </button>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-500">{{ userItem.created_at }}</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openEditModal(userItem)"
                      class="p-1.5 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                      title="Edit"
                    >
                      <PencilSquareIcon class="w-4 h-4" />
                    </button>
                    <button
                      v-if="userItem.id !== user.id"
                      @click="deleteUser(userItem)"
                      class="p-1.5 text-gray-400 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                      title="Delete"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredUsers.length === 0" class="px-5 py-12 text-center">
          <UsersIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <p class="text-sm text-gray-500">No users found matching your criteria</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showCreateModal || showEditModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="closeModals"
      >
        <div
          class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="showCreateModal ? 'create-user-title' : 'edit-user-title'"
        >
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2
              :id="showCreateModal ? 'create-user-title' : 'edit-user-title'"
              class="text-lg font-semibold text-gray-900"
            >
              {{ showCreateModal ? 'Add New User' : 'Edit User' }}
            </h2>
            <button
              @click="closeModals"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-4 overflow-y-auto max-h-[calc(90vh-140px)] space-y-4">
            <!-- Name -->
            <div>
              <label for="user-name" class="block text-sm font-medium text-gray-800 mb-1.5">
                Full Name
              </label>
              <input
                id="user-name"
                v-model="form.name"
                type="text"
                required
                placeholder="e.g. Ahmad bin Ali"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>

            <!-- Email -->
            <div>
              <label for="user-email" class="block text-sm font-medium text-gray-800 mb-1.5">
                Email Address
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <EnvelopeIcon class="h-4 w-4 text-gray-400" />
                </div>
                <input
                  id="user-email"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="user@putrakop.com"
                  class="w-full pl-9 pr-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
            </div>

            <!-- Phone -->
            <div>
              <label for="user-phone" class="block text-sm font-medium text-gray-800 mb-1.5">
                Phone Number <span class="text-gray-400">(optional)</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <PhoneIcon class="h-4 w-4 text-gray-400" />
                </div>
                <input
                  id="user-phone"
                  v-model="form.phone"
                  type="tel"
                  placeholder="+60 12-345-6789"
                  class="w-full pl-9 pr-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
            </div>

            <!-- Role & Department -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="user-role" class="block text-sm font-medium text-gray-800 mb-1.5">
                  Role
                </label>
                <select
                  id="user-role"
                  v-model="form.role"
                  class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="agent">Agent</option>
                  <option value="manager">Manager</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div>
                <label for="user-department" class="block text-sm font-medium text-gray-800 mb-1.5">
                  Department
                </label>
                <select
                  id="user-department"
                  v-model="form.department"
                  class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">None</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.name">
                    {{ dept.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Password (Create only) -->
            <div v-if="showCreateModal">
              <label for="user-password" class="block text-sm font-medium text-gray-800 mb-1.5">
                Password
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </div>
                <input
                  id="user-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Min. 8 characters"
                  class="w-full pl-9 pr-10 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                  <svg v-if="showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Confirm Password (Create only) -->
            <div v-if="showCreateModal">
              <label for="user-confirm-password" class="block text-sm font-medium text-gray-800 mb-1.5">
                Confirm Password
              </label>
              <input
                id="user-confirm-password"
                v-model="form.password_confirmation"
                type="password"
                required
                placeholder="Re-enter password"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
              <p
                v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation"
                class="mt-1 text-xs text-red-600"
              >
                Passwords do not match
              </p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button
              @click="closeModals"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              Cancel
            </button>
            <button
              @click="showCreateModal ? handleCreate() : handleUpdate()"
              :disabled="!form.name || !form.email || (showCreateModal && (!form.password || form.password !== form.password_confirmation)) || isLoading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              <span v-if="isLoading">Saving...</span>
              <span v-else>{{ showCreateModal ? 'Add User' : 'Save Changes' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
