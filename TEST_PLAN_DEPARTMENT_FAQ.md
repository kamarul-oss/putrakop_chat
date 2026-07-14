# Department FAQ Management System — Comprehensive Test Plan

**Project:** PutraKop Live Chat  
**Module:** Department FAQ (DepartmentResponse) Management  
**Version:** 1.0  
**Date:** July 8, 2026  
**Author:** Senior QA Architect  
**Status:** Ready for Execution  

---

## Table of Contents

1. [Test Strategy Overview](#1-test-strategy-overview)
2. [Test Environment Requirements](#2-test-environment-requirements)
3. [Test Data Setup](#3-test-data-setup)
4. [Unit Test Cases](#4-unit-test-cases)
5. [Integration Test Cases](#5-integration-test-cases)
6. [End-to-End Test Scenarios](#6-end-to-end-test-scenarios)
7. [Edge Case Tests](#7-edge-case-tests)
8. [Acceptance Criteria](#8-acceptance-criteria)
9. [Defect Severity Classification](#9-defect-severity-classification)
10. [Test Execution Checklist](#10-test-execution-checklist)

---

## 1. Test Strategy Overview

### 1.1 Testing Pyramid

```
                    ┌─────────────┐
                    │     E2E     │  15% (Critical user journeys)
                    │  Playwright  │
                   ┌┴─────────────┴┐
                   │  Integration   │  35% (API, Auth, Database)
                   │   Pest PHP    │
                  ┌┴───────────────┴┐
                  │   Unit Tests     │  50% (Models, Services, Policies)
                  │    Pest PHP     │
                  └─────────────────┘
```

### 1.2 Scope

**In Scope:**
- `DepartmentResponse` model (CRUD, scopes, relationships)
- `DepartmentResponsePolicy` (all authorization rules)
- `DepartmentResponseService` (keyword matching, fallback, bilingual)
- API endpoints for Agent and Manager FAQ management
- Approval workflow (pending → approved/rejected)
- AI fallback integration
- Bilingual content handling (EN/BM)
- Role-based access control enforcement

**Out of Scope:**
- Admin FAQ management UI (covered by Manager tests)
- Customer-facing chat widget
- Gemini API implementation details (mocked)
- WebSocket real-time events

### 1.3 Test Tools

| Tool | Purpose | Version |
|------|---------|---------|
| Pest PHP | Unit & Integration tests | 2.x |
| Laravel Testing Helpers | HTTP tests, database assertions | Built-in |
| Playwright | E2E browser tests | 1.x |
| Mockery | Service mocking | 2.x |

### 1.4 Entry Criteria

- All migrations run successfully
- Test database is isolated (separate DB or in-memory)
- Authentication system functional
- Department and User models seeded

### 1.5 Exit Criteria

- 100% of critical test cases pass
- 95% of high-severity test cases pass
- No Critical or High defects remain open
- Code coverage ≥ 80% for Model, Policy, Service

---

## 2. Test Environment Requirements

### 2.1 Database

```yaml
Testing Database:
  Type: MySQL 8.0 (or SQLite in-memory for unit tests)
  Name: putrakop_live_chat_testing
  Isolation: Separate database or refreshed between tests
  
Required Tables:
  - users (with role enum: customer, agent, manager, admin)
  - departments
  - department_responses
  - sessions (for auth testing)
```

### 2.2 Environment Variables

```env
APP_ENV=testing
DB_CONNECTION=mysql
DB_DATABASE=putrakop_live_chat_testing
QUEUE_CONNECTION=sync
MAIL_MAILER=log
GEMINI_API_KEY=mock-key-for-testing
```

### 2.3 Seed Data Dependencies

- `Department` model with at least 3 departments
- `User` model with roles: admin, manager, agent
- Each user assigned to a department (except admin)

---

## 3. Test Data Setup

### 3.1 Department Fixtures

| ID | name_en | name_bm | is_active |
|----|---------|---------|-----------|
| 1 | Account Services | Perkhidmatan Akaun | true |
| 2 | Technical Support | Sokongan Teknikal | true |
| 3 | Loan Department | Jabatan Pinjaman | true |

### 3.2 User Fixtures

| ID | name | role | department_id | email |
|----|------|------|---------------|-------|
| 1 | Admin User | admin | null | admin@putrakop.test |
| 2 | Manager Account | manager | 1 | manager.account@putrakop.test |
| 3 | Manager Technical | manager | 2 | manager.tech@putrakop.test |
| 4 | Agent Ahmad | agent | 1 | agent.ahmad@putrakop.test |
| 5 | Agent Siti | agent | 1 | agent.siti@putrakop.test |
| 6 | Agent Lee | agent | 2 | agent.lee@putrakop.test |

### 3.3 DepartmentResponse Fixtures

| ID | dept_id | response_key | content_en | content_bm | trigger_keywords | is_active | is_approved | created_by |
|----|---------|--------------|------------|------------|------------------|-----------|-------------|------------|
| 1 | 1 | account_balance | How to check balance | Cara semak baki | ["balance", "baki", "check balance"] | true | true | 4 |
| 2 | 1 | transfer_money | How to transfer money | Cara pindah wang | ["transfer", "pindah", "send money"] | true | true | 4 |
| 3 | 1 | pending_approval | New FAQ pending | FAQ baru pending | ["new faq", "faq baru"] | true | false | 5 |
| 4 | 2 | wifi_issue | WiFi troubleshooting | Penyelesaian masalah WiFi | ["wifi", "internet", "connection"] | true | true | 6 |
| 5 | 2 | password_reset | Reset password | Tetapkan semula kata laluan | ["password", "kata laluan", "forgot"] | true | true | 6 |
| 6 | 3 | loan_application | How to apply for loan | Cara memohon pinjaman | ["loan", "pinjaman", "apply"] | true | true | 4 |
| 7 | 1 | inactive_faq | Inactive FAQ entry | FAQ tidak aktif | ["inactive"] | false | true | 4 |
| 8 | 1 | special_chars | FAQ with special chars | FAQ dengan aksara khas | ["special", "áéíóú", "<script>"] | true | true | 4 |
| 9 | 1 | long_content | Very long FAQ content | Kandungan FAQ panjang | ["long", "extensive"] | true | true | 4 |
| 10 | 2 | cross_dept_test | Cross department test | Ujian rentas jabatan | ["cross", "test"] | true | true | 6 |

### 3.4 Sample Messages for Keyword Matching

| message | expected_keywords | expected_response_key |
|---------|-------------------|----------------------|
| "How do I check my balance?" | ["balance"] | account_balance |
| "Saya nak transfer wang" | ["transfer"] | transfer_money |
| "My wifi is not working" | ["wifi"] | wifi_issue |
| "I forgot my password" | ["forgot"] | password_reset |
| "How to apply for loan?" | ["loan", "apply"] | loan_application |
| "random unrelated message" | [] | (fallback) |
| "BALANCE" (uppercase) | ["balance"] | account_balance |
| "baki saya berapa" | ["baki"] | account_balance |

---

## 4. Unit Test Cases

### 4.1 DepartmentResponsePolicy Tests

**File:** `tests/Unit/Policies/DepartmentResponsePolicyTest.php`

#### 4.1.1 View Any Permission

```
Test ID: POL-VIEW-001
Test Name: test_agent_can_view_any_returns_true
Role: agent
Setup: Create agent user
Action: Call $policy->viewAny($agent)
Expected: Returns true
Rationale: Agents should be able to list FAQ entries (filtered by department in controller)
```

```
Test ID: POL-VIEW-002
Test Name: test_manager_can_view_any_returns_true
Role: manager
Setup: Create manager user
Action: Call $policy->viewAny($manager)
Expected: Returns true
```

```
Test ID: POL-VIEW-003
Test Name: test_admin_can_view_any_returns_true
Role: admin
Setup: Create admin user
Action: Call $policy->viewAny($admin)
Expected: Returns true
```

```
Test ID: POL-VIEW-004
Test Name: test_customer_cannot_view_any_returns_false
Role: customer
Setup: Create customer user
Action: Call $policy->viewAny($customer)
Expected: Returns false
```

#### 4.1.2 View Single Entry Permission

```
Test ID: POL-VIEW-005
Test Name: test_agent_can_view_own_department_faq
Role: agent (dept 1)
Setup: Create agent in dept 1, create response in dept 1 owned by same agent
Action: Call $policy->view($agent, $response)
Expected: Returns true
```

```
Test ID: POL-VIEW-006
Test Name: test_agent_cannot_view_other_department_faq
Role: agent (dept 1)
Setup: Create agent in dept 1, create response in dept 2
Action: Call $policy->view($agent, $response)
Expected: Returns false
```

```
Test ID: POL-VIEW-007
Test Name: test_agent_cannot_view_other_agents_faq_in_same_department
Role: agent (dept 1, user id 4)
Setup: Create agent in dept 1, create response in dept 1 owned by different agent (id 5)
Action: Call $policy->view($agent, $response)
Expected: Returns false
Rationale: Agent can only view own entries per policy line 36: $response->created_by === $user->id
```

```
Test ID: POL-VIEW-008
Test Name: test_manager_can_view_own_department_faq
Role: manager (dept 1)
Setup: Create manager in dept 1, create response in dept 1
Action: Call $policy->view($manager, $response)
Expected: Returns true
```

```
Test ID: POL-VIEW-009
Test Name: test_manager_cannot_view_other_department_faq
Role: manager (dept 1)
Setup: Create manager in dept 1, create response in dept 2
Action: Call $policy->view($manager, $response)
Expected: Returns false
```

```
Test ID: POL-VIEW-010
Test Name: test_admin_can_view_any_department_faq
Role: admin
Setup: Create admin, create response in any department
Action: Call $policy->view($admin, $response)
Expected: Returns true
```

#### 4.1.3 Create Permission

```
Test ID: POL-CREATE-001
Test Name: test_agent_can_create_returns_true
Role: agent
Action: Call $policy->create($agent)
Expected: Returns true
```

```
Test ID: POL-CREATE-002
Test Name: test_manager_can_create_returns_true
Role: manager
Action: Call $policy->create($manager)
Expected: Returns true
```

```
Test ID: POL-CREATE-003
Test Name: test_admin_can_create_returns_true
Role: admin
Action: Call $policy->create($admin)
Expected: Returns true
```

```
Test ID: POL-CREATE-004
Test Name: test_customer_cannot_create_returns_false
Role: customer
Action: Call $policy->create($customer)
Expected: Returns false
```

#### 4.1.4 Update Permission

```
Test ID: POL-UPDATE-001
Test Name: test_agent_can_update_own_faq
Role: agent (user id 4, dept 1)
Setup: Create response in dept 1 with created_by = 4
Action: Call $policy->update($agent, $response)
Expected: Returns true
```

```
Test ID: POL-UPDATE-002
Test Name: test_agent_cannot_update_other_agents_faq
Role: agent (user id 4, dept 1)
Setup: Create response in dept 1 with created_by = 5
Action: Call $policy->update($agent, $response)
Expected: Returns false
```

```
Test ID: POL-UPDATE-003
Test Name: test_agent_cannot_update_faq_in_other_department
Role: agent (user id 4, dept 1)
Setup: Create response in dept 2 with created_by = 4
Action: Call $policy->update($agent, $response)
Expected: Returns false
```

```
Test ID: POL-UPDATE-004
Test Name: test_manager_can_update_own_department_faq
Role: manager (dept 1)
Setup: Create response in dept 1
Action: Call $policy->update($manager, $response)
Expected: Returns true
```

```
Test ID: POL-UPDATE-005
Test Name: test_manager_cannot_update_other_department_faq
Role: manager (dept 1)
Setup: Create response in dept 2
Action: Call $policy->update($manager, $response)
Expected: Returns false
```

```
Test ID: POL-UPDATE-006
Test Name: test_admin_can_update_any_faq
Role: admin
Setup: Create response in any department
Action: Call $policy->update($admin, $response)
Expected: Returns true
```

#### 4.1.5 Delete Permission

```
Test ID: POL-DELETE-001
Test Name: test_agent_cannot_delete_any_faq
Role: agent
Setup: Create agent, create response in agent's department owned by agent
Action: Call $policy->delete($agent, $response)
Expected: Returns false
Rationale: Per policy line 86: Agents cannot delete
```

```
Test ID: POL-DELETE-002
Test Name: test_manager_can_delete_own_department_faq
Role: manager (dept 1)
Setup: Create response in dept 1
Action: Call $policy->delete($manager, $response)
Expected: Returns true
```

```
Test ID: POL-DELETE-003
Test Name: test_manager_cannot_delete_other_department_faq
Role: manager (dept 1)
Setup: Create response in dept 2
Action: Call $policy->delete($manager, $response)
Expected: Returns false
```

```
Test ID: POL-DELETE-004
Test Name: test_admin_can_delete_any_faq
Role: admin
Setup: Create response in any department
Action: Call $policy->delete($admin, $response)
Expected: Returns true
```

#### 4.1.6 Approve Permission

```
Test ID: POL-APPROVE-001
Test Name: test_agent_cannot_approve_any_faq
Role: agent
Setup: Create agent, create pending response in agent's department
Action: Call $policy->approve($agent, $response)
Expected: Returns false
```

```
Test ID: POL-APPROVE-002
Test Name: test_manager_can_approve_own_department_faq
Role: manager (dept 1)
Setup: Create pending response in dept 1
Action: Call $policy->approve($manager, $response)
Expected: Returns true
```

```
Test ID: POL-APPROVE-003
Test Name: test_manager_cannot_approve_other_department_faq
Role: manager (dept 1)
Setup: Create pending response in dept 2
Action: Call $policy->approve($manager, $response)
Expected: Returns false
```

```
Test ID: POL-APPROVE-004
Test Name: test_admin_can_approve_any_faq
Role: admin
Setup: Create pending response in any department
Action: Call $policy->approve($admin, $response)
Expected: Returns true
```

---

### 4.2 DepartmentResponse Model Tests

**File:** `tests/Unit/Models/DepartmentResponseTest.php`

#### 4.2.1 Model Attributes & Casts

```
Test ID: MODEL-001
Test Name: test_model_has_correct_fillable_attributes
Setup: Create DepartmentResponse with all fillable attributes
Expected: All attributes are mass-assignable: department_id, response_key, content_en, content_bm, trigger_keywords, priority, is_active, is_approved, created_by, updated_by
```

```
Test ID: MODEL-002
Test Name: test_trigger_keywords_cast_to_array
Setup: Create response with trigger_keywords = '["keyword1", "keyword2"]'
Expected: $response->trigger_keywords is an array
Assertion: expect($response->trigger_keywords)->toBeArray()
```

```
Test ID: MODEL-003
Test Name: test_priority_cast_to_integer
Setup: Create response with priority = 5
Expected: $response->priority is int
Assertion: expect($response->priority)->toBeInt()
```

```
Test ID: MODEL-004
Test Name: test_is_active_cast_to_boolean
Setup: Create response with is_active = true
Expected: $response->is_active is boolean true
```

```
Test ID: MODEL-005
Test Name: test_is_approved_cast_to_boolean
Setup: Create response with is_approved = false
Expected: $response->is_approved is boolean false
```

#### 4.2.2 Relationships

```
Test ID: MODEL-006
Test Name: test_belongs_to_department
Setup: Create response with department_id = 1
Expected: $response->department returns Department model
Assertion: $response->department->id === 1
```

```
Test ID: MODEL-007
Test Name: test_belongs_to_creator
Setup: Create response with created_by = 4
Expected: $response->creator returns User model
Assertion: $response->creator->id === 4
```

```
Test ID: MODEL-008
Test Name: test_belongs_to_updater
Setup: Create response with updated_by = 2
Expected: $response->updater returns User model
Assertion: $response->updater->id === 2
```

```
Test ID: MODEL-009
Test Name: test_updater_can_be_null
Setup: Create response without setting updated_by
Expected: $response->updater is null
```

#### 4.2.3 Scopes

```
Test ID: MODEL-010
Test Name: test_scope_active_filters_correctly
Setup: Create 3 responses: 2 active, 1 inactive
Action: DepartmentResponse::active()->get()
Expected: Returns only 2 active responses
```

```
Test ID: MODEL-011
Test Name: test_scope_approved_filters_correctly
Setup: Create 3 responses: 2 approved, 1 pending
Action: DepartmentResponse::approved()->get()
Expected: Returns only 2 approved responses
```

```
Test ID: MODEL-012
Test Name: test_scope_by_department_filters_correctly
Setup: Create responses in dept 1 and dept 2
Action: DepartmentResponse::byDepartment(1)->get()
Expected: Returns only responses where department_id = 1
```

```
Test ID: MODEL-013
Test Name: test_scope_ordered_sorts_by_priority_desc_then_key_asc
Setup: Create 3 responses: priority 10 (key "z"), priority 5 (key "a"), priority 10 (key "a")
Action: DepartmentResponse::ordered()->get()
Expected: Order is: priority 10/key "a", priority 10/key "z", priority 5/key "a"
```

```
Test ID: MODEL-014
Test Name: test_combined_scopes_work_together
Setup: Create 5 responses across 2 departments with mixed active/approved states
Action: DepartmentResponse::active()->approved()->byDepartment(1)->ordered()->get()
Expected: Returns only active, approved responses in dept 1, sorted correctly
```

#### 4.2.4 getContent Helper

```
Test ID: MODEL-015
Test Name: test_get_content_returns_english_by_default
Setup: Create response with content_en = "English content", content_bm = "Malay content"
Action: $response->getContent()
Expected: Returns "English content"
```

```
Test ID: MODEL-016
Test Name: test_get_content_returns_english_when_en_specified
Setup: Same as above
Action: $response->getContent('en')
Expected: Returns "English content"
```

```
Test ID: MODEL-017
Test Name: test_get_content_returns_malay_when_bm_specified
Setup: Same as above
Action: $response->getContent('bm')
Expected: Returns "Malay content"
```

```
Test ID: MODEL-018
Test Name: test_get_content_returns_malay_when_ms_specified
Setup: Same as above
Action: $response->getContent('ms')
Expected: Returns "Malay content"
Rationale: 'ms' is alternate code for Bahasa Malaysia
```

```
Test ID: MODEL-019
Test Name: test_get_content_returns_english_for_unknown_language
Setup: Same as above
Action: $response->getContent('fr')
Expected: Returns "English content"
Rationale: Default case in match statement
```

---

### 4.3 DepartmentResponseService Tests

**File:** `tests/Unit/Services/DepartmentResponseServiceTest.php`

#### 4.3.1 getResponse Method

```
Test ID: SVC-001
Test Name: test_get_response_matches_keywords_case_insensitive
Setup: Create approved response with keywords ["balance", "baki"]
Action: $service->getResponse(1, 'How do I check my BALANCE?')
Expected: Returns the content_en of the matched response
Assertion: Response matches the expected FAQ content
```

```
Test ID: SVC-002
Test Name: test_get_response_returns_highest_priority_match
Setup: Create 2 approved responses in dept 1:
  - Response A: keywords ["account"], priority 5
  - Response B: keywords ["account"], priority 10
Action: $service->getResponse(1, 'account issue')
Expected: Returns Response B content (higher priority)
```

```
Test ID: SVC-003
Test Name: test_get_response_returns_first_key_when_equal_priority
Setup: Create 2 approved responses in dept 1 with same priority, different keywords both matching
Action: $service->getResponse(1, 'message containing both keywords')
Expected: Returns the response with alphabetically earlier response_key
```

```
Test ID: SVC-004
Test Name: test_get_response_returns_english_content_by_default
Setup: Create approved response with content_en and content_bm
Action: $service->getResponse(1, 'matching keyword', 'en')
Expected: Returns content_en
```

```
Test ID: SVC-005
Test Name: test_get_response_returns_malay_content_when_specified
Setup: Create approved response with content_en and content_bm
Action: $service->getResponse(1, 'matching keyword', 'bm')
Expected: Returns content_bm
```

```
Test ID: SVC-006
Test Name: test_get_response_returns_fallback_when_no_keywords_match
Setup: Create approved response with keywords ["specific"], send unrelated message
Action: $service->getResponse(1, 'completely unrelated message')
Expected: Returns fallback response (English)
Assertion: expect($result)->toBe('Thank you for your message. An agent will assist you shortly. Please stay on the line.')
```

```
Test ID: SVC-007
Test Name: test_get_response_skips_inactive_responses
Setup: Create response with is_active = false and matching keywords
Action: $service->getResponse(1, 'matching keyword')
Expected: Returns fallback (inactive responses excluded)
```

```
Test ID: SVC-008
Test Name: test_get_response_skips_unapproved_responses
Setup: Create response with is_approved = false and matching keywords
Action: $service->getResponse(1, 'matching keyword')
Expected: Returns fallback (unapproved responses excluded)
```

```
Test ID: SVC-009
Test Name: test_get_response_filters_by_department
Setup: Create approved response with keywords ["test"] in dept 2 only
Action: $service->getResponse(1, 'test message')
Expected: Returns fallback (no match in dept 1)
```

```
Test ID: SVC-010
Test Name: test_get_response_handles_empty_message
Setup: Create approved response with keywords ["keyword"]
Action: $service->getResponse(1, '')
Expected: Returns fallback
```

```
Test ID: SVC-011
Test Name: test_get_response_handles_multibyte_characters
Setup: Create approved response with keywords ["áéíóú", "penjelasan"]
Action: $service->getResponse(1, 'Saya perlukan penjelasan tentang áéíóú')
Expected: Returns matched response content
```

```
Test ID: SVC-012
Test Name: test_get_response_handles_department_with_no_responses
Setup: Use department ID that has no responses
Action: $service->getResponse(999, 'any message')
Expected: Returns fallback response
```

#### 4.3.2 matchKeywords Method

```
Test ID: SVC-013
Test Name: test_match_keywords_returns_true_when_keyword_found
Setup: None
Action: $service->matchKeywords('check my balance', ['balance'])
Expected: Returns true
```

```
Test ID: SVC-014
Test Name: test_match_keywords_returns_false_when_no_match
Setup: None
Action: $service->matchKeywords('hello world', ['balance', 'transfer'])
Expected: Returns false
```

```
Test ID: SVC-015
Test Name: test_match_keywords_handles_empty_keywords_array
Setup: None
Action: $service->matchKeywords('any message', [])
Expected: Returns false
```

```
Test ID: SVC-016
Test Name: test_match_keywords_is_case_insensitive
Setup: None
Action: $service->matchKeywords('CHECK MY BALANCE', ['balance'])
Expected: Returns true
```

```
Test ID: SVC-017
Test Name: test_match_keywords_handles_whitespace_in_keyword
Setup: None
Action: $service->matchKeywords('check my balance', ['  balance  '])
Expected: Returns true (trim applied)
```

```
Test ID: SVC-018
Test Name: test_match_keywords_skips_empty_string_keyword
Setup: None
Action: $service->matchKeywords('any message', ['', 'balance'])
Expected: Returns true (empty string skipped, 'balance' found)
```

```
Test ID: SVC-019
Test Name: test_match_keywords_handles_substring_match
Setup: None
Action: $service->matchKeywords('transfermoney', ['transfer'])
Expected: Returns true (str_contains used, not exact word match)
```

```
Test ID: SVC-020
Test Name: test_match_keywords_handles_numeric_keyword
Setup: None
Action: $service->matchKeywords('code 12345', ['12345'])
Expected: Returns true (keyword cast to string)
```

#### 4.3.3 getFallbackResponse Method

```
Test ID: SVC-021
Test Name: test_get_fallback_response_english_by_default
Setup: None
Action: $service->getFallbackResponse(1)
Expected: Returns 'Thank you for your message. An agent will assist you shortly. Please stay on the line.'
```

```
Test ID: SVC-022
Test Name: test_get_fallback_response_english_explicit
Setup: None
Action: $service->getFallbackResponse(1, 'en')
Expected: Returns English fallback
```

```
Test ID: SVC-023
Test Name: test_get_fallback_response_malay_bm
Setup: None
Action: $service->getFallbackResponse(1, 'bm')
Expected: Returns 'Terima kasih atas mesej anda. Seorang ejen akan membantu anda tidak lama lagi. Sila kekal di talian.'
```

```
Test ID: SVC-024
Test Name: test_get_fallback_response_malay_ms
Setup: None
Action: $service->getFallbackResponse(1, 'ms')
Expected: Returns Malay fallback
```

```
Test ID: SVC-025
Test Name: test_get_fallback_response_english_for_unknown_language
Setup: None
Action: $service->getFallbackResponse(1, 'fr')
Expected: Returns English fallback (default)
```

#### 4.3.4 getDepartmentResponses Method

```
Test ID: SVC-026
Test Name: test_get_department_responses_returns_only_active_approved
Setup: Create 4 responses in dept 1: active+approved, active+pending, inactive+approved, inactive+pending
Action: $service->getDepartmentResponses(1)
Expected: Returns only 1 response (active + approved)
```

```
Test ID: SVC-027
Test Name: test_get_department_responses_filters_by_department
Setup: Create responses in dept 1 and dept 2
Action: $service->getDepartmentResponses(1)
Expected: Returns only responses where department_id = 1
```

```
Test ID: SVC-028
Test Name: test_get_department_responses_returns_ordered_results
Setup: Create 3 responses with different priorities and keys
Action: $service->getDepartmentResponses(1)
Expected: Results ordered by priority DESC, then response_key ASC
```

#### 4.3.5 getAllDepartmentResponses Method

```
Test ID: SVC-029
Test Name: test_get_all_department_responses_includes_inactive
Setup: Create response with is_active = false
Action: $service->getAllDepartmentResponses(1)
Expected: Returns all responses regardless of active/approved status
```

```
Test ID: SVC-030
Test Name: test_get_all_department_responses_includes_unapproved
Setup: Create response with is_approved = false
Action: $service->getAllDepartmentResponses(1)
Expected: Returns all responses regardless of approved status
```

```
Test ID: SVC-031
Test Name: test_get_all_department_responses_filters_by_department
Setup: Create responses in dept 1 and dept 2
Action: $service->getAllDepartmentResponses(1)
Expected: Returns only dept 1 responses
```

---

## 5. Integration Test Cases

### 5.1 Agent FAQ Endpoints

**File:** `tests/Feature/Agent/AgentFaqTest.php`

#### 5.1.1 GET /agent/faq

```
Test ID: INT-AGT-001
Test Name: test_agent_can_list_own_department_faq
Setup: Login as agent (dept 1), create approved responses in dept 1
Action: GET /agent/faq
Expected:
  - Status: 200
  - Response contains only dept 1 responses
  - Response includes content_en, content_bm, trigger_keywords
  - Each entry has is_approved and is_active status
```

```
Test ID: INT-AGT-002
Test Name: test_agent_cannot_see_other_department_faq
Setup: Login as agent (dept 1), create responses in dept 2
Action: GET /agent/faq
Expected:
  - Status: 200
  - Response does NOT contain dept 2 responses
  - Empty array or only dept 1 entries
```

```
Test ID: INT-AGT-003
Test Name: test_agent_can_see_own_pending_entries
Setup: Login as agent (dept 1), agent creates FAQ (is_approved = false)
Action: GET /agent/faq
Expected:
  - Status: 200
  - Agent's own pending entries are visible to them
```

```
Test ID: INT-AGT-004
Test Name: test_unauthenticated_user_cannot_access_agent_faq
Setup: No authentication
Action: GET /agent/faq
Expected:
  - Status: 302 (redirect to login) or 401 (API)
```

#### 5.1.2 POST /agent/faq

```
Test ID: INT-AGT-005
Test Name: test_agent_can_create_faq_in_own_department
Setup: Login as agent (dept 1)
Action: POST /agent/faq with valid data (department_id: 1, response_key: "new_faq", content_en: "Test", content_bm: "Ujian", trigger_keywords: ["test"])
Expected:
  - Status: 201
  - Response contains created entry
  - is_approved = false (pending by default)
  - is_active = true
  - created_by = agent's user ID
  - Database has new record
```

```
Test ID: INT-AGT-006
Test Name: test_agent_cannot_create_faq_in_other_department
Setup: Login as agent (dept 1)
Action: POST /agent/faq with department_id: 2
Expected:
  - Status: 403 Forbidden
  - No record created in database
```

```
Test ID: INT-AGT-007
Test Name: test_agent_create_faq_validates_required_fields
Setup: Login as agent (dept 1)
Action: POST /agent/faq with missing response_key
Expected:
  - Status: 422
  - Validation error for response_key
```

```
Test ID: INT-AGT-008
Test Name: test_agent_create_faq_validates_unique_response_key_per_department
Setup: Login as agent (dept 1), existing response with key "account_balance" in dept 1
Action: POST /agent/faq with response_key: "account_balance", department_id: 1
Expected:
  - Status: 422
  - Validation error: response_key already taken
```

```
Test ID: INT-AGT-009
Test Name: test_agent_create_faq_accepts_same_key_in_different_department
Setup: Login as admin, existing response with key "account_balance" in dept 1
Action: POST /agent/faq with response_key: "account_balance", department_id: 2
Expected:
  - Status: 201
  - Record created (unique constraint is on department_id + response_key combo)
```

```
Test ID: INT-AGT-010
Test Name: test_agent_create_faq_sets_created_by_to_agent_id
Setup: Login as agent (user id 4)
Action: POST /agent/faq with valid data
Expected:
  - created_by field = 4
  - updated_by field = null
```

#### 5.1.3 PUT /agent/faq/{id}

```
Test ID: INT-AGT-011
Test Name: test_agent_can_update_own_faq
Setup: Login as agent (user id 4), create response owned by agent
Action: PUT /agent/faq/{id} with updated content_en
Expected:
  - Status: 200
  - Response contains updated content
  - updated_by = agent's user ID
  - Timestamps updated
```

```
Test ID: INT-AGT-012
Test Name: test_agent_cannot_update_other_agents_faq
Setup: Login as agent (user id 4), response owned by user id 5 in same dept
Action: PUT /agent/faq/{id}
Expected:
  - Status: 403 Forbidden
  - No changes in database
```

```
Test ID: INT-AGT-013
Test Name: test_agent_cannot_update_faq_in_other_department
Setup: Login as agent (user id 4, dept 1), response in dept 2
Action: PUT /agent/faq/{id}
Expected:
  - Status: 403 Forbidden
```

```
Test ID: INT-AGT-014
Test Name: test_agent_update_faq_validates_input
Setup: Login as agent, own response
Action: PUT /agent/faq/{id} with empty content_en
Expected:
  - Status: 422
  - Validation error
```

```
Test ID: INT-AGT-015
Test Name: test_agent_can_update_own_pending_faq
Setup: Login as agent, own response with is_approved = false
Action: PUT /agent/faq/{id} with corrected content
Expected:
  - Status: 200
  - Content updated, still pending approval
```

#### 5.1.4 DELETE /agent/faq/{id}

```
Test ID: INT-AGT-016
Test Name: test_agent_cannot_delete_own_faq
Setup: Login as agent, own response
Action: DELETE /agent/faq/{id}
Expected:
  - Status: 403 Forbidden
  - Record still exists in database
```

```
Test ID: INT-AGT-017
Test Name: test_agent_cannot_delete_any_faq
Setup: Login as agent, any response in their department
Action: DELETE /agent/faq/{id}
Expected:
  - Status: 403 Forbidden
```

---

### 5.2 Manager FAQ Endpoints

**File:** `tests/Feature/Manager/ManagerFaqTest.php`

#### 5.2.1 GET /manager/faq

```
Test ID: INT-MGR-001
Test Name: test_manager_can_list_own_department_faq
Setup: Login as manager (dept 1), create responses in dept 1
Action: GET /manager/faq
Expected:
  - Status: 200
  - Returns all responses in dept 1 (active/inactive, approved/pending)
```

```
Test ID: INT-MGR-002
Test Name: test_manager_cannot_see_other_department_faq
Setup: Login as manager (dept 1), create responses in dept 2
Action: GET /manager/faq
Expected:
  - Status: 200
  - Does NOT include dept 2 responses
```

```
Test ID: INT-MGR-003
Test Name: test_manager_can_see_pending_approvals
Setup: Login as manager (dept 1), agent creates FAQ in dept 1 (pending)
Action: GET /manager/faq
Expected:
  - Status: 200
  - Pending entries visible with is_approved = false
```

#### 5.2.2 POST /manager/faq

```
Test ID: INT-MGR-004
Test Name: test_manager_can_create_faq_in_own_department
Setup: Login as manager (dept 1)
Action: POST /manager/faq with valid data (department_id: 1)
Expected:
  - Status: 201
  - Record created with created_by = manager's ID
  - is_approved = false by default
```

```
Test ID: INT-MGR-005
Test Name: test_manager_can_create_approved_faq_directly
Setup: Login as manager (dept 1)
Action: POST /manager/faq with is_approved: true
Expected:
  - Status: 201
  - Record created with is_approved = true
  - This allows managers to bypass approval for their own entries
```

```
Test ID: INT-MGR-006
Test Name: test_manager_cannot_create_faq_in_other_department
Setup: Login as manager (dept 1)
Action: POST /manager/faq with department_id: 2
Expected:
  - Status: 403 Forbidden
```

#### 5.2.3 PUT /manager/faq/{id}

```
Test ID: INT-MGR-007
Test Name: test_manager_can_update_any_entry_in_own_department
Setup: Login as manager (dept 1), agent-created response in dept 1
Action: PUT /manager/faq/{id} with corrected content
Expected:
  - Status: 200
  - Entry updated regardless of who created it
```

```
Test ID: INT-MGR-008
Test Name: test_manager_cannot_update_entry_in_other_department
Setup: Login as manager (dept 1), response in dept 2
Action: PUT /manager/faq/{id}
Expected:
  - Status: 403 Forbidden
```

```
Test ID: INT-MGR-009
Test Name: test_manager_can_approve_entry_via_update
Setup: Login as manager (dept 1), pending response in dept 1
Action: PUT /manager/faq/{id} with is_approved: true
Expected:
  - Status: 200
  - is_approved = true
  - updated_by = manager's ID
```

#### 5.2.4 DELETE /manager/faq/{id}

```
Test ID: INT-MGR-010
Test Name: test_manager_can_delete_entry_in_own_department
Setup: Login as manager (dept 1), response in dept 1
Action: DELETE /manager/faq/{id}
Expected:
  - Status: 200 or 204
  - Record removed from database (or soft deleted if implemented)
```

```
Test ID: INT-MGR-011
Test Name: test_manager_cannot_delete_entry_in_other_department
Setup: Login as manager (dept 1), response in dept 2
Action: DELETE /manager/faq/{id}
Expected:
  - Status: 403 Forbidden
  - Record still exists
```

```
Test ID: INT-MGR-012
Test Name: test_manager_can_delete_agent_created_entry
Setup: Login as manager (dept 1), response created by agent in dept 1
Action: DELETE /manager/faq/{id}
Expected:
  - Status: 200 or 204
  - Record deleted
```

#### 5.2.5 POST /manager/faq/{id}/approve

```
Test ID: INT-MGR-013
Test Name: test_manager_can_approve_pending_faq_in_own_department
Setup: Login as manager (dept 1), pending response (is_approved=false) in dept 1
Action: POST /manager/faq/{id}/approve
Expected:
  - Status: 200
  - is_approved = true
  - updated_by = manager's ID
  - Response includes updated entry
```

```
Test ID: INT-MGR-014
Test Name: test_manager_cannot_approve_faq_in_other_department
Setup: Login as manager (dept 1), pending response in dept 2
Action: POST /manager/faq/{id}/approve
Expected:
  - Status: 403 Forbidden
  - is_approved remains false
```

```
Test ID: INT-MGR-015
Test Name: test_approving_already_approved_entry_is_noop_or_422
Setup: Login as manager (dept 1), already approved response
Action: POST /manager/faq/{id}/approve
Expected:
  - Option A: Status 200 (idempotent, no change)
  - Option B: Status 422 with error "Already approved"
  - Choose based on API design decision
```

```
Test ID: INT-MGR-016
Test Name: test_approve_nonexistent_entry_returns_404
Setup: Login as manager
Action: POST /manager/faq/99999/approve
Expected:
  - Status: 404
```

#### 5.2.6 POST /manager/faq/{id}/reject

```
Test ID: INT-MGR-017
Test Name: test_manager_can_reject_pending_faq_in_own_department
Setup: Login as manager (dept 1), pending response in dept 1
Action: POST /manager/faq/{id}/reject
Expected:
  - Status: 200
  - Response indicates rejection (could set is_active = false or delete)
  - Database updated accordingly
```

```
Test ID: INT-MGR-018
Test Name: test_manager_cannot_reject_faq_in_other_department
Setup: Login as manager (dept 1), pending response in dept 2
Action: POST /manager/faq/{id}/reject
Expected:
  - Status: 403 Forbidden
```

---

### 5.3 Cross-Cutting Integration Tests

**File:** `tests/Feature/CrossCutting/CrossCuttingTest.php`

```
Test ID: INT-XC-001
Test Name: test_department_cascade_delete_removes_responses
Setup: Create department with responses
Action: Delete department (via admin)
Expected:
  - All department_responses with that department_id are deleted
  - Verify: cascadeOnDelete in migration
```

```
Test ID: INT-XC-002
Test Name: test_user_cascade_delete_handles_responses
Setup: Create user who created responses
Action: Delete user (soft delete or force delete)
Expected:
  - Responses with created_by = deleted user still exist (cascadeOnDelete in migration)
  - OR: Verify behavior matches business requirements
  Note: Migration has cascadeOnDelete on created_by foreign key
```

```
Test ID: INT-XC-003
Test Name: test_response_key_uniqueness_per_department
Setup: Create response with key "test" in dept 1
Action: Create another response with key "test" in dept 1
Expected:
  - Status: 422 or 500 (database constraint violation)
  - Only one "test" key per department
```

```
Test ID: INT-XC-004
Test Name: test_response_key_can_duplicate_across_departments
Setup: Create response with key "test" in dept 1
Action: Create response with key "test" in dept 2
Expected:
  - Status: 201
  - Both records exist
```

---

## 6. End-to-End Test Scenarios

### 6.1 Scenario 1: Agent Adds FAQ

**File:** `tests/E2E/AgentAddsFaq.spec.js`

```
Scenario ID: E2E-001
Scenario Name: Agent Creates FAQ Entry for Approval
Priority: Critical
Preconditions: User logged in as agent in Department 1

Steps:
  1. Login as agent.ahmad@putrakop.test
  2. Navigate to FAQ management page (/agent/faq)
  3. Verify page loads with existing FAQ entries for own department
  4. Click "Add New FAQ" button
  5. Fill in form fields:
     - response_key: "new_account_inquiry"
     - content_en: "For account inquiries, please call 1-800-PUTRA or visit your nearest branch."
     - content_bm: "Untuk pertanyaan akaun, sila hubungi 1-800-PUTRA atau lawati cawangan berhampiran anda."
     - trigger_keywords: ["account inquiry", "pertanyaan akaun"]
     - priority: 5
  6. Click "Submit for Approval"
  7. Verify success notification appears
  8. Verify new entry appears in FAQ list with "Pending" status badge
  9. Verify entry is visible only to this agent (not yet approved)

Expected Results:
  - Form submission succeeds (201)
  - New entry has is_approved = false
  - Entry visible in agent's own list
  - Status badge shows "Pending" (yellow/warning color)
  - Database record has correct created_by = agent's ID

Assertions:
  - expect(page.locator('.faq-entry')).toContainText('new_account_inquiry')
  - expect(page.locator('.status-badge')).toContainText('Pending')
```

### 6.2 Scenario 2: Manager Approves FAQ

**File:** `tests/E2E/ManagerApprovesFaq.spec.js`

```
Scenario ID: E2E-002
Scenario Name: Manager Reviews and Approves Pending FAQ
Priority: Critical
Preconditions: Pending FAQ entry exists in manager's department

Steps:
  1. Login as manager.account@putrakop.test (dept 1)
  2. Navigate to FAQ management page (/manager/faq)
  3. Verify pending approval count badge shows "1" (or more)
  4. Filter by "Pending" status or identify pending entry
  5. Click on pending FAQ entry to view details
  6. Verify content displays in both EN and BM
  7. Review trigger keywords
  8. Click "Approve" button
  9. Confirm approval in confirmation dialog (if any)
  10. Verify status changes from "Pending" to "Approved"
  11. Verify entry is now available as AI fallback response

Expected Results:
  - Pending count decremented
  - Status badge changes from yellow "Pending" to green "Approved"
  - Entry included in active+approved scope
  - DepartmentResponseService.getResponse() now returns this response for matching keywords

Assertions:
  - expect(response.is_approved).toBe(true)
  - expect(response.updated_by).toBe(manager.id)
  - Database query: DepartmentResponse::active()->approved()->where('id', entryId)->exists() === true
```

### 6.3 Scenario 3: AI Fallback Uses FAQ

**File:** `tests/E2E/AiFallbackUsesFaq.spec.js`

```
Scenario ID: E2E-003
Scenario Name: AI Service Falls Back to FAQ When API Limit Hit
Priority: High
Preconditions: Approved FAQ entries exist, Gemini API mocked to return rate limit error

Steps:
  1. Mock Gemini API to return HTTP 429 (rate limit exceeded)
  2. Seed approved FAQ in department 1 with keywords ["balance", "baki"]
  3. Send customer message "How do I check my balance?" to department 1
  4. Verify system does NOT call Gemini API (or handles error)
  5. Verify system falls back to DepartmentResponseService
  6. Verify FAQ response is returned: "How to check your balance..."
  7. Send another message "Saya nak tahu baki akaun"
  8. Verify BM response returned (if language = 'bm')
  9. Send message with no keyword match
  10. Verify generic fallback response returned

Expected Results:
  - When API returns 429: system uses FAQ matching
  - Keyword "balance" matches FAQ → returns FAQ content
  - Keyword "baki" matches FAQ → returns BM content
  - No keyword match → returns generic "Thank you for your message..."
  - API is NOT called again after rate limit (circuit breaker)

Assertions:
  - Mock API called count === 0 (or 1 before circuit opens)
  - FAQ response content matches expected
  - Language-appropriate content returned
```

### 6.4 Scenario 4: Permission Boundary Enforcement

**File:** `tests/E2E/PermissionBoundary.spec.js`

```
Scenario ID: E2E-004
Scenario Name: Cross-Department Access Blocked
Priority: Critical
Preconditions: Agent in dept 1, FAQ entries in dept 2

Steps:
  1. Login as agent.ahmad@putrakop.test (dept 1)
  2. Attempt to access FAQ entry from dept 2 via direct URL manipulation:
     - GET /agent/faq/5 (where ID 5 belongs to dept 2)
  3. Verify 403 Forbidden response
  4. Attempt to update entry via PUT /agent/faq/5
  5. Verify 403 Forbidden response
  6. Attempt to delete entry via DELETE /agent/faq/5
  7. Verify 403 Forbidden response
  8. Attempt to approve entry via POST /manager/faq/5/approve (as agent)
  9. Verify 403 Forbidden response (agent cannot approve)

Expected Results:
  - All cross-department actions blocked with 403
  - No data leakage in error responses
  - No database modifications occurred

Assertions:
  - All responses: status === 403
  - Database unchanged: entry still belongs to dept 2
  - Error message: "Unauthorized" or "Forbidden"
```

### 6.5 Scenario 5: Full Approval Workflow

**File:** `tests/E2E/FullApprovalWorkflow.spec.js`

```
Scenario ID: E2E-005
Scenario Name: Complete FAQ Lifecycle — Create → Edit → Approve → Use
Priority: High
Preconditions: Agent and Manager in same department

Steps:
  1. Agent logs in, creates new FAQ (pending)
  2. Agent edits the FAQ to fix a typo (still pending)
  3. Manager logs in, views pending FAQ
  4. Manager edits FAQ to improve wording
  5. Manager approves FAQ
  6. Customer sends message matching FAQ keywords
  7. System returns approved FAQ response

Expected Results:
  - Each step updates updated_by and timestamps
  - Final FAQ content reflects manager's edits
  - AI fallback returns final approved content
  - Audit trail: created_by = agent, updated_by = manager

Assertions:
  - created_by === agent.id
  - updated_by === manager.id
  - content_en === manager's edited version
  - is_approved === true
  - Service returns final content for matching keywords
```

---

## 7. Edge Case Tests

### 7.1 Empty Department

```
Test ID: EDGE-001
Test Name: test_empty_department_returns_fallback
Setup: Create department with no FAQ entries
Action: DepartmentResponseService::getResponse(deptId, 'any message')
Expected: Returns fallback response
```

```
Test ID: EDGE-002
Test Name: test_agent_in_empty_department_sees_empty_list
Setup: Agent in department with no FAQ
Action: GET /agent/faq
Expected: Returns empty array []
```

### 7.2 Special Characters

```
Test ID: EDGE-003
Test Name: test_faq_with_html_tags_in_content
Setup: Create FAQ with content_en = '<script>alert("xss")</script>'
Action: Store and retrieve FAQ
Expected: Content stored as-is (plain text), no XSS execution
Note: Verify frontend escapes HTML when displaying
```

```
Test ID: EDGE-004
Test Name: test_faq_with_unicode_characters
Setup: Create FAQ with content_en = 'Café, naïve, résumé, 日本語, العربية'
Action: Store and retrieve FAQ
Expected: Content preserved correctly, UTF-8 encoding maintained
```

```
Test ID: EDGE-005
Test Name: test_faq_with_emoji_in_content
Setup: Create FAQ with content_en = 'Great! 🎉 Thank you! 😊'
Action: Store and retrieve FAQ
Expected: Emoji preserved correctly
```

```
Test ID: EDGE-006
Test Name: test_faq_with_newlines_and_whitespace
Setup: Create FAQ with content_en = "Line 1\nLine 2\n\nLine 3"
Action: Store and retrieve FAQ
Expected: Newlines preserved in storage, displayed correctly
```

### 7.3 Long Content

```
Test ID: EDGE-007
Test Name: test_faq_with_maximum_content_length
Setup: Create FAQ with content_en = 5000 characters, content_bm = 5000 characters
Action: Store and retrieve FAQ
Expected: Content stored correctly, no truncation
Note: Verify database TEXT column handles this
```

```
Test ID: EDGE-008
Test Name: test_faq_with_very_long_keywords_array
Setup: Create FAQ with 50 trigger keywords
Action: Store and retrieve FAQ
Expected: JSON column stores all keywords, matching works correctly
```

```
Test ID: EDGE-009
Test Name: test_faq_with_very_long_keyword_string
Setup: Create FAQ with a single keyword of 200 characters
Action: matchKeywords() with this keyword
Expected: Matching works correctly
```

### 7.4 Concurrent Operations

```
Test ID: EDGE-010
Test Name: test_concurrent_approval_requests
Setup: Two managers try to approve the same FAQ simultaneously
Action: Both POST /manager/faq/{id}/approve at the same time
Expected:
  - One succeeds (200), one may get 422 or 200 (idempotent)
  - No database corruption
  - is_approved = true after both complete
```

```
Test ID: EDGE-011
Test Name: test_agent_edits_while_manager_reviews
Setup: Agent starts editing FAQ, manager simultaneously tries to approve
Action: Concurrent PUT and POST approve
Expected:
  - Both operations complete without error
  - Final state is consistent
  - Either: approved with agent's edits, or approved with stale data (document behavior)
```

### 7.5 Cascade and Referential Integrity

```
Test ID: EDGE-012
Test Name: test_department_deletion_removes_responses
Setup: Department with FAQ entries
Action: Admin deletes the department
Expected:
  - All responses with that department_id are deleted (cascadeOnDelete)
  - No orphaned records
```

```
Test ID: EDGE-013
Test Name: test_user_deletion_with_created_responses
Setup: Agent who created FAQ entries
Action: Admin soft-deletes the agent
Expected:
  - Responses remain (created_by foreign key has cascadeOnDelete)
  - Responses show original creator info
  - OR: Verify business rule for handling deleted creator references
```

### 7.6 Role Changes

```
Test ID: EDGE-014
Test Name: test_agent_promoted_to_manager_sees_same_department_faq
Setup: Agent in dept 1 has created FAQ entries
Action: Change agent's role to manager (same dept 1)
Expected:
  - Can view all dept 1 FAQ (not just own)
  - Can approve/reject FAQ
  - Own entries still editable
```

```
Test ID: EDGE-015
Test Name: test_manager_demoted_to_agent_loses_approve_permission
Setup: Manager in dept 1 has approved FAQ entries
Action: Change manager's role to agent (same dept 1)
Expected:
  - Cannot approve/reject FAQ
  - Can view only own entries (not all dept entries)
  - Can edit only own entries
```

### 7.7 Keyword Edge Cases

```
Test ID: EDGE-016
Test Name: test_null_trigger_keywords_handled
Setup: Create FAQ with trigger_keywords = null
Action: DepartmentResponseService::matchKeywords('message', null)
Expected: Returns false (treated as empty)
```

```
Test ID: EDGE-017
Test Name: test_empty_string_keyword_ignored
Setup: Create FAQ with trigger_keywords = [""]
Action: matchKeywords('any message', [''])
Expected: Returns false (empty string skipped)
```

```
Test ID: EDGE-018
Test Name: test_whitespace_only_keyword_ignored
Setup: Create FAQ with trigger_keywords = ["   "]
Action: matchKeywords('any message', ['   '])
Expected: Returns false (trimmed to empty, skipped)
```

### 7.8 Response Key Uniqueness

```
Test ID: EDGE-019
Test Name: test_duplicate_response_key_in_same_department_rejected
Setup: Existing FAQ with key "test" in dept 1
Action: Create another FAQ with key "test" in dept 1
Expected: Database constraint violation (unique index on department_id + response_key)
```

```
Test ID: EDGE-020
Test Name: test_duplicate_response_key_across_departments_allowed
Setup: Existing FAQ with key "test" in dept 1
Action: Create FAQ with key "test" in dept 2
Expected: Success (unique constraint is composite)
```

---

## 8. Acceptance Criteria

### 8.1 FAQ CRUD Operations

| Criterion | Pass Condition | Fail Condition |
|-----------|---------------|----------------|
| Create FAQ | Entry saved with correct fields, created_by set, is_approved=false by default | Entry missing fields, wrong created_by, or missing default |
| Read FAQ | Correct entries returned per role/department, scopes applied | Wrong entries shown, missing entries, or extra entries |
| Update FAQ | Content updated, updated_by set, timestamps refreshed | Content not saved, updated_by missing, timestamps stale |
| Delete FAQ | Record removed, cascade behavior correct | Record remains, orphaned data, or wrong record deleted |

### 8.2 Permission Enforcement

| Criterion | Pass Condition | Fail Condition |
|-----------|---------------|----------------|
| Agent view restriction | Agent sees only own department FAQ | Agent sees other department FAQ |
| Agent edit restriction | Agent edits only own entries | Agent edits other agent's entries |
| Agent delete block | Agent cannot delete any FAQ | Agent can delete |
| Manager view scope | Manager sees all own department FAQ | Manager sees other department FAQ |
| Manager edit scope | Manager edits any entry in department | Manager cannot edit agent entries |
| Manager approve scope | Manager approves only own department | Manager approves other department |
| Admin full access | Admin manages all departments | Admin restricted to any department |

### 8.3 Approval Workflow

| Criterion | Pass Condition | Fail Condition |
|-----------|---------------|----------------|
| Pending state | New FAQ defaults to is_approved=false | New FAQ defaults to is_approved=true |
| Approve action | is_approved changes to true, updated_by set | is_approved unchanged, wrong updater |
| Reject action | Entry removed or deactivated | Entry remains active and approved |
| Visibility rules | Only approved+active in AI fallback | Pending/inactive entries used in fallback |

### 8.4 AI Fallback Integration

| Criterion | Pass Condition | Fail Condition |
|-----------|---------------|----------------|
| Keyword matching | Matching keywords trigger correct FAQ response | No match when keywords present, wrong response returned |
| Priority ordering | Highest priority FAQ returned when multiple match | Lower priority returned, or random selection |
| Language selection | Correct language content returned based on parameter | Wrong language, or always English |
| Fallback response | Generic response when no keywords match | Empty string or error returned |
| API limit handling | FAQ used when Gemini returns 429 | System crashes or returns empty |

### 8.5 Bilingual Content

| Criterion | Pass Condition | Fail Condition |
|-----------|---------------|----------------|
| EN content storage | English content stored and retrieved correctly | Content corrupted or truncated |
| BM content storage | Malay content stored and retrieved correctly | Content corrupted or truncated |
| Language switching | getContent() returns correct language variant | Wrong variant returned for given language code |
| Default language | Default to English when no language specified | Throws error or returns null |

---

## 9. Defect Severity Classification

### 9.1 Severity Levels

| Level | Definition | Example | SLA |
|-------|-----------|---------|-----|
| **Critical** | System crash, data loss, security breach | Agent can delete other agent's FAQ, XSS vulnerability, approval bypass | Fix within 4 hours |
| **High** | Major feature broken, permission violation | Manager cannot approve FAQ, wrong department FAQ shown, AI fallback returns empty | Fix within 24 hours |
| **Medium** | Feature partially working, UX issue | Incorrect sort order, missing validation, language not switching | Fix within 72 hours |
| **Low** | Cosmetic, minor inconvenience | Wrong badge color, typo in error message, minor alignment issue | Fix in next sprint |

### 9.2 Common Defect Patterns to Watch

| Pattern | Example | Severity |
|---------|---------|----------|
| IDOR (Insecure Direct Object Reference) | Agent accesses FAQ by ID without department check | Critical |
| Missing authorization check | Endpoint doesn't call `$this->authorize()` | Critical |
| Race condition on approval | Two managers approve simultaneously, inconsistent state | High |
| N+1 query in list endpoint | Loading department/creator per FAQ item in loop | Medium |
| Missing validation | No check for empty response_key or content | Medium |
| Stale cache | Approved FAQ cached, but approval revoked | High |

---

## 10. Test Execution Checklist

### 10.1 Pre-Execution

- [ ] Test database created and migrations run
- [ ] Seed data loaded (departments, users, FAQ entries)
- [ ] Authentication working for all test users
- [ ] API routes registered and accessible
- [ ] Test environment variables configured

### 10.2 Unit Test Execution Order

- [ ] 4.1 DepartmentResponsePolicy Tests (POL-*)
- [ ] 4.2 DepartmentResponse Model Tests (MODEL-*)
- [ ] 4.3 DepartmentResponseService Tests (SVC-*)

### 10.3 Integration Test Execution Order

- [ ] 5.1 Agent FAQ Endpoints (INT-AGT-*)
- [ ] 5.2 Manager FAQ Endpoints (INT-MGR-*)
- [ ] 5.3 Cross-Cutting Tests (INT-XC-*)

### 10.4 E2E Test Execution Order

- [ ] 6.1 Agent Adds FAQ (E2E-001)
- [ ] 6.2 Manager Approves FAQ (E2E-002)
- [ ] 6.3 AI Fallback Uses FAQ (E2E-003)
- [ ] 6.4 Permission Boundary (E2E-004)
- [ ] 6.5 Full Approval Workflow (E2E-005)

### 10.5 Edge Case Test Execution

- [ ] 7.1 Empty Department (EDGE-001, EDGE-002)
- [ ] 7.2 Special Characters (EDGE-003 to EDGE-006)
- [ ] 7.3 Long Content (EDGE-007 to EDGE-009)
- [ ] 7.4 Concurrent Operations (EDGE-010, EDGE-011)
- [ ] 7.5 Cascade and Referential Integrity (EDGE-012, EDGE-013)
- [ ] 7.6 Role Changes (EDGE-014, EDGE-015)
- [ ] 7.7 Keyword Edge Cases (EDGE-016 to EDGE-018)
- [ ] 7.8 Response Key Uniqueness (EDGE-019, EDGE-020)

### 10.6 Post-Execution

- [ ] All Critical/High defects documented
- [ ] Test results reported
- [ ] Coverage report generated
- [ ] Regression test set identified for CI/CD

---

## Appendix A: Test Summary Matrix

| Test Category | Total Tests | Critical | High | Medium | Low |
|---------------|-------------|----------|------|--------|-----|
| Policy Unit Tests | 16 | 8 | 6 | 2 | 0 |
| Model Unit Tests | 19 | 5 | 8 | 4 | 2 |
| Service Unit Tests | 31 | 12 | 10 | 7 | 2 |
| Integration Tests | 30 | 15 | 10 | 5 | 0 |
| E2E Scenarios | 5 | 3 | 2 | 0 | 0 |
| Edge Case Tests | 20 | 6 | 6 | 5 | 3 |
| **TOTAL** | **121** | **49** | **42** | **23** | **7** |

## Appendix B: Coverage Targets

| Component | Line Coverage | Branch Coverage |
|-----------|---------------|-----------------|
| DepartmentResponsePolicy | ≥ 95% | ≥ 90% |
| DepartmentResponse Model | ≥ 90% | ≥ 85% |
| DepartmentResponseService | ≥ 95% | ≥ 90% |
| Agent Controllers | ≥ 85% | ≥ 80% |
| Manager Controllers | ≥ 85% | ≥ 80% |

## Appendix C: CI/CD Integration

```yaml
# Recommended CI pipeline stages for FAQ test suite
stages:
  - name: unit-tests
    command: php artisan test --testsuite=Unit --filter=DepartmentResponse
    coverage_threshold: 80
    
  - name: integration-tests
    command: php artisan test --testsuite=Feature --filter=Faq
    coverage_threshold: 75
    
  - name: e2e-tests
    command: npx playwright test tests/E2E/FAQ
    parallel: true
    
  - name: security-scan
    command: php artisan test --testsuite=Feature --filter=Permission
```

---

*Document Version: 1.0*  
*Last Updated: July 8, 2026*  
*Prepared by: Senior QA Architect*  
*Review Status: Ready for Execution*
