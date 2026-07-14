<script setup>
import { reactive, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import {
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  LockClosedIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  language_preference: 'en',
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const errors = ref({})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const validateForm = () => {
  const newErrors = {}

  if (!form.name.trim()) {
    newErrors.name = 'Name is required'
  } else if (form.name.trim().length < 2) {
    newErrors.name = 'Name must be at least 2 characters'
  }

  if (!form.email.trim()) {
    newErrors.email = 'Email is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    newErrors.email = 'Please enter a valid email address'
  }

  if (form.phone && !/^[\d\s+\-()]{9,15}$/.test(form.phone)) {
    newErrors.phone = 'Please enter a valid phone number'
  }

  if (!form.password) {
    newErrors.password = 'Password is required'
  } else if (form.password.length < 8) {
    newErrors.password = 'Password must be at least 8 characters'
  } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(form.password)) {
    newErrors.password = 'Password must include uppercase, lowercase, and a number'
  }

  if (!form.password_confirmation) {
    newErrors.password_confirmation = 'Please confirm your password'
  } else if (form.password !== form.password_confirmation) {
    newErrors.password_confirmation = 'Passwords do not match'
  }

  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = async () => {
  if (isSubmitting.value) return

  errorMessage.value = ''

  if (!validateForm()) return

  isSubmitting.value = true

  router.post('/api/v1/auth/register', form, {
    preserveState: true,
    onSuccess: () => {
      router.visit('/chat')
    },
    onError: (serverErrors) => {
      errors.value = serverErrors
      if (serverErrors.message) {
        errorMessage.value = serverErrors.message
      } else {
        errorMessage.value = 'Registration failed. Please check your details and try again.'
      }
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}
</script>

<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Left Branding Panel (Desktop) -->
    <div
      class="hidden lg:flex lg:w-[40%] bg-primary-900 flex-col items-center justify-center p-12 relative overflow-hidden"
    >
      <!-- Decorative pattern -->
      <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
            </pattern>
          </defs>
          <rect width="400" height="400" fill="url(#grid)" />
        </svg>
      </div>

      <div class="relative z-10 text-center">
        <!-- Logo -->
        <div class="flex items-center justify-center mb-8">
          <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-lg">
            <svg
              class="w-12 h-12 text-primary-900"
              viewBox="0 0 48 48"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" fill="currentColor" opacity="0.15" />
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" stroke="currentColor" stroke-width="2.5" fill="none" />
              <text
                x="24"
                y="30"
                text-anchor="middle"
                fill="currentColor"
                font-size="14"
                font-weight="700"
                font-family="Inter, sans-serif"
              >
                PK
              </text>
            </svg>
          </div>
        </div>

        <h1 class="text-3xl font-bold text-white tracking-tight mb-3">
          PutraKop Live Chat
        </h1>
        <p class="text-lg text-white/70 max-w-xs mx-auto leading-relaxed">
          Join PutraKop today and get the support you need.
        </p>
      </div>
    </div>

    <!-- Right Form Panel -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 lg:px-12">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-8">
          <div class="w-16 h-16 bg-primary-900 rounded-xl flex items-center justify-center mx-auto mb-4">
            <svg
              class="w-10 h-10 text-white"
              viewBox="0 0 48 48"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" fill="currentColor" opacity="0.15" />
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" stroke="currentColor" stroke-width="2.5" fill="none" />
              <text
                x="24"
                y="30"
                text-anchor="middle"
                fill="currentColor"
                font-size="14"
                font-weight="700"
                font-family="Inter, sans-serif"
              >
                PK
              </text>
            </svg>
          </div>
          <h1 class="text-xl font-semibold text-gray-900">PutraKop Live Chat</h1>
        </div>

        <!-- Form Header -->
        <div class="mb-8">
          <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">
            Create your account
          </h2>
          <p class="mt-2 text-sm text-gray-600">
            Fill in the details below to get started
          </p>
        </div>

        <!-- Error Message -->
        <div
          v-if="errorMessage"
          class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg"
          role="alert"
        >
          <p class="text-sm text-red-700">{{ errorMessage }}</p>
        </div>

        <!-- Registration Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>
          <!-- Name Field -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-800 mb-1.5">
              Full name
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <UserIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                autocomplete="name"
                placeholder="e.g. Ahmad bin Ali"
                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.name }"
              />
            </div>
            <p v-if="errors.name" class="mt-1.5 text-xs text-red-600">{{ errors.name }}</p>
          </div>

          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-800 mb-1.5">
              Email address
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <EnvelopeIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.email }"
              />
            </div>
            <p v-if="errors.email" class="mt-1.5 text-xs text-red-600">{{ errors.email }}</p>
          </div>

          <!-- Phone Field -->
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-800 mb-1.5">
              Phone number <span class="text-gray-400">(optional)</span>
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <PhoneIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                autocomplete="tel"
                placeholder="+60 12-345-6789"
                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.phone }"
              />
            </div>
            <p v-if="errors.phone" class="mt-1.5 text-xs text-red-600">{{ errors.phone }}</p>
          </div>

          <!-- Language Preference -->
          <div>
            <label for="language" class="block text-sm font-medium text-gray-800 mb-1.5">
              Language preference
            </label>
            <select
              id="language"
              v-model="form.language_preference"
              class="block w-full px-3 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
            >
              <option value="en">English</option>
              <option value="bm">Bahasa Melayu</option>
            </select>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-800 mb-1.5">
              Password
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                class="block w-full pl-10 pr-10 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password }"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                @click="togglePassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
              >
                <EyeSlashIcon v-if="showPassword" class="h-5 w-5" aria-hidden="true" />
                <EyeIcon v-else class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
            <p v-if="errors.password" class="mt-1.5 text-xs text-red-600">{{ errors.password }}</p>
            <div class="mt-2 flex gap-1.5">
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="form.password.length >= 8 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                8+ chars
              </span>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="/[A-Z]/.test(form.password) ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                Uppercase
              </span>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="/[a-z]/.test(form.password) ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                Lowercase
              </span>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="/\d/.test(form.password) ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                Number
              </span>
            </div>
          </div>

          <!-- Confirm Password Field -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-800 mb-1.5">
              Confirm password
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="Re-enter your password"
                class="block w-full pl-10 pr-10 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{
                  'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password_confirmation,
                  'border-green-300 focus:ring-green-500 focus:border-green-500': form.password_confirmation && form.password === form.password_confirmation,
                }"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                @click="toggleConfirmPassword"
                :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
              >
                <EyeSlashIcon v-if="showConfirmPassword" class="h-5 w-5" aria-hidden="true" />
                <EyeIcon v-else class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
            <p v-if="errors.password_confirmation" class="mt-1.5 text-xs text-red-600">
              {{ errors.password_confirmation }}
            </p>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors mt-6"
          >
            <svg
              v-if="isSubmitting"
              class="animate-spin h-4 w-4"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            <span v-if="isSubmitting">Creating account...</span>
            <span v-else class="flex items-center gap-2">
              Create account
              <ArrowRightIcon class="h-4 w-4" />
            </span>
          </button>
        </form>

        <!-- Login Link -->
        <p class="mt-6 text-center text-sm text-gray-600">
          Already have an account?
          <a
            href="/login"
            class="font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded"
          >
            Sign in here
          </a>
        </p>
      </div>
    </div>
  </div>
</template>
