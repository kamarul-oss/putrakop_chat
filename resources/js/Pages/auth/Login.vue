<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import {
  EnvelopeIcon,
  LockClosedIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const form = reactive({
  email: '',
  password: '',
  fingerprint: null,
})

const showPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const language = ref('en')

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const collectFingerprint = async () => {
  try {
    if (typeof Fingerprint2 !== 'undefined') {
      return new Promise((resolve) => {
        Fingerprint2.get((components) => {
          const values = components.map((c) => c.value)
          const hash = values.join('')
          resolve(hash)
        })
      })
    }
  } catch {
    // Fallback: basic fingerprint from browser properties
  }

  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  ctx.textBaseline = 'top'
  ctx.font = '14px Arial'
  ctx.fillText('PutraKop', 2, 2)
  const canvasHash = canvas.toDataURL()

  const components = [
    navigator.userAgent,
    navigator.language,
    screen.width + 'x' + screen.height,
    screen.colorDepth,
    new Date().getTimezoneOffset(),
    canvasHash,
  ]

  return components.join('|||')
}

const handleSubmit = async () => {
  if (isSubmitting.value) return

  errorMessage.value = ''
  isSubmitting.value = true

  try {
    form.fingerprint = await collectFingerprint()

    router.post('/api/v1/auth/login', form, {
      preserveState: true,
      onSuccess: (page) => {
        const user = page.props.auth?.user
        if (user) {
          switch (user.role) {
            case 'admin':
              router.visit('/admin')
              break
            case 'manager':
              router.visit('/manager/dashboard')
              break
            case 'agent':
              router.visit('/agent/workspace')
              break
            default:
              router.visit('/chat')
          }
        }
      },
      onError: (errors) => {
        if (errors.message) {
          errorMessage.value = errors.message
        } else {
          errorMessage.value = 'Invalid email or password. Please try again.'
        }
      },
      onFinish: () => {
        isSubmitting.value = false
      },
    })
  } catch {
    errorMessage.value = 'An unexpected error occurred. Please try again.'
    isSubmitting.value = false
  }
}

const toggleLanguage = () => {
  language.value = language.value === 'en' ? 'bm' : 'en'
}

onMounted(() => {
  const flash = page.props.flash
  if (flash?.error) {
    errorMessage.value = flash.error
  }
})
</script>

<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Language Toggle -->
    <button
      type="button"
      class="absolute top-4 right-4 z-10 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
      @click="toggleLanguage"
      :aria-label="language === 'en' ? 'Switch to Bahasa Melayu' : 'Switch to English'"
    >
      {{ language === 'en' ? 'BM' : 'EN' }}
    </button>

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
          <div
            class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-lg"
          >
            <svg
              class="w-12 h-12 text-primary-900"
              viewBox="0 0 48 48"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M24 4L4 14v20l20 10 20-10V14L24 4z"
                fill="currentColor"
                opacity="0.15"
              />
              <path
                d="M24 4L4 14v20l20 10 20-10V14L24 4z"
                stroke="currentColor"
                stroke-width="2.5"
                fill="none"
              />
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
          {{ language === 'en'
            ? "We're here to help. Sign in to your support workspace."
            : 'Kami sedia membantu. Log masuk ke ruang kerja sokongan anda.'
          }}
        </p>
      </div>
    </div>

    <!-- Right Form Panel -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 lg:px-12">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-8">
          <div
            class="w-16 h-16 bg-primary-900 rounded-xl flex items-center justify-center mx-auto mb-4"
          >
            <svg
              class="w-10 h-10 text-white"
              viewBox="0 0 48 48"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M24 4L4 14v20l20 10 20-10V14L24 4z"
                fill="currentColor"
                opacity="0.15"
              />
              <path
                d="M24 4L4 14v20l20 10 20-10V14L24 4z"
                stroke="currentColor"
                stroke-width="2.5"
                fill="none"
              />
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
            {{ language === 'en' ? 'Sign in to your account' : 'Log masuk ke akaun anda' }}
          </h2>
          <p class="mt-2 text-sm text-gray-600">
            {{ language === 'en'
              ? 'Enter your credentials to access the workspace'
              : 'Masukkan kelayakan anda untuk mengakses ruang kerja'
            }}
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

        <!-- Login Form -->
        <form @submit.prevent="handleSubmit" class="space-y-5" novalidate>
          <!-- Email Field -->
          <div>
            <label
              for="email"
              class="block text-sm font-medium text-gray-800 mb-1.5"
            >
              {{ language === 'en' ? 'Email address' : 'Alamat e-mel' }}
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
                :placeholder="language === 'en' ? 'you@example.com' : 'anda@contoh.com'"
                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors?.email }"
              />
            </div>
            <p v-if="form.errors?.email" class="mt-1.5 text-xs text-red-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label
              for="password"
              class="block text-sm font-medium text-gray-800 mb-1.5"
            >
              {{ language === 'en' ? 'Password' : 'Kata laluan' }}
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
                autocomplete="current-password"
                :placeholder="language === 'en' ? 'Enter your password' : 'Masukkan kata laluan anda'"
                class="block w-full pl-10 pr-10 py-2.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors?.password }"
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
            <p v-if="form.errors?.password" class="mt-1.5 text-xs text-red-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                class="w-4 h-4 text-primary-700 border-gray-300 rounded focus:ring-primary-500 focus:ring-2 focus:ring-offset-0"
              />
              <span class="text-sm text-gray-600">
                {{ language === 'en' ? 'Remember me' : 'Ingat saya' }}
              </span>
            </label>
            <a
              href="/forgot-password"
              class="text-sm font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded"
            >
              {{ language === 'en' ? 'Forgot password?' : 'Lupa kata laluan?' }}
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <svg
              v-if="isSubmitting"
              class="animate-spin h-4 w-4"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            <span v-if="isSubmitting">
              {{ language === 'en' ? 'Signing in...' : 'Sedang log masuk...' }}
            </span>
            <span v-else class="flex items-center gap-2">
              {{ language === 'en' ? 'Sign in' : 'Log masuk' }}
              <ArrowRightIcon class="h-4 w-4" />
            </span>
          </button>
        </form>

        <!-- Register Link -->
        <p class="mt-6 text-center text-sm text-gray-600">
          {{ language === 'en' ? "Don't have an account?" : 'Belum mempunyai akaun?' }}
          <a
            href="/register"
            class="font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded"
          >
            {{ language === 'en' ? 'Register here' : 'Daftar di sini' }}
          </a>
        </p>
      </div>
    </div>
  </div>
</template>
