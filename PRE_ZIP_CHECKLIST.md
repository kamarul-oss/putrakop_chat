# Pre-Zip Checklist — PutraKop Live Chat System

**Use this checklist to verify ALL files are ready before zipping for deployment.**

---

## Instructions

1. Open your project folder: `C:\KAMARUL\1.PROJECT\OPENCODE\putrakop_live_chat`
2. Check each item below
3. Mark ✅ if the file/folder exists
4. Mark ❌ if missing (you need to create it)
5. Once all items are ✅, you can zip and upload

---

## Root Files (in putrakop_live_chat/)

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `artisan` | ☐ | Laravel command-line tool |
| 2 | `composer.json` | ☐ | PHP dependencies list |
| 3 | `composer.lock` | ☐ | PHP dependencies lock file |
| 4 | `package.json` | ☐ | JavaScript dependencies list |
| 5 | `package-lock.json` | ☐ | JavaScript dependencies lock file |
| 6 | `vite.config.js` | ☐ | Vite build configuration |
| 7 | `tailwind.config.js` | ☐ | Tailwind CSS configuration |
| 8 | `.env.example` | ☐ | Environment file template |
| 9 | `README.md` | ☐ | Project documentation |

**Root Files Count: 9 files**

---

## app/ Folder (Backend Code)

### app/Enums/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AgentStatus.php` | ☐ | Agent status enum |
| 2 | `ConversationStatus.php` | ☐ | Conversation status enum |
| 3 | `Language.php` | ☐ | Language enum |
| 4 | `SenderType.php` | ☐ | Message sender type enum |
| 5 | `UserRole.php` | ☐ | User role enum |

**Enums Count: 5 files**

---

### app/Events/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Chat/MessageSent.php` | ☐ | New message event |
| 2 | `Chat/TypingStarted.php` | ☐ | Typing indicator start |
| 3 | `Chat/TypingStopped.php` | ☐ | Typing indicator stop |
| 4 | `Chat/MessagesRead.php` | ☐ | Messages read event |
| 5 | `Chat/ConversationAssigned.php` | ☐ | Conversation assigned to agent |
| 6 | `Chat/ConversationCreated.php` | ☐ | New conversation created |
| 7 | `Chat/ConversationClosed.php` | ☐ | Conversation closed |
| 8 | `Agent/AgentStatusChanged.php` | ☐ | Agent status changed |

**Events Count: 8 files**

---

### app/Http/Controllers/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Controller.php` | ☐ | Base controller |

---

### app/Http/Controllers/Api/V1/Auth/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DeviceController.php` | ☐ | Device registration |
| 2 | `LoginController.php` | ☐ | User login/logout |
| 3 | `RegisterController.php` | ☐ | User registration |

**Auth Controllers Count: 3 files**

---

### app/Http/Controllers/Api/V1/Admin/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AdminDashboardController.php` | ☐ | Admin dashboard |
| 2 | `AuditController.php` | ☐ | Audit log viewer |
| 3 | `DepartmentConfigController.php` | ☐ | Department config |
| 4 | `DepartmentController.php` | ☐ | Department CRUD |
| 5 | `KnowledgeBaseController.php` | ☐ | Knowledge base CRUD |
| 6 | `SettingController.php` | ☐ | Settings management |
| 7 | `UserController.php` | ☐ | User CRUD |

**Admin Controllers Count: 7 files**

---

### app/Http/Controllers/Api/V1/Agent/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `ChatController.php` | ☐ | Agent chat operations |
| 2 | `QueueController.php` | ☐ | Queue management |
| 3 | `WorkspaceController.php` | ☐ | Agent workspace |

**Agent Controllers Count: 3 files**

---

### app/Http/Controllers/Api/V1/Customer/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AIChatController.php` | ☐ | AI chat interface |
| 2 | `ChatController.php` | ☐ | Customer chat |
| 3 | `DepartmentController.php` | ☐ | Department listing |
| 4 | `QueueController.php` | ☐ | Queue status |
| 5 | `RatingController.php` | ☐ | Rating submission |

**Customer Controllers Count: 5 files**

---

### app/Http/Controllers/Api/V1/Manager/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AgentController.php` | ☐ | Agent management |
| 2 | `AIController.php` | ☐ | AI configuration |
| 3 | `DashboardController.php` | ☐ | Manager dashboard |
| 4 | `InterventionController.php` | ☐ | Conversation intervention |
| 5 | `RatingController.php` | ☐ | Rating viewing |
| 6 | `ReportController.php` | ☐ | Reports generation |

**Manager Controllers Count: 6 files**

---

### app/Http/Controllers/Api/V1/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `HealthController.php` | ☐ | Health check endpoint |

**Root Controllers Count: 1 file**

---

### app/Http/Controllers/Agent/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `FAQController.php` | ☐ | Agent FAQ management |

**Legacy Agent Controllers Count: 1 file**

---

### app/Http/Controllers/Manager/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `FAQController.php` | ☐ | Manager FAQ management |

**Legacy Manager Controllers Count: 1 file**

---

### app/Http/Middleware/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `SecurityHeadersMiddleware.php` | ☐ | Security headers |

**Middleware Count: 1 file**

---

### app/Http/Requests/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `StoreFAQRequest.php` | ☐ | Agent FAQ validation |
| 2 | `UpdateFAQRequest.php` | ☐ | Agent FAQ update validation |
| 3 | `ManagerStoreFAQRequest.php` | ☐ | Manager FAQ validation |
| 4 | `ManagerUpdateFAQRequest.php` | ☐ | Manager FAQ update validation |

---

### app/Http/Requests/Admin/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `StoreDepartmentRequest.php` | ☐ | Department create validation |
| 2 | `UpdateDepartmentRequest.php` | ☐ | Department update validation |
| 3 | `StoreUserRequest.php` | ☐ | User create validation |
| 4 | `UpdateUserRequest.php` | ☐ | User update validation |
| 5 | `UpdateSettingsRequest.php` | ☐ | Settings update validation |
| 6 | `StoreKnowledgeBaseRequest.php` | ☐ | KB create validation |
| 7 | `UpdateKnowledgeBaseRequest.php` | ☐ | KB update validation |
| 8 | `AuditLogRequest.php` | ☐ | Audit log filter validation |
| 9 | `ExportAuditRequest.php` | ☐ | Audit export validation |
| 10 | `UpdateRoutingRulesRequest.php` | ☐ | Routing rules validation |
| 11 | `UpdateAIConfigRequest.php` | ☐ | AI config validation |

**Requests Count: 15 files**

---

### app/Jobs/AI/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `ProcessAIResponse.php` | ☐ | Process AI response job |
| 2 | `GenerateGreeting.php` | ☐ | Generate AI greeting job |
| 3 | `DetectLanguage.php` | ☐ | Detect language job |

**Jobs Count: 3 files**

---

### app/Models/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AuditLog.php` | ☐ | Audit log model |
| 2 | `Conversation.php` | ☐ | Conversation model |
| 3 | `Department.php` | ☐ | Department model |
| 4 | `DepartmentResponse.php` | ☐ | FAQ response model |
| 5 | `InternalNote.php` | ☐ | Internal note model |
| 6 | `KnowledgeBase.php` | ☐ | Knowledge base model |
| 7 | `Message.php` | ☐ | Message model |
| 8 | `Queue.php` | ☐ | Queue model |
| 9 | `Rating.php` | ☐ | Rating model |
| 10 | `Setting.php` | ☐ | Setting model |
| 11 | `User.php` | ☐ | User model |
| 12 | `UserDevice.php` | ☐ | User device model |

**Models Count: 12 files**

---

### app/Observers/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DepartmentResponseObserver.php` | ☐ | FAQ response observer |

**Observers Count: 1 file**

---

### app/Policies/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DepartmentResponsePolicy.php` | ☐ | FAQ response policy |
| 2 | `DepartmentPolicy.php` | ☐ | Department policy |
| 3 | `UserPolicy.php` | ☐ | User policy |
| 4 | `SettingPolicy.php` | ☐ | Setting policy |
| 5 | `KnowledgeBasePolicy.php` | ☐ | Knowledge base policy |

**Policies Count: 5 files**

---

### app/Providers/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AuthServiceProvider.php` | ☐ | Auth service provider |
| 2 | `RateLimiterServiceProvider.php` | ☐ | Rate limiter provider |

**Providers Count: 2 files**

---

### app/Scopes/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DepartmentScope.php` | ☐ | Department scope |

**Scopes Count: 1 file**

---

### app/Services/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AIService.php` | ☐ | AI service |
| 2 | `BusinessHoursService.php` | ☐ | Business hours |
| 3 | `DepartmentResponseService.php` | ☐ | FAQ response service |

---

### app/Services/AI/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `AIOrchestrator.php` | ☐ | AI orchestration |
| 2 | `GeminiService.php` | ☐ | Gemini API integration |
| 3 | `KBSearchService.php` | ☐ | Knowledge base search |
| 4 | `LanguageDetector.php` | ☐ | Language detection |

**AI Services Count: 4 files**

---

### app/Services/Analytics/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DashboardStatsService.php` | ☐ | Dashboard statistics |
| 2 | `ReportGenerator.php` | ☐ | Report generation |
| 3 | `ExportService.php` | ☐ | Data export |

**Analytics Services Count: 3 files**

---

### app/Services/Chat/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `ConversationService.php` | ☐ | Conversation management |
| 2 | `EmojiService.php` | ☐ | Emoji support |
| 3 | `FileUploadService.php` | ☐ | File uploads |
| 4 | `MessageService.php` | ☐ | Message handling |
| 5 | `QueueService.php` | ☐ | Queue management |
| 6 | `RatingService.php` | ☐ | Rating system |
| 7 | `ReadReceiptService.php` | ☐ | Read receipts |
| 8 | `RoutingService.php` | ☐ | Chat routing |
| 9 | `SmartRoutingService.php` | ☐ | Smart routing algorithm |
| 10 | `TypingService.php` | ☐ | Typing indicators |

**Chat Services Count: 10 files**

---

### app/Services/Monitoring/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `PerformanceService.php` | ☐ | Performance monitoring |
| 2 | `SecurityAuditService.php` | ☐ | Security audit |
| 3 | `CacheWarmingService.php` | ☐ | Cache warming |

**Monitoring Services Count: 3 files**

---

**Total Backend Files Count: 100+ files**

---

## config/ Folder

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `security.php` | ☐ | Security configuration |

**Config Count: 1 file**

---

## database/ Folder

### database/migrations/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `0001_01_01_000000_create_users_table.php` | ☐ | Users table |
| 2 | `0001_01_01_000001_create_user_devices_table.php` | ☐ | User devices table |
| 3 | `0001_01_01_000002_create_departments_table.php` | ☐ | Departments table |
| 4 | `2026_07_08_000001_create_department_responses_table.php` | ☐ | FAQ responses table |
| 5 | `2026_07_08_000002_create_conversations_table.php` | ☐ | Conversations table |
| 6 | `2026_07_08_000003_create_messages_table.php` | ☐ | Messages table |
| 7 | `2026_07_08_000004_create_queues_table.php` | ☐ | Queues table |
| 8 | `2026_07_08_000005_create_ratings_table.php` | ☐ | Ratings table |
| 9 | `2026_07_08_000006_create_knowledge_base_table.php` | ☐ | Knowledge base table |
| 10 | `2026_07_08_000007_create_settings_table.php` | ☐ | Settings table |
| 11 | `2026_07_08_000008_create_audit_logs_table.php` | ☐ | Audit logs table |
| 12 | `2026_07_08_000009_create_internal_notes_table.php` | ☐ | Internal notes table |

**Migrations Count: 12 files**

---

### database/seeders/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `DatabaseSeeder.php` | ☐ | Main database seeder |
| 2 | `DepartmentResponseSeeder.php` | ☐ | FAQ responses seeder |

**Seeders Count: 2 files**

---

## resources/js/Pages/ (Vue.js Frontend)

### resources/js/Pages/admin/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Analytics.vue` | ☐ | Analytics dashboard |
| 2 | `AuditLog.vue` | ☐ | Audit log viewer |
| 3 | `Departments.vue` | ☐ | Department management |
| 4 | `KnowledgeBase.vue` | ☐ | Knowledge base management |
| 5 | `Layout.vue` | ☐ | Admin layout |
| 6 | `RoutingConfig.vue` | ☐ | Routing configuration |
| 7 | `Settings.vue` | ☐ | System settings |
| 8 | `SystemMonitor.vue` | ☐ | System monitor |
| 9 | `Users.vue` | ☐ | User management |

**Admin Pages Count: 9 files**

---

### resources/js/Pages/agent/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Workspace.vue` | ☐ | Agent workspace |

**Agent Pages Count: 1 file**

---

### resources/js/Pages/auth/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Login.vue` | ☐ | Login page |
| 2 | `Register.vue` | ☐ | Registration page |

**Auth Pages Count: 2 files**

---

### resources/js/Pages/components/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `ChatMessage.vue` | ☐ | Chat message component |
| 2 | `StatusBadge.vue` | ☐ | Status badge component |

**Components Count: 2 files**

---

### resources/js/Pages/customer/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Chat.vue` | ☐ | Customer chat |

**Customer Pages Count: 1 file**

---

### resources/js/Pages/manager/

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `Dashboard.vue` | ☐ | Manager dashboard |

**Manager Pages Count: 1 file**

---

## routes/ Folder

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `api.php` | ☐ | API routes |
| 2 | `channels.php` | ☐ | Broadcast channels |
| 3 | `web.php` | ☐ | Web routes |

**Routes Count: 3 files**

---

## Summary Checklist

| Category | Expected | Actual | Status |
|----------|----------|--------|--------|
| Root Files | 9 | ___ | ☐ |
| Enums | 5 | ___ | ☐ |
| Events | 8 | ___ | ☐ |
| Controllers | 32 | ___ | ☐ |
| Middleware | 1 | ___ | ☐ |
| Requests | 15 | ___ | ☐ |
| Jobs | 3 | ___ | ☐ |
| Models | 12 | ___ | ☐ |
| Observers | 1 | ___ | ☐ |
| Policies | 5 | ___ | ☐ |
| Providers | 2 | ___ | ☐ |
| Scopes | 1 | ___ | ☐ |
| Services | 23 | ___ | ☐ |
| Config | 1 | ___ | ☐ |
| Migrations | 12 | ___ | ☐ |
| Seeders | 2 | ___ | ☐ |
| Vue Pages | 16 | ___ | ☐ |
| Routes | 3 | ___ | ☐ |
| **TOTAL** | **~150** | ___ | ☐ |

---

## Files to EXCLUDE from ZIP

**Do NOT include these files/folders:**

| File/Folder | Reason |
|-------------|--------|
| `.env` | Contains sensitive data, create on server |
| `node_modules/` | Too large, run `npm install` on server |
| `.git/` | Version control, not needed for deployment |
| `storage/logs/*.log` | Log files, not needed |
| `bootstrap/cache/*.php` | Cache files, will be regenerated |
| `vendor/` | PHP dependencies, run `composer install` on server |
| `*.md` | Documentation files, not needed for deployment |
| `tests/` | Test files, not needed for production |

---

## Final Steps Before Zipping

1. ☐ Run `composer install --no-dev --optimize-autoloader` (on your computer)
2. ☐ Run `npm install` (on your computer)
3. ☐ Run `npm run build` (on your computer)
4. ☐ Clear cache: `php artisan config:clear && php artisan route:clear`
5. ☐ Verify all checklist items are ✅
6. ☐ Right-click project folder → Send to → Compressed (zipped) folder
7. ☐ Name it: `putrakop_live_chat.zip`
8. ☐ Check ZIP file size (should be under 100MB)

---

## Need Help?

If any files are missing:
1. Check if you ran all the specialist agents
2. Check the implementation summary: `IMPLEMENTATION_SUMMARY.md`
3. Check the master project plan: `MASTER_PROJECT_PLAN.md`

---

*Last Updated: July 8, 2026*
