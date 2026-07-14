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
  BookOpenIcon,
  EyeIcon,
  TagIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ArrowUpIcon,
  ArrowDownIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
  user: { type: Object, required: true },
  articles: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

// Mock data
const articles = ref([
  {
    id: 1,
    title_en: 'How to Open a Savings Account',
    title_ms: 'Cara Membuka Akaun Simpanan',
    content_en: 'To open a savings account with PutraKop, visit your nearest branch with your NRIC and a minimum deposit of RM10. Our friendly staff will guide you through the process.',
    content_ms: 'Untuk membuka akaun simpanan dengan PutraKop, lawati cawangan terdekat anda dengan NRIC dan deposit minimum RM10. Kakitangan mesra kami akan membimbing anda melalui proses tersebut.',
    category: 'Accounts',
    department: 'Account Services',
    status: 'active',
    priority: 'high',
    trigger_keywords: ['savings', 'open account', 'simpanan', 'buka akaun', 'new account'],
    created_at: '2024-06-15',
    updated_at: '2024-12-01',
  },
  {
    id: 2,
    title_en: 'Loan Application Process',
    title_ms: 'Proses Permohonan Pinjaman',
    content_en: 'Apply for a personal loan online or at any PutraKop branch. You will need your NRIC, proof of income, and employment details. Processing takes 3-5 business days.',
    content_ms: 'Mohon pinjaman peribadi dalam talian atau di mana-mana cawangan PutraKop. Anda memerlukan NRIC, bukti pendapatan, dan butiran pekerjaan. Pemprosesan mengambil masa 3-5 hari bekerja.',
    category: 'Loans',
    department: 'Loans',
    status: 'active',
    priority: 'high',
    trigger_keywords: ['loan', 'apply', 'pinjaman', 'mohon', 'personal loan'],
    created_at: '2024-07-20',
    updated_at: '2024-11-28',
  },
  {
    id: 3,
    title_en: 'Online Banking Registration',
    title_ms: 'Pendaftaran Perbankan Dalam Talian',
    content_en: 'Register for PutraKop online banking by visiting our website and clicking "Register Now". You will need your account number and active phone number.',
    content_ms: 'Daftar untuk perbankan dalam talian PutraKop dengan melawat laman web kami dan mengklik "Daftar Sekarang". Anda memerlukan nombor akaun dan nombor telefon aktif.',
    category: 'Digital Banking',
    department: 'Technical Support',
    status: 'active',
    priority: 'medium',
    trigger_keywords: ['online banking', 'register', 'perbankan dalam talian', 'daftar', 'internet banking'],
    created_at: '2024-08-10',
    updated_at: '2024-12-05',
  },
  {
    id: 4,
    title_en: 'Understanding Your Statement',
    title_ms: 'Memahami Penyata Anda',
    content_en: 'Your monthly statement includes all transactions, fees, and account balance. Review it regularly to track your finances.',
    content_ms: 'Penyata bulanan anda termasuk semua transaksi, yuman, dan baki akaun. Semak ia secara berkala untuk menjejaki kewangan anda.',
    category: 'Accounts',
    department: 'Billing',
    status: 'active',
    priority: 'low',
    trigger_keywords: ['statement', 'penyata', 'monthly statement', 'transaksi'],
    created_at: '2024-09-01',
    updated_at: '2024-10-15',
  },
  {
    id: 5,
    title_en: 'Mobile App Troubleshooting',
    title_ms: 'Penyelesaian Masalah Aplikasi Mudah Alih',
    content_en: 'If you are experiencing issues with our mobile app, try clearing the cache, updating to the latest version, or reinstalling the app. Contact support if issues persist.',
    content_ms: 'Jika anda mengalami masalah dengan aplikasi mudah alihi kami, cuba kosongkan cache, kemas kini ke versi terkini, atau pasang semula aplikasi. Hubungi sokongan jika masalah berterusan.',
    category: 'Technical',
    department: 'Technical Support',
    status: 'active',
    priority: 'medium',
    trigger_keywords: ['mobile app', 'app not working', 'aplikasi', 'masalah', 'troubleshoot'],
    created_at: '2024-10-10',
    updated_at: '2024-12-10',
  },
  {
    id: 6,
    title_en: 'Fee Schedule 2024',
    title_ms: 'Jadual Yuran 2024',
    content_en: 'Review the complete fee schedule for all PutraKop services including account maintenance, ATM fees, and transfer charges.',
    content_ms: 'Semak jadual yuran lengkap untuk semua perkhidmatan PutraKop termasuk penyelenggaraan akaun, yuran ATM, dan caj pemindahan.',
    category: 'Billing',
    department: 'Billing',
    status: 'inactive',
    priority: 'low',
    trigger_keywords: ['fees', 'charges', 'yuran', 'caj', 'fee schedule'],
    created_at: '2024-03-01',
    updated_at: '2024-06-01',
  },
])

const departments = ref([
  { id: 1, name: 'Account Services' },
  { id: 2, name: 'Billing' },
  { id: 3, name: 'Loans' },
  { id: 4, name: 'Technical Support' },
])

const categories = ref(['Accounts', 'Loans', 'Digital Banking', 'Technical', 'Billing', 'General'])

const searchQuery = ref('')
const departmentFilter = ref('all')
const categoryFilter = ref('all')
const statusFilter = ref('all')

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showPreviewModal = ref(false)
const previewArticle = ref(null)
const editingArticle = ref(null)
const isLoading = ref(false)

const form = reactive({
  title_en: '',
  title_ms: '',
  content_en: '',
  content_ms: '',
  category: '',
  department: '',
  status: 'active',
  priority: 'medium',
  trigger_keywords: [],
})

const newKeyword = ref('')

const filteredArticles = computed(() => {
  let filtered = articles.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (a) =>
        a.title_en.toLowerCase().includes(query) ||
        a.title_ms.toLowerCase().includes(query) ||
        a.content_en.toLowerCase().includes(query) ||
        a.content_ms.toLowerCase().includes(query)
    )
  }

  if (departmentFilter.value !== 'all') {
    filtered = filtered.filter((a) => a.department === departmentFilter.value)
  }

  if (categoryFilter.value !== 'all') {
    filtered = filtered.filter((a) => a.category === categoryFilter.value)
  }

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter((a) => a.status === statusFilter.value)
  }

  return filtered
})

const resetForm = () => {
  form.title_en = ''
  form.title_ms = ''
  form.content_en = ''
  form.content_ms = ''
  form.category = ''
  form.department = ''
  form.status = 'active'
  form.priority = 'medium'
  form.trigger_keywords = []
  newKeyword.value = ''
}

const openCreateModal = () => {
  resetForm()
  showCreateModal.value = true
}

const openEditModal = (article) => {
  editingArticle.value = article
  form.title_en = article.title_en
  form.title_ms = article.title_ms
  form.content_en = article.content_en
  form.content_ms = article.content_ms
  form.category = article.category
  form.department = article.department
  form.status = article.status
  form.priority = article.priority
  form.trigger_keywords = [...article.trigger_keywords]
  showEditModal.value = true
}

const openPreviewModal = (article) => {
  previewArticle.value = article
  showPreviewModal.value = true
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  showPreviewModal.value = false
  editingArticle.value = null
  previewArticle.value = null
  resetForm()
}

const addKeyword = () => {
  const kw = newKeyword.value.trim().toLowerCase()
  if (kw && !form.trigger_keywords.includes(kw)) {
    form.trigger_keywords.push(kw)
    newKeyword.value = ''
  }
}

const removeKeyword = (keyword) => {
  form.trigger_keywords = form.trigger_keywords.filter((k) => k !== keyword)
}

const handleCreate = async () => {
  if (!form.title_en || !form.category || !form.department) return

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    articles.value.unshift({
      id: articles.value.length + 1,
      title_en: form.title_en,
      title_ms: form.title_ms,
      content_en: form.content_en,
      content_ms: form.content_ms,
      category: form.category,
      department: form.department,
      status: form.status,
      priority: form.priority,
      trigger_keywords: [...form.trigger_keywords],
      created_at: new Date().toISOString().split('T')[0],
      updated_at: new Date().toISOString().split('T')[0],
    })

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const handleUpdate = async () => {
  if (!editingArticle.value) return

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))

    const index = articles.value.findIndex((a) => a.id === editingArticle.value.id)
    if (index !== -1) {
      articles.value[index] = {
        ...articles.value[index],
        title_en: form.title_en,
        title_ms: form.title_ms,
        content_en: form.content_en,
        content_ms: form.content_ms,
        category: form.category,
        department: form.department,
        status: form.status,
        priority: form.priority,
        trigger_keywords: [...form.trigger_keywords],
        updated_at: new Date().toISOString().split('T')[0],
      }
    }

    closeModals()
  } catch {
    // Handle error
  } finally {
    isLoading.value = false
  }
}

const toggleStatus = (article) => {
  article.status = article.status === 'active' ? 'inactive' : 'active'
  article.updated_at = new Date().toISOString().split('T')[0]
}

const deleteArticle = (article) => {
  if (!confirm(`Are you sure you want to delete "${article.title_en}"?`)) return
  articles.value = articles.value.filter((a) => a.id !== article.id)
}

const getPriorityClass = (priority) => {
  switch (priority) {
    case 'high':
      return 'bg-red-100 text-red-700 border-red-200'
    case 'medium':
      return 'bg-amber-100 text-amber-700 border-amber-200'
    case 'low':
      return 'bg-gray-100 text-gray-600 border-gray-200'
    default:
      return 'bg-gray-100 text-gray-600 border-gray-200'
  }
}
</script>

<template>
  <AdminLayout :user="user" title="Knowledge Base">
    <div class="px-6 py-6 max-w-[1400px] mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Knowledge Base</h1>
          <p class="mt-1 text-sm text-gray-600">Manage help articles and trigger keywords for chatbot responses</p>
        </div>
        <button
          @click="openCreateModal"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="w-4 h-4" />
          Add Article
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
              placeholder="Search articles by title or content..."
              class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <select
            v-model="departmentFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.name">
              {{ dept.name }}
            </option>
          </select>
          <select
            v-model="categoryFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Categories</option>
            <option v-for="cat in categories" :key="cat" :value="cat">
              {{ cat }}
            </option>
          </select>
          <select
            v-model="statusFilter"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Articles Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Article
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Category
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Department
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Priority
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Keywords
                </th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="article in filteredArticles"
                :key="article.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                      <BookOpenIcon class="w-5 h-5 text-primary-700" />
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-gray-900 truncate max-w-[280px]">{{ article.title_en }}</p>
                      <p class="text-xs text-gray-500 truncate max-w-[280px]">{{ article.title_ms }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                    {{ article.category }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-700">{{ article.department }}</span>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize"
                    :class="getPriorityClass(article.priority)"
                  >
                    {{ article.priority }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <button
                    @click="toggleStatus(article)"
                    class="focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                  >
                    <StatusBadge :status="article.status" size="md" />
                  </button>
                </td>
                <td class="px-5 py-4">
                  <div class="flex flex-wrap gap-1 max-w-[200px]">
                    <span
                      v-for="kw in article.trigger_keywords.slice(0, 3)"
                      :key="kw"
                      class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600"
                    >
                      {{ kw }}
                    </span>
                    <span
                      v-if="article.trigger_keywords.length > 3"
                      class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500"
                    >
                      +{{ article.trigger_keywords.length - 3 }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openPreviewModal(article)"
                      class="p-1.5 text-gray-400 hover:text-blue-700 hover:bg-blue-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                      title="Preview"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="openEditModal(article)"
                      class="p-1.5 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                      title="Edit"
                    >
                      <PencilSquareIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteArticle(article)"
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
        <div v-if="filteredArticles.length === 0" class="px-5 py-12 text-center">
          <BookOpenIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <p class="text-sm text-gray-500">No articles found matching your criteria</p>
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
          class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="showCreateModal ? 'create-article-title' : 'edit-article-title'"
        >
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2
              :id="showCreateModal ? 'create-article-title' : 'edit-article-title'"
              class="text-lg font-semibold text-gray-900"
            >
              {{ showCreateModal ? 'Create Article' : 'Edit Article' }}
            </h2>
            <button
              @click="closeModals"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-4 overflow-y-auto max-h-[calc(90vh-140px)] space-y-5">
            <!-- English Title -->
            <div>
              <label for="article-title-en" class="block text-sm font-medium text-gray-800 mb-1.5">
                Title (English) <span class="text-red-500">*</span>
              </label>
              <input
                id="article-title-en"
                v-model="form.title_en"
                type="text"
                required
                placeholder="e.g. How to Open a Savings Account"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>

            <!-- Malay Title -->
            <div>
              <label for="article-title-ms" class="block text-sm font-medium text-gray-800 mb-1.5">
                Title (Bahasa Melayu)
              </label>
              <input
                id="article-title-ms"
                v-model="form.title_ms"
                type="text"
                placeholder="e.g. Cara Membuka Akaun Simpanan"
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>

            <!-- English Content -->
            <div>
              <label for="article-content-en" class="block text-sm font-medium text-gray-800 mb-1.5">
                Content (English) <span class="text-red-500">*</span>
              </label>
              <textarea
                id="article-content-en"
                v-model="form.content_en"
                rows="4"
                placeholder="Article content in English..."
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
              />
            </div>

            <!-- Malay Content -->
            <div>
              <label for="article-content-ms" class="block text-sm font-medium text-gray-800 mb-1.5">
                Content (Bahasa Melayu)
              </label>
              <textarea
                id="article-content-ms"
                v-model="form.content_ms"
                rows="4"
                placeholder="Kandungan artikel dalam Bahasa Melayu..."
                class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
              />
            </div>

            <!-- Category & Department -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="article-category" class="block text-sm font-medium text-gray-800 mb-1.5">
                  Category <span class="text-red-500">*</span>
                </label>
                <select
                  id="article-category"
                  v-model="form.category"
                  class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="" disabled>Select category</option>
                  <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
              </div>
              <div>
                <label for="article-department" class="block text-sm font-medium text-gray-800 mb-1.5">
                  Department <span class="text-red-500">*</span>
                </label>
                <select
                  id="article-department"
                  v-model="form.department"
                  class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="" disabled>Select department</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.name">
                    {{ dept.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Status & Priority -->
            <div class="grid grid-cols-2 gap-4">
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
              <div>
                <label for="article-priority" class="block text-sm font-medium text-gray-800 mb-1.5">Priority</label>
                <select
                  id="article-priority"
                  v-model="form.priority"
                  class="w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="high">High</option>
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
            </div>

            <!-- Trigger Keywords -->
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1.5">
                Trigger Keywords
              </label>
              <div class="flex gap-2 mb-2">
                <div class="relative flex-1">
                  <TagIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                  <input
                    v-model="newKeyword"
                    type="text"
                    placeholder="Add keyword and press Enter"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    @keydown.enter.prevent="addKeyword"
                  />
                </div>
                <button
                  @click="addKeyword"
                  class="px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                  Add
                </button>
              </div>
              <div v-if="form.trigger_keywords.length > 0" class="flex flex-wrap gap-1.5">
                <span
                  v-for="kw in form.trigger_keywords"
                  :key="kw"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-200"
                >
                  {{ kw }}
                  <button
                    @click="removeKeyword(kw)"
                    class="p-0.5 rounded-full hover:bg-primary-200 transition-colors focus:outline-none"
                    :aria-label="`Remove keyword ${kw}`"
                  >
                    <XMarkIcon class="w-3 h-3" />
                  </button>
                </span>
              </div>
              <p v-else class="text-xs text-gray-400 mt-1">No keywords added yet</p>
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
              :disabled="!form.title_en || !form.category || !form.department || isLoading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              <span v-if="isLoading">Saving...</span>
              <span v-else>{{ showCreateModal ? 'Create Article' : 'Save Changes' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Preview Modal -->
    <Teleport to="body">
      <div
        v-if="showPreviewModal && previewArticle"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="closeModals"
      >
        <div
          class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden"
          role="dialog"
          aria-modal="true"
          aria-labelledby="preview-article-title"
        >
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id="preview-article-title" class="text-lg font-semibold text-gray-900">
              Article Preview
            </h2>
            <button
              @click="closeModals"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-5 overflow-y-auto max-h-[calc(90vh-140px)] space-y-5">
            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                {{ previewArticle.category }}
              </span>
              <span class="text-xs text-gray-400">·</span>
              <span class="text-xs text-gray-600">{{ previewArticle.department }}</span>
              <span class="text-xs text-gray-400">·</span>
              <StatusBadge :status="previewArticle.status" size="sm" />
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border capitalize"
                :class="getPriorityClass(previewArticle.priority)"
              >
                {{ previewArticle.priority }} priority
              </span>
            </div>

            <!-- English Content -->
            <div>
              <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">English</h3>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ previewArticle.title_en }}</h4>
              <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ previewArticle.content_en }}</p>
            </div>

            <!-- Malay Content -->
            <div v-if="previewArticle.title_ms || previewArticle.content_ms" class="pt-4 border-t border-gray-100">
              <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Bahasa Melayu</h3>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ previewArticle.title_ms || '—' }}</h4>
              <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ previewArticle.content_ms || '—' }}</p>
            </div>

            <!-- Keywords -->
            <div v-if="previewArticle.trigger_keywords.length > 0" class="pt-4 border-t border-gray-100">
              <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Trigger Keywords</h3>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="kw in previewArticle.trigger_keywords"
                  :key="kw"
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                >
                  {{ kw }}
                </span>
              </div>
            </div>

            <!-- Timestamps -->
            <div class="pt-4 border-t border-gray-100 flex gap-6">
              <div>
                <p class="text-xs text-gray-500">Created</p>
                <p class="text-sm text-gray-700">{{ previewArticle.created_at }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Updated</p>
                <p class="text-sm text-gray-700">{{ previewArticle.updated_at }}</p>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button
              @click="closeModals"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              Close
            </button>
            <button
              @click="closeModals(); openEditModal(previewArticle)"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
              Edit Article
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
