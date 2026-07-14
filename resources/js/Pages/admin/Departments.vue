<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import StatusBadge from '@/Pages/components/StatusBadge.vue'
import {
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  CheckCircleIcon,
  XCircleIcon,
  XMarkIcon,
  BuildingOffice2Icon,
  UsersIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
})

// Mock data for demo
const departments = ref([
  {
    id: 1,
    name: 'Account Services',
    description: 'General account inquiries and support',
    status: 'active',
    agent_count: 4,
    is_online: true,
    business_hours: {
      enabled: true,
      timezone: 'Asia/Kuala_Lumpur',
      schedule: {
        monday: { start: '09:00', end: '18:00' },
        tuesday: { start: '09:00', end: '18:00' },
        wednesday: { start: '09:00', end: '18:00' },
        thursday: { start: '09:00', end: '18:00' },
        friday: { start: '09:00', end: '18:00' },
        saturday: { start: '09:00', end: '13:00' },
        sunday: null,
      },
    },
  },
  {
    id: 2,
    name: 'Billing',
    description: 'Billing inquiries, invoices, and payments',
    status: 'active',
    agent_count: 3,
    is_online: true,
    business_hours: {
      enabled: true,
      timezone: 'Asia/Kuala_Lumpur',
      schedule: {
        monday: { start: '09:00', end: '18:00' },
        tuesday: { start: '09:00', end: '18:00' },
        wednesday: { start: '09:00', end: '18:00' },
        thursday: { start: '09:00', end: '18:00' },
        friday: { start: '09:00', end: '18:00' },
        saturday: null,
        sunday: null,
      },
    },
  },
  {
    id: 3,
    name: 'Loans',
    description: 'Loan applications and inquiries',
    status: 'active',
    agent_count: 2,
    is_online: true,
    business_hours: {
      enabled: true,
      timezone: 'Asia/Kuala_Lumpur',
      schedule: {
        monday: { start: '09:00', end: '17:00' },
        tuesday: { start: '09:00', end: '17:00' },
        wednesday: { start: '09:00', end: '17:00' },
        thursday: { start: '09:00', end: '17:00' },
        friday: { start: '09:00', end: '17:00' },
        saturday: null,
        sunday: null,
      },
    },
  },
  {
    id: 4,
    name: 'Technical Support',
    description: 'Technical issues and troubleshooting',
    status: 'active',
    agent_count: 2,
    is_online: false,
    business_hours: {
      enabled: false,
      timezone: 'Asia/Kuala_Lumpur',
      schedule: {},
    },
  },
  {
    id: 5,
    name: 'VIP Services',
    description: 'Priority support for VIP members',
    status: 'inactive',
    agent_count: 0,
    is_online: false,
    business_hours: {
      enabled: true,
      timezone: 'Asia/Kuala_Lumpur',
      schedule: {
        monday: { start: '08:00', end: '20:00' },
        tuesday: { start: '08:00', end: '20:00' },
        wednesday: { start: '08:00', end: '20:00' },
        thursday: { start: '08:00', end: '20:00' },
        friday: { start: '08:00', end: '20:00' },
        saturday: { start: '09:00', end: '17:00' },
        sunday: { start: '09:00', end: '17:00' },
      },
    },
  },
])

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingDepartment = ref(null)
const isLoading = ref(false)

const form = reactive({
  name: '',
  description: '',
  status: 'active',
  business_hours: {
    enabled: true,
    timezone: 'Asia/Kuala_Lumpur',
    schedule: {
      monday: { start: '09:00', end: '18:00' },
      tuesday: { start: '09:00', end: '18:00' },
      wednesday: { start: '09:00', end: '18:00' },
      thursday: { start: '09:00', end: '18:00' },
      friday: { start: '09:00', end: '18:00' },
      saturday: null,
      sunday: null,
    },
  },
})

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.status = 'active'
  form.business_hours.enabled = true
  form.business_hours.schedule = {
    monday: { start: '09:00', end: '18:00' },
    tuesday: { start: '09:00', end: '18:00' },
    wednesday: { start: '09:00', end: '18:00' },
    thursday: { start: '09:00', end: '18:00' },
    friday: { start: '09:00', end: '18:00' },
    saturday: null,
    sunday: null,
  }
}

const openCreateModal = () => {
  resetForm()
  showCreateModal.value = true
}

const openEditModal = (department) => {
  editingDepartment.value = department
  form.name = department.name
  form.description = department.description
  form.status = department.status
  form.business_hours = JSON.parse(JSON.stringify(department.business_hours))
  showEditModal.value = true
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingDepartment.value = null
  resetForm()
}

const toggleDay = (day) => {
  if (form.business_hours.schedule[day]) {
    form.business_hours.schedule[day] = null
  } else {
    form.business_hours.schedule[day] = { start: '09:00', end: '18:00' }
  }
}

const handleCreate = async () => {
  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    departments.value.push({
      id: departments.value.length + 1,
      name: form.name,
      description: form.description,
      status: form.status,
      agent_count: 0,
      is_online: form.status === 'active',
      business_hours: JSON.parse(JSON.stringify(form.business_hours)),
    })

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const handleUpdate = async () => {
  if (!editingDepartment.value) return

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    const index = departments.value.findIndex((d) => d.id === editingDepartment.value.id)
    if (index !== -1) {
      departments.value[index] = {
        ...departments.value[index],
        name: form.name,
        description: form.description,
        status: form.status,
        business_hours: JSON.parse(JSON.stringify(form.business_hours)),
      }
    }

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const toggleStatus = async (department) => {
  department.status = department.status === 'active' ? 'inactive' : 'active'
  department.is_online = department.status === 'active'
}

const deleteDepartment = async (department) => {
  if (!confirm(`Are you sure you want to delete "${department.name}"?`)) return

  departments.value = departments.value.filter((d) => d.id !== department.id)
}

const formatDayName = (day) => {
  return day.charAt(0).toUpperCase() + day.slice(1)
}
</script>

<template>
  <AdminLayout :user="user" title="Departments">
    <div class="px-6 py-6 max-w-[1400px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Departments</h1>
          <p class="mt-1 text-sm text-gray-600">Manage your support departments and business hours</p>
        </div>
        <button
          @click="openCreateModal"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="w-4 h-4" />
          Add Department
        </button>
      </div>

      <!-- Department List -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Department
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Agents
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Business Hours
                </th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="department in departments"
                :key="department.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                      <BuildingOffice2Icon class="w-5 h-5 text-primary-700" />
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-900">{{ department.name }}</p>
                      <p class="text-xs text-gray-500 max-w-[300px] truncate">{{ department.description }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <button
                    @click="toggleStatus(department)"
                    class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                  >
                    <StatusBadge :status="department.status" size="md" />
                  </button>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2 text-sm text-gray-700">
                    <UsersIcon class="w-4 h-4 text-gray-400" />
                    {{ department.agent_count }}
                  </div>
                </td>
                <td class="px-5 py-4">
                  <div v-if="department.business_hours.enabled" class="flex items-center gap-2 text-sm text-gray-700">
                    <ClockIcon class="w-4 h-4 text-gray-400" />
                    <span>9:00 AM - 6:00 PM</span>
                  </div>
                  <span v-else class="text-sm text-gray-400 italic">24/7</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openEditModal(department)"
                      class="p-1.5 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                      title="Edit"
                    >
                      <PencilSquareIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteDepartment(department)"
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
          :aria-labelledby="showCreateModal ? 'create-dept-title' : 'edit-dept-title'"
        >
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2
              :id="showCreateModal ? 'create-dept-title' : 'edit-dept-title'"
              class="text-lg font-semibold text-gray-900"
            >
              {{ showCreateModal ? 'Create Department' : 'Edit Department' }}
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
              <label for="dept-name" class="block text-sm font-medium text-gray-800 mb-1.5">
                Name
              </label>
              <input
                id="dept-name"
                v-model="form.name"
                type="text"
                required
                placeholder="e.g. Account Services"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>

            <!-- Description -->
            <div>
              <label for="dept-description" class="block text-sm font-medium text-gray-800 mb-1.5">
                Description
              </label>
              <textarea
                id="dept-description"
                v-model="form.description"
                rows="2"
                placeholder="Brief description of this department"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
              />
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1.5">Status</label>
              <div class="flex gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    v-model="form.status"
                    value="active"
                    class="w-4 h-4 text-primary-700 border-gray-300 focus:ring-primary-500"
                  />
                  <span class="text-sm text-gray-700">Active</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    v-model="form.status"
                    value="inactive"
                    class="w-4 h-4 text-primary-700 border-gray-300 focus:ring-primary-500"
                  />
                  <span class="text-sm text-gray-700">Inactive</span>
                </label>
              </div>
            </div>

            <!-- Business Hours -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-medium text-gray-800">Business Hours</label>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="form.business_hours.enabled"
                    class="sr-only peer"
                  />
                  <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-700" />
                </label>
              </div>

              <div v-if="form.business_hours.enabled" class="space-y-2 bg-gray-50 rounded-lg p-3">
                <div
                  v-for="day in days"
                  :key="day"
                  class="flex items-center gap-3"
                >
                  <label class="flex items-center gap-2 w-28">
                    <input
                      type="checkbox"
                      :checked="form.business_hours.schedule[day] !== null"
                      @change="toggleDay(day)"
                      class="w-4 h-4 text-primary-700 border-gray-300 rounded focus:ring-primary-500"
                    />
                    <span class="text-sm text-gray-700">{{ formatDayName(day) }}</span>
                  </label>
                  <div v-if="form.business_hours.schedule[day]" class="flex items-center gap-2">
                    <input
                      v-model="form.business_hours.schedule[day].start"
                      type="time"
                      class="px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                    <span class="text-sm text-gray-400">to</span>
                    <input
                      v-model="form.business_hours.schedule[day].end"
                      type="time"
                      class="px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                  <span v-else class="text-sm text-gray-400 italic">Closed</span>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500 italic">24/7 availability</p>
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
              :disabled="!form.name.trim() || isLoading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              <span v-if="isLoading">Saving...</span>
              <span v-else>{{ showCreateModal ? 'Create Department' : 'Save Changes' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
