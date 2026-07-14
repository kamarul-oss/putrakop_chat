<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import {
  ArrowsRightLeftIcon,
  CheckCircleIcon,
  PlayIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  AcademicCapIcon,
  BoltIcon,
  UserGroupIcon,
  TrophyIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
})

// Mock departments
const departments = ref([
  { id: 1, name: 'Account Services' },
  { id: 2, name: 'Billing' },
  { id: 3, name: 'Loans' },
  { id: 4, name: 'Technical Support' },
])

const selectedDepartment = ref('Account Services')

const strategies = [
  {
    key: 'round_robin',
    name: 'Round Robin',
    description: 'Distributes conversations equally across all available agents in a rotating order.',
    icon: ArrowsRightLeftIcon,
  },
  {
    key: 'least_loaded',
    name: 'Least Loaded',
    description: 'Assigns conversations to the agent with the fewest active chats.',
    icon: UserGroupIcon,
  },
  {
    key: 'skill_based',
    name: 'Skill-Based',
    description: 'Routes conversations to agents with matching skills and expertise.',
    icon: AcademicCapIcon,
  },
  {
    key: 'priority_based',
    name: 'Priority-Based',
    description: 'Routes conversations based on customer priority and VIP status.',
    icon: TrophyIcon,
  },
]

const routingConfigs = ref({
  'Account Services': {
    strategy: 'round_robin',
    config: {
      max_queue_wait: 5,
      fallback_to_general: true,
      vip_priority: true,
      skill_tags: ['accounts', 'general'],
      priority_weight: 1,
    },
  },
  'Billing': {
    strategy: 'least_loaded',
    config: {
      max_queue_wait: 5,
      fallback_to_general: true,
      vip_priority: true,
      skill_tags: ['billing', 'payments'],
      priority_weight: 2,
    },
  },
  'Loans': {
    strategy: 'skill_based',
    config: {
      max_queue_wait: 10,
      fallback_to_general: false,
      vip_priority: true,
      skill_tags: ['loans', 'applications'],
      priority_weight: 3,
      required_skills: ['loans'],
    },
  },
  'Technical Support': {
    strategy: 'round_robin',
    config: {
      max_queue_wait: 5,
      fallback_to_general: true,
      vip_priority: false,
      skill_tags: ['technical', 'troubleshooting'],
      priority_weight: 1,
    },
  },
})

const currentConfig = computed(() => routingConfigs.value[selectedDepartment.value])

const editForm = reactive({
  strategy: '',
  max_queue_wait: 5,
  fallback_to_general: true,
  vip_priority: true,
  skill_tags: [],
  priority_weight: 1,
  required_skills: [],
})

const newSkillTag = ref('')
const isSaving = ref(false)
const saveSuccess = ref(false)
const isTesting = ref(false)
const testResult = ref(null)

// Initialize form when department changes
const initForm = () => {
  const cfg = currentConfig.value
  if (cfg) {
    editForm.strategy = cfg.strategy
    editForm.max_queue_wait = cfg.config.max_queue_wait
    editForm.fallback_to_general = cfg.config.fallback_to_general
    editForm.vip_priority = cfg.config.vip_priority
    editForm.skill_tags = [...(cfg.config.skill_tags || [])]
    editForm.priority_weight = cfg.config.priority_weight
    editForm.required_skills = [...(cfg.config.required_skills || [])]
  }
  testResult.value = null
}

initForm()

const getStrategyInfo = (key) => strategies.find((s) => s.key === key)

const setStrategy = (key) => {
  editForm.strategy = key
}

const addSkillTag = () => {
  const tag = newSkillTag.value.trim().toLowerCase()
  if (tag && !editForm.skill_tags.includes(tag)) {
    editForm.skill_tags.push(tag)
    newSkillTag.value = ''
  }
}

const removeSkillTag = (tag) => {
  editForm.skill_tags = editForm.skill_tags.filter((t) => t !== tag)
}

const addRequiredSkill = () => {
  const skill = newSkillTag.value.trim().toLowerCase()
  if (skill && !editForm.required_skills.includes(skill)) {
    editForm.required_skills.push(skill)
    newSkillTag.value = ''
  }
}

const removeRequiredSkill = (skill) => {
  editForm.required_skills = editForm.required_skills.filter((s) => s !== skill)
}

const handleSave = async () => {
  isSaving.value = true
  saveSuccess.value = false

  try {
    await new Promise((resolve) => setTimeout(resolve, 1000))

    routingConfigs.value[selectedDepartment.value] = {
      strategy: editForm.strategy,
      config: {
        max_queue_wait: editForm.max_queue_wait,
        fallback_to_general: editForm.fallback_to_general,
        vip_priority: editForm.vip_priority,
        skill_tags: [...editForm.skill_tags],
        priority_weight: editForm.priority_weight,
        required_skills: [...editForm.required_skills],
      },
    }

    saveSuccess.value = true
    setTimeout(() => {
      saveSuccess.value = false
    }, 3000)
  } catch {
    // Handle error
  } finally {
    isSaving.value = false
  }
}

const testRouting = async () => {
  isTesting.value = true
  testResult.value = null

  try {
    await new Promise((resolve) => setTimeout(resolve, 1500))

    // Mock test result
    const strategy = getStrategyInfo(editForm.strategy)
    testResult.value = {
      success: true,
      strategy: strategy.name,
      matched_agent: 'Siti Aminah',
      wait_time: '< 1 minute',
      queue_position: 0,
      message: `Test routing successful. Customer would be routed to ${strategy.name} queue for ${selectedDepartment.value}.`,
    }
  } catch {
    testResult.value = {
      success: false,
      message: 'Test failed. Please check the configuration.',
    }
  } finally {
    isTesting.value = false
  }
}
</script>

<template>
  <AdminLayout :user="user" title="Routing Configuration">
    <div class="px-6 py-6 max-w-[1200px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Routing Configuration</h1>
          <p class="mt-1 text-sm text-gray-600">Configure how conversations are routed to agents</p>
        </div>
      </div>

      <!-- Save Success -->
      <div
        v-if="saveSuccess"
        class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg"
      >
        <CheckCircleIcon class="w-5 h-5 text-green-600 flex-shrink-0" />
        <p class="text-sm text-green-700">Routing configuration saved successfully.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left: Department Selector & Current Config -->
        <div class="lg:col-span-1 space-y-4">
          <!-- Department Selector -->
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Department</h2>
            <div class="space-y-1">
              <button
                v-for="dept in departments"
                :key="dept.id"
                @click="selectedDepartment = dept.name; initForm()"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500"
                :class="
                  selectedDepartment === dept.name
                    ? 'bg-primary-50 text-primary-700 border border-primary-200'
                    : 'text-gray-600 hover:bg-gray-50 border border-transparent'
                "
              >
                <Cog6ToothIcon class="w-4 h-4 flex-shrink-0" />
                {{ dept.name }}
              </button>
            </div>
          </div>

          <!-- Current Strategy Display -->
          <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Current Strategy</h2>
            <div v-if="currentConfig" class="space-y-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
                  <component :is="getStrategyInfo(currentConfig.strategy).icon" class="w-5 h-5 text-primary-700" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">
                    {{ getStrategyInfo(currentConfig.strategy).name }}
                  </p>
                  <p class="text-xs text-gray-500">Active strategy</p>
                </div>
              </div>
              <div class="pt-2 border-t border-gray-100 space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Queue Wait</span>
                  <span class="font-medium text-gray-900">{{ currentConfig.config.max_queue_wait }} min</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">VIP Priority</span>
                  <span class="font-medium" :class="currentConfig.config.vip_priority ? 'text-green-700' : 'text-gray-400'">
                    {{ currentConfig.config.vip_priority ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Fallback</span>
                  <span class="font-medium" :class="currentConfig.config.fallback_to_general ? 'text-green-700' : 'text-gray-400'">
                    {{ currentConfig.config.fallback_to_general ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Weight</span>
                  <span class="font-medium text-gray-900">{{ currentConfig.config.priority_weight }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Configuration Form -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Strategy Selection -->
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Routing Strategy</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                v-for="strategy in strategies"
                :key="strategy.key"
                @click="setStrategy(strategy.key)"
                class="flex items-start gap-3 p-4 rounded-lg border-2 transition-all text-left focus:outline-none focus:ring-2 focus:ring-primary-500"
                :class="
                  editForm.strategy === strategy.key
                    ? 'border-primary-500 bg-primary-50'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                "
              >
                <div
                  class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                  :class="
                    editForm.strategy === strategy.key
                      ? 'bg-primary-100'
                      : 'bg-gray-100'
                  "
                >
                  <component
                    :is="strategy.icon"
                    class="w-5 h-5"
                    :class="editForm.strategy === strategy.key ? 'text-primary-700' : 'text-gray-500'"
                  />
                </div>
                <div class="min-w-0">
                  <p
                    class="text-sm font-semibold"
                    :class="editForm.strategy === strategy.key ? 'text-primary-700' : 'text-gray-900'"
                  >
                    {{ strategy.name }}
                  </p>
                  <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ strategy.description }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Configuration Options -->
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Configuration Options</h2>
            <div class="space-y-5">
              <!-- Max Queue Wait -->
              <div class="flex items-center justify-between">
                <div>
                  <label for="max-queue-wait" class="text-sm font-medium text-gray-800">Max Queue Wait Time</label>
                  <p class="text-xs text-gray-500">Maximum minutes a customer waits before fallback</p>
                </div>
                <div class="flex items-center gap-2">
                  <input
                    id="max-queue-wait"
                    v-model.number="editForm.max_queue_wait"
                    type="number"
                    min="1"
                    max="30"
                    class="w-20 px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  />
                  <span class="text-sm text-gray-500">min</span>
                </div>
              </div>

              <!-- Priority Weight -->
              <div class="flex items-center justify-between">
                <div>
                  <label for="priority-weight" class="text-sm font-medium text-gray-800">Priority Weight</label>
                  <p class="text-xs text-gray-500">Higher weight = higher priority in queue</p>
                </div>
                <input
                  id="priority-weight"
                  v-model.number="editForm.priority_weight"
                  type="number"
                  min="1"
                  max="10"
                  class="w-20 px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              </div>

              <!-- VIP Priority -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-800">VIP Customer Priority</label>
                  <p class="text-xs text-gray-500">Route VIP customers ahead of regular queue</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="editForm.vip_priority"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-700" />
                </label>
              </div>

              <!-- Fallback -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-800">Fallback to General Queue</label>
                  <p class="text-xs text-gray-500">Route to general queue when department has no available agents</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="editForm.fallback_to_general"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-700" />
                </label>
              </div>

              <!-- Skill Tags -->
              <div>
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Skill Tags</label>
                <div class="flex gap-2 mb-2">
                  <input
                    v-model="newSkillTag"
                    type="text"
                    placeholder="Add skill tag and press Enter"
                    class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    @keydown.enter.prevent="addSkillTag"
                  />
                  <button
                    @click="addSkillTag"
                    class="px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    Add
                  </button>
                </div>
                <div v-if="editForm.skill_tags.length > 0" class="flex flex-wrap gap-1.5">
                  <span
                    v-for="tag in editForm.skill_tags"
                    :key="tag"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-200"
                  >
                    {{ tag }}
                    <button
                      @click="removeSkillTag(tag)"
                      class="p-0.5 rounded-full hover:bg-primary-200 transition-colors focus:outline-none"
                      :aria-label="`Remove skill tag ${tag}`"
                    >
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </span>
                </div>
                <p v-else class="text-xs text-gray-400">No skill tags configured</p>
              </div>

              <!-- Required Skills (Skill-Based only) -->
              <div v-if="editForm.strategy === 'skill_based'">
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Required Skills</label>
                <p class="text-xs text-gray-500 mb-2">Agents must have ALL these skills to receive routed conversations</p>
                <div class="flex gap-2 mb-2">
                  <input
                    v-model="newSkillTag"
                    type="text"
                    placeholder="Add required skill and press Enter"
                    class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    @keydown.enter.prevent="addRequiredSkill"
                  />
                  <button
                    @click="addRequiredSkill"
                    class="px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    Add
                  </button>
                </div>
                <div v-if="editForm.required_skills.length > 0" class="flex flex-wrap gap-1.5">
                  <span
                    v-for="skill in editForm.required_skills"
                    :key="skill"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"
                  >
                    {{ skill }}
                    <button
                      @click="removeRequiredSkill(skill)"
                      class="p-0.5 rounded-full hover:bg-amber-200 transition-colors focus:outline-none"
                      :aria-label="`Remove required skill ${skill}`"
                    >
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </span>
                </div>
                <p v-else class="text-xs text-gray-400">No required skills configured</p>
              </div>
            </div>
          </div>

          <!-- Test Routing & Save -->
          <div class="flex items-center justify-between">
            <button
              @click="testRouting"
              :disabled="isTesting"
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              <PlayIcon class="w-4 h-4" />
              <span v-if="isTesting">Testing...</span>
              <span v-else>Test Routing</span>
            </button>
            <button
              @click="handleSave"
              :disabled="isSaving"
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            >
              <CheckCircleIcon v-if="!isSaving" class="w-4 h-4" />
              <span v-if="isSaving">Saving...</span>
              <span v-else>Save Configuration</span>
            </button>
          </div>

          <!-- Test Result -->
          <div
            v-if="testResult"
            class="bg-white rounded-lg border p-5"
            :class="testResult.success ? 'border-green-200' : 'border-red-200'"
          >
            <div class="flex items-start gap-3">
              <CheckCircleIcon
                v-if="testResult.success"
                class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5"
              />
              <ExclamationTriangleIcon
                v-else
                class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
              />
              <div>
                <h3
                  class="text-sm font-semibold"
                  :class="testResult.success ? 'text-green-800' : 'text-red-800'"
                >
                  {{ testResult.success ? 'Test Successful' : 'Test Failed' }}
                </h3>
                <p
                  class="text-sm mt-1"
                  :class="testResult.success ? 'text-green-700' : 'text-red-700'"
                >
                  {{ testResult.message }}
                </p>
                <div v-if="testResult.success" class="mt-3 grid grid-cols-3 gap-4 text-sm">
                  <div>
                    <p class="text-gray-500">Strategy</p>
                    <p class="font-medium text-gray-900">{{ testResult.strategy }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Matched Agent</p>
                    <p class="font-medium text-gray-900">{{ testResult.matched_agent }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Est. Wait</p>
                    <p class="font-medium text-gray-900">{{ testResult.wait_time }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
