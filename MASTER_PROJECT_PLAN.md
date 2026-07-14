# PutraKop Live Chat System — Master Project Plan

**Version:** 1.0
**Date:** July 7, 2026
**Status:** Planning Phase

---

## Executive Summary

The PutraKop Live Chat System is an enterprise-grade, AI-powered customer service platform. This master plan consolidates recommendations from four specialist domains: Backend Architecture, UI/UX Design, Security, and Quality Assurance.

---

## 1. Technology Stack (Consensus)

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| **Backend** | Laravel 11 (PHP 8.3+) | Mature ecosystem, built-in auth/broadcasting/queues, strong Malaysian developer community |
| **Frontend** | Vue.js 3 + Inertia.js + Tailwind CSS | Seamless Laravel integration, reactive UI, rapid styling |
| **Real-time** | Laravel Reverb + Echo | First-party Laravel WebSocket, no vendor lock-in, presence channels native |
| **Database** | MySQL 8.0 | Full JSON support, mature, excellent Laravel ORM integration |
| **Cache/Queue** | Redis 7.x + Laravel Horizon | Session, presence, pub/sub, queue with monitoring dashboard |
| **Search** | Laravel Scout + Meilisearch | Typo-tolerant, multilingual (BM/EN), lightweight |
| **AI** | OpenAI GPT-4o-mini | Cost-effective, fast, multilingual. Fallback to knowledge base |
| **File Storage** | S3-compatible (MinIO self-hosted) | Never store files on local filesystem in production |
| **Auth** | Laravel Fortify + Sanctum | Device fingerprinting, API tokens, session management |
| **Testing** | Pest PHP + Vitest + Playwright + k6 | Unit, integration, E2E, performance |
| **CI/CD** | GitHub Actions | Automated testing, quality gates, deployment |

---

## 2. Architecture Overview

### 2.1 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ Customer Chat │  │ Agent Workspace│  │ Manager/Admin Dashboard│ │
│  │ (Vue.js SPA)  │  │ (Vue.js SPA)  │  │ (Vue.js SPA)         │ │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘  │
│         │                  │                     │               │
│  ┌──────┴──────────────────┴─────────────────────┴───────────┐  │
│  │         Device Fingerprint Collection (fingerprintjs2)    │  │
│  └────────────────────────┬──────────────────────────────────┘  │
└───────────────────────────┼──────────────────────────────────────┘
                            │ HTTPS / WSS
┌───────────────────────────┼──────────────────────────────────────┐
│                    EDGE LAYER                                    │
│  ┌────────────────────────┴──────────────────────────────────┐  │
│  │  Reverse Proxy (TLS 1.3, WAF, Rate Limiting, CSP)        │  │
│  └────────────────────────┬──────────────────────────────────┘  │
└───────────────────────────┼──────────────────────────────────────┘
                            │
┌───────────────────────────┼──────────────────────────────────────┐
│                 APPLICATION LAYER                                │
│  ┌────────────────────────┴──────────────────────────────────┐  │
│  │                    Laravel 11                              │  │
│  │  ┌─────────┐  ┌──────────┐  ┌──────────┐  ┌───────────┐  │  │
│  │  │  Auth    │  │   Chat   │  │    AI    │  │   Admin   │  │  │
│  │  │ Module  │  │  Module  │  │  Module  │  │  Module   │  │  │
│  │  └─────────┘  └──────────┘  └──────────┘  └───────────┘  │  │
│  └──────────────────────────────────────────────────────────┘  │
│  ┌──────────────────┐  ┌───────────────────────────────────┐   │
│  │ Laravel Reverb    │  │ Queue Workers (Horizon)           │   │
│  │ WebSocket Server  │  │ AI Processing, File Upload, etc.  │   │
│  └──────────────────┘  └───────────────────────────────────┘   │
└───────────────────────────┬──────────────────────────────────────┘
                            │
┌───────────────────────────┼──────────────────────────────────────┐
│                     DATA LAYER                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ MySQL 8.0     │  │ Redis 7.x    │  │ S3/MinIO             │   │
│  │ Encrypted     │  │ Sessions     │  │ File Storage         │   │
│  │ At Rest       │  │ Cache/PubSub │  │ Signed URLs          │   │
│  └──────────────┘  └──────────────┘  └──────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Backend Directory Structure

```
app/
├── Actions/                    # Single-purpose action classes
│   ├── Chat/                   # StartConversation, SendMessage, Transfer, Close, Rate
│   ├── Agent/                  # AcceptConversation, SetStatus, AddNote
│   └── AI/                     # ProcessGreeting, RouteToDepartment, GenerateResponse
├── Enums/                      # PHP 8.1+ Enums
│   ├── ConversationStatus.php  # pending, queued, active, transferred, closed
│   ├── AgentStatus.php         # online, away, busy, offline
│   ├── SenderType.php          # customer, agent, ai, system
│   ├── Language.php            # BM, EN
│   └── UserRole.php            # customer, agent, manager, admin
├── Events/                     # Broadcast + domain events
│   ├── Chat/                   # MessageSent, TypingStarted, ConversationAssigned
│   ├── Agent/                  # AgentStatusChanged
│   └── Dashboard/              # StatsUpdated
├── Http/
│   ├── Controllers/Api/V1/     # REST API endpoints
│   ├── Middleware/              # EnsureAgentIsOnline, RateLimitApi, etc.
│   ├── Requests/               # Form request validation
│   └── Resources/              # API resource transformers
├── Jobs/                       # Queue jobs
│   ├── AI/                     # ProcessAIResponse, EmbedKB
│   ├── Chat/                   # ProcessFileUpload, GenerateTranscript
│   └── Analytics/              # AggregateDailyStats
├── Models/                     # Eloquent models
├── Observers/                  # Model event hooks
├── Policies/                   # Authorization policies
├── Services/                   # Business logic layer
│   ├── AI/                     # AIOrchestrator, LanguageDetector, KBSearch
│   ├── Chat/                   # ConversationService, MessageService
│   ├── Queue/                  # QueueManager, WaitTimeEstimator
│   ├── Agent/                  # RoutingService, CapacityService
│   └── Analytics/              # DashboardStatsService, ReportGenerator
└── Traits/                     # HasUuid, AuditLogged, FiltersByDepartment
```

### 2.3 API Structure (Hybrid Approach)

**Web Routes (Inertia.js):**
- `/chat` → Customer chat widget
- `/agent/workspace` → Agent workspace
- `/manager/dashboard` → Real-time dashboard
- `/admin/*` → Full admin panel

**API Routes (JSON /api/v1):**
- Auth: `POST /api/v1/auth/device/register`, `POST /api/v1/auth/login`
- Chat: `POST /api/v1/conversations`, `POST /api/v1/conversations/{id}/messages`
- Queue: `GET /api/v1/queue/status`
- Agent: `POST /api/v1/agent/conversations/{id}/accept`
- Manager: `GET /api/v1/manager/dashboard`
- Admin: CRUD for departments, users, settings, knowledge base

**Broadcast Channels:**
- `conversation.{id}` → Private (customer + assigned agent + AI)
- `agents.{department}` → Presence (agents in department)
- `dashboard` → Private (managers only)

---

## 3. Database Schema (Core Entities)

### 3.1 Entity Relationship Map

```
users ─────────┬── hasMany: devices
(shared auth)  ├── hasOne: agent_profile → belongsTo: department
               └── polymorphic: conversations, messages

departments ───┬── hasMany: conversations
(admin-config) ├── hasMany: agents
               └── hasMany: quick_replies

conversations ─┬── belongsTo: customer (users)
(1:1 chat)     ├── belongsTo: agent (users, nullable)
               ├── belongsTo: department
               ├── hasMany: messages
               ├── hasMany: internal_notes
               ├── hasOne: rating
               └── belongsTo: queue

messages ──────┬── morphTo: sender (user or AI)
               └── hasMany: message_deliveries

queues ────────┬── belongsTo: department
(per dept)     └── hasMany: queue_entries → belongsTo: conversation

knowledge_base └── hasMany: kb_embeddings (vector search)

settings ──────└── key-value store for system config

audit_logs ────└── polymorphic: auditable (any model)
```

### 3.2 Key Tables

| Table | Key Columns | Purpose |
|-------|------------|---------|
| `users` | id, name, email, role, department_id, status, language_preference | All user accounts |
| `user_devices` | user_id, fingerprint_hash, device_name, is_trusted | Device registration |
| `departments` | name_en, name_bm, is_active, business_hours, ai_config | Configurable departments |
| `conversations` | uuid, customer_id, agent_id, department_id, status, priority | Chat sessions |
| `messages` | conversation_id, sender_type, sender_id, body, type, metadata | All messages |
| `queue` | department_id, position, priority_score, estimated_wait_seconds | Queue management |
| `ratings` | conversation_id, rating (1-5), feedback, complaint | Post-chat feedback |
| `knowledge_base` | title_en, title_bm, content_en, content_bm, department_id | AI knowledge base |
| `audit_logs` | auditable_type, auditable_id, event, old_values, new_values | Audit trail |

### 3.3 Indexing Strategy

```
conversations:
  - INDEX (customer_id, status)     → "my active chats"
  - INDEX (agent_id, status)        → "agent's active chats"
  - INDEX (department_id, status)   → queue management
  - UNIQUE INDEX (uuid)             → URL/broadcast lookups

messages:
  - INDEX (conversation_id, created_at)  → chat history (CRITICAL)
  - INDEX (sender_type, created_at)      → analytics

queue:
  - INDEX (department_id, status, position)  → "who's next?"
  - UNIQUE INDEX (conversation_id)           → one entry per conversation

audit_logs:
  - INDEX (auditable_type, auditable_id)  → model audit trail
  - INDEX (created_at)                     → time-based queries
```

---

## 4. Security Architecture

### 4.1 Threat Model — Top 10 Risks

| Rank | Threat | Risk Score | Mitigation |
|------|--------|------------|------------|
| 1 | WebSocket Hijacking | 20 (CRITICAL) | Server-side channel auth, UUID identifiers, ACL |
| 2 | Stored XSS via Chat Messages | 20 (CRITICAL) | Plain text storage, DOMPurify, CSP, no v-html |
| 3 | IDOR — Accessing Other Users' Conversations | 20 (CRITICAL) | Policy-based auth, UUID IDs, ownership checks |
| 4 | Prompt Injection via AI | 16 (HIGH) | Input sanitization, output filtering, rate limiting |
| 5 | Malicious File Upload | 15 (HIGH) | Magic byte validation, store outside web root, scan |
| 6 | Session Hijacking via Device Fingerprint | 15 (HIGH) | Supplementary factor only, hash before storage |
| 7 | Privilege Escalation | 15 (HIGH) | Server-side RBAC, no mass assignment, Policies |
| 8 | PII Leakage in Logs/AI | 16 (HIGH) | PII masking, structured logging, data minimization |
| 9 | CSRF on Chat Operations | 12 (MEDIUM) | Laravel CSRF, SameSite cookies, Sanctum tokens |
| 10 | OpenAI API Key Abuse | 12 (MEDIUM) | Env vars only, spending limits, circuit breaker |

### 4.2 Authentication Flow

```
1. User submits credentials (email + password)
2. Validate against Argon2id hash
3. Check account status (active, locked)
4. IF new device (fingerprint mismatch) → Require MFA
5. Generate session token (256-bit random)
6. Bind session to device fingerprint hash
7. Set secure cookies (HttpOnly, Secure, SameSite=Strict)
8. Create Sanctum personal access token
9. Log auth event with IP, device, timestamp
10. Return session + token
```

### 4.3 RBAC Matrix

| Permission | Customer | Agent | Manager | Admin |
|------------|----------|-------|---------|-------|
| Send message | ✓ (own) | ✓ (assigned) | ✓ (dept) | ✓ (all) |
| Close conversation | ✗ | ✓ (assigned) | ✓ (dept) | ✓ (all) |
| View analytics | ✗ | ✓ (self) | ✓ (dept) | ✓ (all) |
| Manage users | ✗ | ✗ | ✗ | ✓ |
| Configure AI | ✗ | ✗ | ✗ | ✓ |
| View audit log | ✗ | ✗ | ✓ (dept) | ✓ (all) |
| Transfer chat | ✗ | ✓ (own) | ✓ (dept) | ✓ (all) |

### 4.4 Key Security Controls

- **Password:** Argon2id, min 12 chars, HaveIBeenPwned check
- **MFA:** TOTP (Google Authenticator) + Email OTP fallback
- **Sessions:** Redis-backed, 24hr lifetime, 30min idle timeout for staff
- **Rate Limiting:** 60 req/min customers, 120 req/min agents, 5 login attempts/min
- **File Upload:** Magic byte + MIME + extension validation, store outside web root
- **PII:** Field-level encryption at rest, masking in logs and AI prompts
- **Compliance:** Malaysia PDPA 2010 — consent management, data retention, right to deletion

---

## 5. UI/UX Design System

### 5.1 Design Tokens

| Token | Value | Usage |
|-------|-------|-------|
| **Primary Blue** | #1E3A5F (dark) → #3B82F6 (medium) → #DBEAFE (light) | Brand, CTAs, links |
| **Neutral** | #111827 → #F9FAFB | Text, backgrounds, borders |
| **Success** | #059669 | Online status, success messages |
| **Warning** | #D97706 | Queue alerts, away status |
| **Error** | #DC2626 | Errors, urgent items |
| **Font** | Inter (400, 500, 600, 700) | All UI text |
| **Base Size** | 14px | Optimized for data-dense SaaS |
| **Spacing** | 4px grid (0.5 → 16 tokens) | Consistent visual rhythm |
| **Shadows** | 7-level elevation system | xs → 2xl for modals |
| **Icons** | Lucide | 5 size tokens (14px → 32px) |

### 5.2 Layout Architecture

**Agent Workspace (3-panel):**
```
┌──────────┬────────────────────────┬──────────────┐
│ 240px    │     Flex Chat Area     │   320px      │
│ Sidebar  │  (messages + input)    │   Context    │
│ Convos   │                        │   Panel      │
│ List     │                        │  (customer   │
│          │                        │   info,      │
│          │                        │   notes,     │
│          │                        │   actions)   │
└──────────┴────────────────────────┴──────────────┘
```

**Responsive Breakpoints:**
- Mobile (0-639px): Full-screen chat, hamburger sidebar
- Tablet (640-1023px): Collapsible 64px icon sidebar
- Desktop (1024px+): Full 3-panel workspace

### 5.3 Chat Interface Design

| Element | Customer | Agent | AI | System |
|---------|----------|-------|-----|--------|
| **Bubble** | Blue, right-aligned | White, left-aligned | Green-tinted | Centered, gray |
| **Radius** | 16/16/4/16 | 16/16/16/4 | 16/16/16/4 | 8 all |
| **Read Receipts** | ✓ sent → ✓✓ delivered → ✓✓ blue (read) |
| **Typing** | 3 bouncing dots, 150ms stagger |
| **Optimistic UI** | Messages appear at 80% opacity, solidify on confirm |

### 5.4 Component Library

- 7 button variants (primary, secondary, ghost, danger, outline, link, icon)
- 6 form input types (text, select, checkbox, toggle, textarea, search)
- 5 card styles (default, elevated, outlined, interactive, stat)
- Badge/tag system, modals, toasts, skeleton/spinner loading, empty states

---

## 6. Testing Strategy

### 6.1 Testing Pyramid

```
              ┌─────────┐
              │  E2E    │  10% (Critical paths)
              │ Playwright│
             ┌┴─────────┴┐
             │Integration │  30% (API, WebSocket, Queue)
             │  Pest PHP  │
            ┌┴───────────┴┐
            │  Unit Tests  │  60% (Models, Services, Components)
            │  Pest + Vitest│
            └──────────────┘
```

### 6.2 Quality Gates

| Gate | Threshold | Tool |
|------|-----------|------|
| PHPStan Level | 8 (zero errors) | Static analysis |
| ESLint + Prettier | Zero warnings | Linting |
| Unit Test Coverage | ≥80% lines, ≥90% services | Pest PHP |
| API Response Time | P95 < 500ms | k6 |
| WebSocket Connect | < 200ms | k6 |
| Concurrent Users | 500+ steady | k6 |
| Security Scan | No critical/high | OWASP ZAP |
| Lighthouse Score | ≥ 90 | Lighthouse CI |

### 6.3 Critical Test Journeys

1. **Customer:** Login → Start chat → AI greeting → Department select → AI response → Agent handoff → Rating
2. **Agent:** Login → Accept chat → Send response → Use canned reply → Transfer → Close
3. **Manager:** Login → View dashboard → Monitor live → Intervene → View analytics → Export
4. **Admin:** Login → Manage departments → Configure AI → Set business hours → View audit log

### 6.4 Performance Benchmarks

| Metric | Target | Tool |
|--------|--------|------|
| API P95 response | < 500ms | k6 |
| Message broadcast | < 200ms P95 | k6 |
| DB query P95 | < 100ms | Pest benchmark |
| Concurrent WebSocket | 500+ connections | k6 |
| Spike recovery | < 30s to baseline | k6 |
| Memory (4hr soak) | < 512MB growth | k6 |

---

## 7. Phased Delivery Plan

### Phase 1: Foundation (Weeks 1-6)
**Milestone: Internal Alpha**

| Week | Deliverables |
|------|--------------|
| 1-2 | Project setup, DB schema, authentication, device registration |
| 3-4 | Department system, basic routing, agent workspace shell |
| 5-6 | Real-time messaging (text), WebSocket infrastructure |

**Exit Criteria:**
- Users can register/login with device persistence
- Departments configurable
- Basic real-time text messaging works

---

### Phase 2: AI & Intelligence (Weeks 7-10)
**Milestone: Beta — AI and smart routing operational**

| Week | Deliverables |
|------|--------------|
| 7-8 | AI assistant integration, welcome flow, language detection |
| 9-10 | Knowledge base, AI Q&A, smart routing algorithm |

**Exit Criteria:**
- AI greets customers and guides department selection
- AI answers questions from knowledge base
- Smart routing assigns chats based on availability

---

### Phase 3: Rich Features (Weeks 11-14)
**Milestone: Feature-complete Beta**

| Week | Deliverables |
|------|--------------|
| 11-12 | File/image sharing, emoji, typing indicators, read receipts |
| 13-14 | Queue management, estimated wait time, post-chat rating |

**Exit Criteria:**
- Full rich messaging (images, files, emoji)
- Queue system with position tracking
- Post-chat rating system functional

---

### Phase 4: Management & Analytics (Weeks 15-18)
**Milestone: Manager Dashboard and Admin Panel complete**

| Week | Deliverables |
|------|--------------|
| 15-16 | Manager dashboard, real-time monitoring, intervention |
| 17-18 | Admin panel, user/dept management, settings, reports |

**Exit Criteria:**
- Managers can monitor all active conversations
- Full admin panel with all CRUD operations
- Analytics reports functional

---

### Phase 5: Polish & Launch (Weeks 19-22)
**Milestone: Production Ready**

| Week | Deliverables |
|------|--------------|
| 19-20 | UI polish, responsive optimization, performance tuning |
| 21-22 | Security audit, load testing, bug fixes, UAT |

**Exit Criteria:**
- All UI responsive across devices
- Performance benchmarks met
- Security audit passed
- UAT completed
- Documentation complete

---

## 8. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| WebSocket scalability issues | Medium | High | Redis pub/sub, connection pooling, load test early |
| AI API costs exceeding budget | Medium | Medium | KB caching, usage limits, fallback to KB |
| Security vulnerabilities | Medium | High | Laravel protections, CSP, regular audits |
| Performance degradation | Medium | High | DB indexing, query optimization, CDN |
| Scope creep | High | Medium | Strict change control, documented requirements |
| PDPA compliance gaps | Medium | High | Data encryption, consent management, retention policies |

---

## 9. Success Metrics

| Metric | Target |
|--------|--------|
| Message Delivery Latency | < 200ms (P95) |
| AI First Response | < 3 seconds |
| Agent First Response | < 60 seconds |
| System Uptime | 99.9% |
| Customer Satisfaction (CSAT) | > 4.0 / 5.0 |
| Concurrent Users | 500+ |
| Page Load Time | < 2 seconds |
| Mobile Responsiveness | All core features |

---

## 10. Specialist Reports (Full Detail)

The following detailed reports are available:

| Report | Location | Length |
|--------|----------|--------|
| Backend Architecture | Full analysis by PHP/Laravel specialist | ~21,000 words |
| UI/UX Design Plan | Full design system and wireframes | ~1,847 lines |
| Security Architecture | Threat model and controls | ~15,000 words |
| QA & Testing Strategy | Complete testing plan | ~15,000 words |

---

*Document Version: 1.0*
*Last Updated: July 7, 2026*
*Prepared by: Engineering Director with Specialist Agents*
