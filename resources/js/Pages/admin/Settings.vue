<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import {
  Cog6ToothIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  settings: { type: Object, default: () => ({}) },
})

// Mock settings data organized by group
const settingsGroups = ref([
  {
    key: 'general',
    name: 'General',
    description: 'Basic system configuration',
    settings: [
      { key: 'app_name', label: 'Application Name', type: 'string', value: 'PutraKop Live Chat', default: 'PutraKop Live Chat' },
      { key: 'app_url', label: 'Application URL', type: 'string', value: 'https://chat.putrakop.com', default: 'https://chat.putrakop.com' },
      { key: 'support_email', label: 'Support Email', type: 'string', value: 'support@putrakop.com', default: 'support@putrakop.com' },
      { key: 'max_upload_size', label: 'Max Upload Size (MB)', type: 'integer', value: 10, default: 10 },
      { key: 'timezone', label: 'Default Timezone', type: 'string', value: 'Asia/Kuala_Lumpur', default: 'Asia/Kuala_Lumpur' },
    ],
  },
  {
    key: 'ai',
    name: 'AI',
    description: 'AI and chatbot configuration',
    settings: [
      { key: 'ai_enabled', label: 'Enable AI Assistant', type: 'boolean', value: true, default: true },
      { key: 'ai_model', label: 'AI Model', type: 'string', value: 'gpt-4o-mini', default: 'gpt-4o-mini' },
      { key: 'ai_confidence_threshold', label: 'Confidence Threshold (%)', type: 'integer', value: 75, default: 75 },
      { key: 'ai_max_tokens', label: 'Max Tokens per Response', type: 'integer', value: 500, default: 500 },
      { key: 'ai_auto_resolve', label: 'Auto-resolve on High Confidence', type: 'boolean', value: false, default: false },
      { key: 'ai_fallback_message', label: 'Fallback Message', type: 'string', value: "I'm not sure about that. Let me connect you with an agent.", default: "I'm not sure about that. Let me connect you with an agent." },
    ],
  },
  {
    key: 'chat',
    name: 'Chat',
    description: 'Chat behavior and routing settings',
    settings: [
      { key: 'max_concurrent_chats', label: 'Max Concurrent Chats per Agent', type: 'integer', value: 5, default: 5 },
      { key: 'chat_timeout_minutes', label: 'Idle Timeout (minutes)', type: 'integer', value: 30, default: 30 },
      { key: 'queue_enabled', label: 'Enable Queue', type: 'boolean', value: true, default: true },
      { key: 'queue_max_size', label: 'Max Queue Size', type: 'integer', value: 50, default: 50 },
      { key: 'proactive_chat', label: 'Enable Proactive Chat', type: 'boolean', value: false, default: false },
      { key: 'chat_rating_enabled', label: 'Enable Chat Rating', type: 'boolean', value: true, default: true },
      { key: 'typing_indicator', label: 'Show Typing Indicator', type: 'boolean', value: true, default: true },
    ],
  },
  {
    key: 'security',
    name: 'Security',
    description: 'Security and authentication settings',
    settings: [
      { key: 'session_timeout', label: 'Session Timeout (minutes)', type: 'integer', value: 120, default: 120 },
      { key: 'max_login_attempts', label: 'Max Login Attempts', type: 'integer', value: 5, default: 5 },
      { key: 'password_min_length', label: 'Minimum Password Length', type: 'integer', value: 8, default: 8 },
      { key: 'two_factor_enabled', label: 'Require Two-Factor Auth', type: 'boolean', value: false, default: false },
      { key: 'ip_whitelist_enabled', label: 'Enable IP Whitelist', type: 'boolean', value: false, default: false },
      { key: 'ip_whitelist', label: 'IP Whitelist (JSON)', type: 'json', value: '[]', default: '[]' },
    ],
  },
  {
    key: 'business_hours',
    name: 'Business Hours',
    description: 'Global business hours configuration',
    settings: [
      { key: 'business_hours_enabled', label: 'Enforce Business Hours', type: 'boolean', value: true, default: true },
      { key: 'business_hours_timezone', label: 'Timezone', type: 'string', value: 'Asia/Kuala_Lumpur', default: 'Asia/Kuala_Lumpur' },
      { key: 'outside_hours_message', label: 'Outside Hours Message', type: 'string', value: 'Our business hours are 9:00 AM - 6:00 PM (MYT). We will respond to your query on the next business day.', default: 'Our business hours are 9:00 AM - 6:00 PM (MYT). We will respond to your query on the next business day.' },
      { key: 'holiday_dates', label: 'Holiday Dates (JSON)', type: 'json', value: '["2024-12-25", "2025-01-01"]', default: '["2024-12-25", "2025-01-01"]' },
    ],
  },
])

const editedSettings = ref({})
const isSaving = ref(false)
const saveSuccess = ref(false)
const saveError = ref(false)

// Initialize edited settings
const initEditedSettings = () => {
  settingsGroups.value.forEach((group) => {
    group.settings.forEach((setting) => {
      editedSettings.value[setting.key] = JSON.parse(JSON.stringify(setting.value))
    })
  })
}
initEditedSettings()

const hasChanges = computed(() => {
  return settingsGroups.value.some((group) =>
    group.settings.some((setting) => {
      const edited = editedSettings.value[setting.key]
      return JSON.stringify(edited) !== JSON.stringify(setting.value)
    })
  )
})

const getSettingStatus = (setting) => {
  const edited = editedSettings.value[setting.key]
  if (JSON.stringify(edited) === JSON.stringify(setting.default)) return 'default'
  if (JSON.stringify(edited) !== JSON.stringify(setting.value)) return 'modified'
  return 'current'
}

const handleSave = async () => {
  isSaving.value = true
  saveSuccess.value = false
  saveError.value = false

  try {
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // Update settings with edited values
    settingsGroups.value.forEach((group) => {
      group.settings.forEach((setting) => {
        setting.value = JSON.parse(JSON.stringify(editedSettings.value[setting.key]))
      })
    })

    saveSuccess.value = true
    setTimeout(() => {
      saveSuccess.value = false
    }, 3000)
  } catch {
    saveError.value = true
    setTimeout(() => {
      saveError.value = false
    }, 3000)
  } finally {
    isSaving.value = false
  }
}

const resetToDefaults = () => {
  if (!confirm('Are you sure you want to reset all settings to their default values? This cannot be undone.')) return

  settingsGroups.value.forEach((group) => {
    group.settings.forEach((setting) => {
      editedSettings.value[setting.key] = JSON.parse(JSON.stringify(setting.default))
    })
  })
}

const updateSetting = (key, value) => {
  editedSettings.value[key] = value
}

const expandedGroups = ref(settingsGroups.value.map((g) => g.key))

const toggleGroup = (key) => {
  const idx = expandedGroups.value.indexOf(key)
  if (idx === -1) {
    expandedGroups.value.push(key)
  } else {
    expandedGroups.value.splice(idx, 1)
  }
}

const isGroupExpanded = (key) => expandedGroups.value.includes(key)
</script>

<template>
  <AdminLayout :user="user" title="Settings">
    <div class="px-6 py-6 max-w-[1000px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Settings</h1>
          <p class="mt-1 text-sm text-gray-600">Configure your live chat system preferences</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="resetToDefaults"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
          >
            Reset to Defaults
          </button>
          <button
            @click="handleSave"
            :disabled="!hasChanges || isSaving"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          >
            <CheckCircleIcon v-if="!isSaving" class="w-4 h-4" />
            <span v-if="isSaving">Saving...</span>
            <span v-else>Save All Changes</span>
          </button>
        </div>
      </div>

      <!-- Save Success Message -->
      <div
        v-if="saveSuccess"
        class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg"
      >
        <CheckCircleIcon class="w-5 h-5 text-green-600 flex-shrink-0" />
        <p class="text-sm text-green-700">Settings saved successfully.</p>
      </div>

      <!-- Save Error Message -->
      <div
        v-if="saveError"
        class="mb-4 flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-lg"
      >
        <ExclamationTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0" />
        <p class="text-sm text-red-700">Failed to save settings. Please try again.</p>
      </div>

      <!-- Unsaved Changes Banner -->
      <div
        v-if="hasChanges"
        class="mb-4 flex items-center gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg"
      >
        <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 flex-shrink-0" />
        <p class="text-sm text-amber-700">You have unsaved changes.</p>
      </div>

      <!-- Settings Groups -->
      <div class="space-y-4">
        <div
          v-for="group in settingsGroups"
          :key="group.key"
          class="bg-white rounded-lg border border-gray-200 overflow-hidden"
        >
          <!-- Group Header -->
          <button
            @click="toggleGroup(group.key)"
            class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
                <Cog6ToothIcon class="w-5 h-5 text-primary-700" />
              </div>
              <div class="text-left">
                <h2 class="text-sm font-semibold text-gray-900">{{ group.name }}</h2>
                <p class="text-xs text-gray-500">{{ group.description }}</p>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform duration-200"
              :class="{ 'rotate-180': isGroupExpanded(group.key) }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Group Settings -->
          <div v-if="isGroupExpanded(group.key)" class="border-t border-gray-200">
            <div class="divide-y divide-gray-100">
              <div
                v-for="setting in group.settings"
                :key="setting.key"
                class="px-5 py-4 flex items-start gap-4"
              >
                <!-- Label & Description -->
                <div class="flex-1 min-w-0 pt-0.5">
                  <label :for="`setting-${setting.key}`" class="text-sm font-medium text-gray-800">
                    {{ setting.label }}
                  </label>
                  <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ setting.key }}</p>
                </div>

                <!-- Input -->
                <div class="flex-shrink-0 w-[320px]">
                  <!-- String Input -->
                  <input
                    v-if="setting.type === 'string'"
                    :id="`setting-${setting.key}`"
                    :value="editedSettings[setting.key]"
                    @input="updateSetting(setting.key, $event.target.value)"
                    type="text"
                    class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  />

                  <!-- Integer Input -->
                  <input
                    v-else-if="setting.type === 'integer'"
                    :id="`setting-${setting.key}`"
                    :value="editedSettings[setting.key]"
                    @input="updateSetting(setting.key, parseInt($event.target.value) || 0)"
                    type="number"
                    class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  />

                  <!-- Boolean Toggle -->
                  <label
                    v-else-if="setting.type === 'boolean'"
                    :for="`setting-${setting.key}`"
                    class="relative inline-flex items-center cursor-pointer"
                  >
                    <input
                      :id="`setting-${setting.key}`"
                      type="checkbox"
                      :checked="editedSettings[setting.key]"
                      @change="updateSetting(setting.key, $event.target.checked)"
                      class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-700" />
                    <span class="ml-3 text-sm font-medium" :class="editedSettings[setting.key] ? 'text-primary-700' : 'text-gray-500'">
                      {{ editedSettings[setting.key] ? 'Enabled' : 'Disabled' }}
                    </span>
                  </label>

                  <!-- JSON Editor -->
                  <textarea
                    v-else-if="setting.type === 'json'"
                    :id="`setting-${setting.key}`"
                    :value="typeof editedSettings[setting.key] === 'string' ? editedSettings[setting.key] : JSON.stringify(editedSettings[setting.key])"
                    @input="updateSetting(setting.key, $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg font-mono focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                  />
                </div>

                <!-- Status Indicator -->
                <div class="flex-shrink-0 pt-2">
                  <span
                    v-if="getSettingStatus(setting) === 'default'"
                    class="text-xs text-gray-400"
                    title="Using default value"
                  >
                    Default
                  </span>
                  <span
                    v-else-if="getSettingStatus(setting) === 'modified'"
                    class="w-2 h-2 bg-amber-500 rounded-full inline-block"
                    title="Modified but not saved"
                  />
                  <span
                    v-else
                    class="w-2 h-2 bg-green-500 rounded-full inline-block"
                    title="Saved"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
