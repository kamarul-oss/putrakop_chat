# PutraKop Live Chat System — Comprehensive UI/UX Design Plan

**Version:** 1.0  
**Date:** July 2026  
**Status:** Design Specification Document  
**Audience:** Design team, frontend/backend engineers, product stakeholders

---

## Table of Contents

1. [Design Philosophy & Principles](#1-design-philosophy--principles)
2. [Design System](#2-design-system)
3. [Component Library Plan](#3-component-library-plan)
4. [Layout Architecture](#4-layout-architecture)
5. [Screen-by-Screen Design Specifications](#5-screen-by-screen-design-specifications)
6. [Chat Interface Deep-Dive](#6-chat-interface-deep-dive)
7. [Responsive Strategy](#7-responsive-strategy)
8. [Accessibility Plan](#8-accessibility-plan)
9. [Animation & Micro-Interactions](#9-animation--micro-interactions)
10. [Internationalization (i18n) Strategy](#10-internationalization-i18n-strategy)
11. [Implementation Notes](#11-implementation-notes)

---

## 1. Design Philosophy & Principles

### Core Philosophy
**"Professional warmth meets operational efficiency."**

PutraKop Live Chat serves a Malaysian cooperative organization. The design must convey **institutional trust** (banking/cooperative feel) while maintaining the **approachability** expected of a customer service platform. Think: the reliability of Zendesk combined with the clarity of Intercom, softened with Malaysian warmth.

### Design Principles

| Principle | Description | How It Manifests |
|-----------|-------------|------------------|
| **Trust First** | Every pixel should reinforce credibility | Consistent branding, professional typography, no playful excess |
| **Clarity Over Cleverness** | Users should never wonder what to do | Clear labels, obvious CTAs, generous whitespace |
| **Bilingual Harmony** | BM and EN feel equally native, not translated | Layout accommodates both languages without breaking |
| **Progressive Disclosure** | Show what's needed, reveal complexity on demand | Collapsed sidebars, expandable sections, contextual actions |
| **Operational Speed** | Agents need to respond fast, managers need to see fast | Keyboard shortcuts, minimal clicks, real-time updates |
| **Accessible by Default** | WCAG 2.1 AA minimum, AAA target | Contrast ratios, focus management, semantic structure |

---

## 2. Design System

### 2.1 Color Palette

#### Primary Colors (Corporate Blue)
```
Blue-900:  #1E3A5F  — Darkest blue (headers, primary text on light backgrounds)
Blue-800:  #1E4D8C  — Deep blue (sidebar backgrounds, primary buttons hover)
Blue-700:  #2563EB  — PRIMARY BRAND (buttons, links, active states)
Blue-600:  #3B82F6  — Primary hover state
Blue-500:  #60A5FA  — Lighter primary (interactive elements, icons)
Blue-400:  #93C5FD  — Light blue (borders, dividers, subtle highlights)
Blue-100:  #DBEAFE  — Very light blue (backgrounds, badges)
Blue-50:   #EFF6FF  — Near-white blue (card backgrounds, subtle fills)
```

#### Semantic Colors
```
Success-700: #15803D  — Confirmed actions, online status, delivered/read receipts
Success-500: #22C55E  — Success badges, positive metrics
Success-50:  #F0FDF4  — Success backgrounds

Warning-700: #A16207  — Attention needed, queue warnings
Warning-500: #EAB308  — Warning badges, pending states
Warning-50:  #FEFCE8  — Warning backgrounds

Error-700:   #B91C1C  — Destructive actions, error states
Error-500:   #EF4444  — Error badges, critical alerts
Error-50:    #FEF2F2  — Error backgrounds

Info-700:    #1D4ED8  — Informational messages
Info-500:    #3B82F6  — Info badges
Info-50:     #EFF6FF  — Info backgrounds
```

#### Neutral Colors
```
Gray-950: #0A0A0B  — Primary text
Gray-800: #1F2937  — Secondary text
Gray-600: #4B5563  — Tertiary text, placeholders
Gray-400: #9CA3AF  — Disabled text, subtle borders
Gray-200: #E5E7EB  — Borders, dividers
Gray-100: #F3F4F6  — Secondary backgrounds, hover states
Gray-50:  #F9FAFB  — Page background
White:    #FFFFFF   — Cards, modals, primary backgrounds
```

#### Chat-Specific Colors
```
Customer Bubble:   #FFFFFF (white with gray-200 border)
Agent Bubble:      #2563EB (Blue-700, white text)
AI Bubble:         #F0FDF4 (Success-50, success-700 border)
System Message:    #EFF6FF (Blue-50, gray-600 text)
Typing Indicator:  Gray-400 dots on Gray-100 background
```

#### Color Usage Rules
- **Primary Blue (#2563EB)** is reserved for: agent chat bubbles, primary buttons, active navigation items, links, and focused elements
- **Never use blue for error states** — always use Error-500/700
- **Text contrast minimum:** 4.5:1 for body text, 3:1 for large text (18px+ or 14px bold)
- **All interactive elements** must have a visible focus indicator using Blue-500 or Blue-600 ring

### 2.2 Typography

#### Font Family
```
Primary:    'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif
Monospace:  'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace
```

**Rationale:** Inter is the industry standard for SaaS dashboards (used by Linear, Vercel, etc.). It has excellent readability at small sizes, supports Latin and extended Latin characters for Bahasa Malaysia, and has tabular figures for data displays.

#### Type Scale

| Token | Size | Weight | Line Height | Letter Spacing | Usage |
|-------|------|--------|-------------|----------------|-------|
| `display-lg` | 36px / 2.25rem | 700 | 1.2 | -0.025em | Page hero titles (rare) |
| `display` | 30px / 1.875rem | 700 | 1.3 | -0.025em | Dashboard page titles |
| `heading-1` | 24px / 1.5rem | 600 | 1.35 | -0.02em | Section headers |
| `heading-2` | 20px / 1.25rem | 600 | 1.4 | -0.015em | Card titles, subsections |
| `heading-3` | 16px / 1rem | 600 | 1.5 | -0.01em | Small headings, labels |
| `body-lg` | 16px / 1rem | 400 | 1.6 | 0 | Body text (large) |
| `body` | 14px / 0.875rem | 400 | 1.6 | 0 | Default body text, messages |
| `body-sm` | 13px / 0.8125rem | 400 | 1.55 | 0 | Chat messages (compact) |
| `caption` | 12px / 0.75rem | 400 | 1.5 | 0.01em | Timestamps, metadata, badges |
| `overline` | 11px / 0.6875rem | 600 | 1.4 | 0.05em | Uppercase labels, categories |
| `mono` | 13px / 0.8125rem | 400 | 1.5 | 0 | Code, reference numbers |

#### Typography Rules
- **Maximum 3 font weights** in any single view: 400 (body), 600 (headings), 700 (display only)
- **Base text size:** 14px (body) — this is standard for data-dense SaaS interfaces
- **Line length:** Max 65-75 characters for readability; use `max-width: 65ch` for long-form content
- **Malay text consideration:** Bahasa Malaysia words tend to be 15-20% longer than English equivalents. All containers must accommodate this with flexible widths or wrapping

### 2.3 Spacing System

#### Base Unit: 4px

All spacing values are multiples of 4px to maintain visual rhythm.

| Token | Value | Usage |
|-------|-------|-------|
| `space-0` | 0px | Reset |
| `space-0.5` | 2px | Tight inline spacing |
| `space-1` | 4px | Icon-to-text gap, micro spacing |
| `space-1.5` | 6px | Inline badge padding |
| `space-2` | 8px | Compact element spacing, input padding |
| `space-3` | 12px | Default element padding |
| `space-4` | 16px | Card padding, standard gaps |
| `space-5` | 20px | Medium section spacing |
| `space-6` | 24px | Section padding, card gaps |
| `space-8` | 32px | Large section spacing |
| `space-10` | 40px | Page section dividers |
| `space-12` | 48px | Major section breaks |
| `space-16` | 64px | Page-level vertical spacing |

#### Layout Spacing Rules
- **Page padding:** 24px (desktop) → 16px (tablet) → 16px (mobile)
- **Card internal padding:** 20px (desktop), 16px (mobile)
- **Gap between cards in grid:** 16px
- **Gap between sidebar and content:** 0px (flush) or 1px border
- **Message bubble internal padding:** 10px 14px
- **Vertical gap between messages:** 2px (same sender), 12px (different sender)

### 2.4 Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `radius-none` | 0px | — |
| `radius-sm` | 4px | Badges, small tags, input fields |
| `radius-md` | 6px | Buttons, cards, dropdown menus |
| `radius-lg` | 8px | Modals, panels, large cards |
| `radius-xl` | 12px | Chat bubbles (rounded rectangles) |
| `radius-2xl` | 16px | Chat widget launcher button |
| `radius-full` | 9999px | Avatars, circular buttons, pills |

#### Chat Bubble Radius Specifics
- **Customer bubble (right-aligned):** `border-radius: 16px 16px 4px 16px` (sharp bottom-right corner)
- **Agent bubble (left-aligned):** `border-radius: 16px 16px 16px 4px` (sharp bottom-left corner)
- **Consecutive messages** from same sender: reduce top radius to 4px for visual grouping
- **System messages:** `border-radius: 8px` with centered alignment

### 2.5 Elevation / Shadow System

| Token | Shadow Value | Usage |
|-------|-------------|-------|
| `shadow-xs` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle lift on inputs |
| `shadow-sm` | `0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06)` | Cards at rest |
| `shadow-md` | `0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06)` | Dropdowns, popovers |
| `shadow-lg` | `0 10px 15px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)` | Modals, floating panels |
| `shadow-xl` | `0 20px 25px rgba(0,0,0,0.1), 0 8px 10px rgba(0,0,0,0.04)` | Chat widget when open |
| `shadow-2xl` | `0 25px 50px rgba(0,0,0,0.25)` | Dialog overlays |

#### Elevation Rules
- **Flat by default:** Use borders (gray-200) over shadows for separation
- **Shadows for floating elements only:** Modals, dropdowns, popovers, tooltips
- **Active/hover elevation increase:** Cards go from `shadow-sm` to `shadow-md` on hover
- **Chat widget:** `shadow-xl` when open, `shadow-lg` on hover when collapsed

### 2.6 Icon System

#### Recommendation
Use **Lucide Icons** (lucide.dev) — clean, consistent, MIT-licensed, 1px stroke weight.

**Alternate option:** Phosphor Icons (more variants: thin, light, regular, bold, fill, duotone)

#### Icon Sizes
| Token | Size | Usage |
|-------|------|-------|
| `icon-xs` | 14px | Inline indicators, status dots |
| `icon-sm` | 16px | Button icons, list item icons |
| `icon-md` | 20px | Navigation icons, section icons |
| `icon-lg` | 24px | Feature icons, empty states |
| `icon-xl` | 32px | Hero illustrations, onboarding |

#### Icon Rules
- Always pair icons with text labels (accessibility)
- Icons in navigation: 20px, consistent stroke width of 1.5-2px
- Status indicators (online/offline): use colored circles (10px), not icons
- Send button icon: paper-plane (Lucide `Send`), 20px, rotated 0deg (not 45deg)

---

## 3. Component Library Plan

### 3.1 Buttons

#### Variants

| Variant | Background | Text Color | Border | Usage |
|---------|-----------|------------|--------|-------|
| **Primary** | Blue-700 (#2563EB) | White | None | Primary actions: Send, Submit, Save, Start Chat |
| **Secondary** | White | Blue-700 | Blue-700 1px | Secondary actions: Cancel, Back, Export |
| **Ghost** | Transparent | Gray-700 | None | Tertiary: Close, Toggle, Navigation items |
| **Danger** | Error-700 (#B91C1C) | White | None | Destructive: Delete, End Chat, Remove |
| **Danger Ghost** | Transparent | Error-700 | Error-700 1px | Subtle destructive: Unassign, Leave |
| **Success** | Success-700 (#15803D) | White | None | Positive: Approve, Resolve, Send (customer) |
| **Link** | Transparent | Blue-700 | None (underline on hover) | Inline actions within text |

#### Sizes

| Size | Height | Padding (horizontal) | Font Size | Icon Size | Border Radius |
|------|--------|---------------------|-----------|-----------|---------------|
| `xs` | 24px | 8px | 12px | 14px | radius-sm (4px) |
| `sm` | 32px | 12px | 13px | 16px | radius-md (6px) |
| `md` | 36px | 16px | 14px | 16px | radius-md (6px) |
| `lg` | 40px | 20px | 14px | 20px | radius-md (6px) |
| `xl` | 48px | 24px | 16px | 20px | radius-md (6px) |
| `icon-only` | 36px | 0 (square) | — | 20px | radius-md (6px) |

#### Button States
- **Default:** Base styling
- **Hover:** Background darken 10%, cursor pointer
- **Active/Pressed:** Background darken 15%, slight scale (0.98)
- **Focus:** 2px Blue-500 ring with 2px offset (visible focus ring)
- **Disabled:** Opacity 50%, cursor not-allowed, no hover effect
- **Loading:** Replace text with spinner, disable interaction, maintain width (no layout shift)

### 3.2 Form Inputs

#### Text Input
- **Height:** 36px (md), 32px (sm), 40px (lg)
- **Border:** 1px Gray-200, transitions to Blue-400 on focus
- **Border Radius:** radius-md (6px)
- **Padding:** 8px 12px (left-to-right)
- **Font:** 14px / body, Gray-950 text, Gray-400 placeholder
- **Focus:** 2px Blue-500 ring with 1px offset, border-color Blue-400
- **Error:** Border Error-500, error text below in Error-700 (12px caption)
- **Label:** 13px body-sm, Gray-800, font-weight 500, positioned above input with space-1.5 gap
- **Helper text:** 12px caption, Gray-600, below input with space-1 gap

#### Select / Dropdown
- Styled as text input with custom chevron icon (Lucide `ChevronDown`, 16px, right-aligned)
- Dropdown panel: white bg, shadow-md, max-height 240px with overflow-y auto
- Items: 36px height, 12px 16px padding, 14px text, hover Gray-100 bg
- Selected item: Blue-50 bg, Blue-700 text, check icon on right
- Keyboard navigation: Arrow keys, Enter to select, Escape to close

#### Checkbox
- **Size:** 16px × 16px, radius-sm (3px)
- **Unchecked:** Gray-200 border, white bg
- **Checked:** Blue-700 bg, white checkmark icon (12px)
- **Focus:** Blue-500 ring
- **Label:** 14px body, right of checkbox, space-2 gap
- **Touch target:** Minimum 44px × 44px (wrap in padding if needed)

#### Toggle / Switch
- **Track:** 40px × 20px, radius-full
- **Knob:** 16px × 16px, white, 2px elevation
- **Off state:** Gray-200 track, Gray-400 knob
- **On state:** Blue-700 track, white knob
- **Transition:** 200ms ease for knob slide + color change
- **Label:** Same as checkbox, with optional description text below

#### Textarea
- Same border/focus styling as text input
- Min-height: 80px, resizable vertically only
- Auto-grows with content (up to max-height of 200px)
- Character count shown in bottom-right when near limit

### 3.3 Cards & Containers

#### Standard Card
- **Background:** White
- **Border:** 1px Gray-200
- **Border Radius:** radius-lg (8px)
- **Shadow:** shadow-sm at rest, shadow-md on hover (optional)
- **Padding:** 20px
- **Internal spacing between elements:** 16px

#### Card Variants

| Variant | Use Case | Distinction |
|---------|----------|-------------|
| **Default** | Dashboard metrics, list items | White bg, gray border |
| **Elevated** | Feature cards, clickable items | Shadow-sm, no visible border |
| **Outlined** | Info panels, secondary content | No shadow, dashed or solid border |
| **Inset** | Nested content within cards | Gray-50 bg, inset shadow or border |
| **Interactive** | Clickable cards | Hover shadow-lg, cursor pointer, scale(1.01) |

#### Container Hierarchy
```
Page Container (max-width: 1400px, centered, padding: 24px)
  └── Section Container (margin-bottom: 32px)
        └── Card Container (padding: 20px, border: 1px gray-200)
              └── Content (spaced with gap: 16px)
```

### 3.4 Badges & Tags

#### Badge (Status)
- **Size:** 20px height, 8px 10px padding, 11px font (overline)
- **Border Radius:** radius-full (pill shape)
- **Variants:**
  - `online` — Success-500 bg, white text
  - `away` — Warning-500 bg, white text
  - `offline` — Gray-400 bg, white text
  - `busy` — Error-500 bg, white text
  - `new` — Blue-700 bg, white text
  - `unread` — Blue-700 bg, white text

#### Tag
- **Size:** 24px height, 8px 12px padding, 12px font
- **Border Radius:** radius-sm (4px)
- **Background:** Gray-100, Gray-800 text
- **Removable variant:** Includes × icon button on right

### 3.5 Modals & Dialogs

#### Standard Modal
- **Overlay:** Black at 50% opacity, backdrop-blur(4px)
- **Container:** White bg, radius-lg (8px), shadow-2xl
- **Max Width:** 480px (sm), 560px (md), 720px (lg)
- **Padding:** 24px
- **Header:** heading-2 (20px/600), with × close button (ghost, 36px icon-only)
- **Body:** body (14px), gray-800 text
- **Footer:** Right-aligned button group, gap-3 between buttons, border-top, padding-top 20px
- **Animation:** Fade in overlay (200ms), scale modal from 0.95 to 1 (200ms ease-out)
- **Focus trap:** Tab cycles within modal, Escape closes

#### Confirmation Dialog
- **Icon:** Warning/Error icon centered above title
- **Title:** 18px/600, centered
- **Message:** 14px, gray-600, centered, max-width 360px
- **Buttons:** Stacked vertically on mobile, horizontal on desktop
- **Danger variant:** Red-tinted overlay, danger button emphasized

#### Toast Notifications (Bottom-Right)
- **Position:** Fixed bottom-right, 16px from edges
- **Width:** 360px max
- **Height:** Auto (typically 60-80px)
- **Padding:** 12px 16px
- **Border Radius:** radius-md (6px)
- **Shadow:** shadow-lg
- **Variants:** Success (green left border), Error (red), Warning (amber), Info (blue)
- **Animation:** Slide in from right + fade, auto-dismiss after 5s (success/info), manual dismiss (error/warning)
- **Stacking:** New toasts stack above old ones with 8px gap

### 3.6 Loading States

#### Skeleton Loader
- **Shape:** Rounded rectangles matching content layout
- **Color:** Gray-100 base with Gray-200 shimmer animation
- **Animation:** Shimmer sweep left-to-right, 1.5s infinite
- **Rounded:** radius-md (6px)
- **Usage:** Page loads, content refresh, card grids

#### Spinner
- **Size:** 20px (inline), 32px (card-level), 48px (page-level)
- **Color:** Blue-700, 3px stroke
- **Animation:** 1s linear infinite rotation
- **Usage:** Button loading, form submission, data fetch

#### Chat-Specific Loading
- **Typing indicator:** 3 dots, 8px diameter, bouncing animation (see Section 6)
- **Message sending:** Message appears immediately at reduced opacity, solidifies on confirmation
- **Connection lost:** Banner at top: "Connection lost. Reconnecting..." with retry button

### 3.7 Empty States

- **Illustration:** Simple line illustration, 80px-120px, Gray-300 color
- **Title:** heading-3 (16px/600), Gray-800
- **Description:** body (14px), Gray-600, max-width 280px centered
- **Action:** Primary or Secondary button below description
- **Padding:** 48px vertical, centered horizontally
- **Usage examples:**
  - Empty conversation list: "No conversations yet"
  - Empty search: "No results found"
  - Empty queue: "All caught up!"
  - No file attached: "Drag files here or click to upload"

---

## 4. Layout Architecture

### 4.1 Navigation Structure by Role

#### Customer (Unauthenticated)
```
[Chat Widget Launcher] (floating, bottom-right, 56px circle)
  └── [Chat Window] (360px wide, 560px tall, fixed bottom-right)
        ├── Header: PutraKop logo, language toggle, minimize/close
        ├── Messages Area: Scrollable message list
        ├── Input Area: Text input, file upload, emoji, send
        └── (If queued): Queue banner overlay
```

#### Agent Workspace
```
┌──────────────────────────────────────────────────────────────────────┐
│ Top Bar (56px)                                                       │
│ [Logo] [Search (Ctrl+K)] .................. [Language] [Status] [Avatar] │
├──────────┬───────────────────────────────────────┬──────────────────┤
│ Sidebar  │              Main Content              │    Right Panel   │
│ (240px)  │             (flex-1)                   │    (320px)       │
│          │                                       │   [Collapsed     │
│ [Nav     │  [Chat Workspace / Dashboard / etc.]  │    by default]   │
│  Items]  │                                       │                  │
│          │                                       │  [Customer Info] │
│ [Queue   │                                       │  [Internal Notes]│
│  Summary]│                                       │  [Quick Actions] │
│          │                                       │                  │
├──────────┴───────────────────────────────────────┴──────────────────┤
│ Status Bar (28px) — optional: connection status, queue stats        │
└──────────────────────────────────────────────────────────────────────┘
```

#### Manager Dashboard
```
┌──────────────────────────────────────────────────────────────────────┐
│ Top Bar (56px) — same as Agent                                      │
├──────────┬──────────────────────────────────────────────────────────┤
│ Sidebar  │              Main Content Area                            │
│ (240px)  │             (flex-1, overflow-y auto)                     │
│          │                                                          │
│ [Nav:    │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                   │
│  Overview│  │Metric│ │Metric│ │Metric│ │Metric│  ← Metrics Row    │
│  Live    │  │ Card │ │ Card │ │ Card │ │ Card │                    │
│  Monitor │  └──────┘ └──────┘ └──────┘ └──────┘                   │
│  Agents  │                                                          │
│  Queues  │  ┌─────────────────┐ ┌─────────────────┐               │
│  Analytics│  │  Active Chats   │ │  Agent Status   │  ← Split Row │
│  Reports │  │  Grid / List    │ │  Board          │               │
│          │  └─────────────────┘ └─────────────────┘               │
│ [Dept    │                                                          │
│  Filter] │  ┌─────────────────────────────────────┐               │
│          │  │  Charts / Analytics                  │  ← Charts    │
│          │  └─────────────────────────────────────┘               │
└──────────┴──────────────────────────────────────────────────────────┘
```

#### Admin Panel
```
┌──────────────────────────────────────────────────────────────────────┐
│ Top Bar (56px) — same pattern                                        │
├──────────┬──────────────────────────────────────────────────────────┤
│ Sidebar  │              Main Content Area                            │
│ (240px)  │             (flex-1, overflow-y auto)                     │
│          │                                                          │
│ [Nav:    │  [Page Title] + [Action Buttons]                         │
│  Users   │                                                          │
│  Depts   │  [Filter Bar / Tabs]                                     │
│  KB      │                                                          │
│  Settings│  [Data Table / Form / Config Panel]                      │
│  Announce│                                                          │
│  BizHours│                                                          │
└──────────┴──────────────────────────────────────────────────────────┘
```

### 4.2 Sidebar Design (All Roles)

#### Structure
```
┌─────────────────┐
│ [Logo + Wordmark]│  ← 48px height section, Blue-900 bg
│   PutraKop Live  │
├─────────────────┤
│ 🔍 Search...     │  ← Optional search, depends on role
├─────────────────┤
│ 💬 Conversations │  ← Nav items: 40px height, 12px 16px padding
│    (5)           │     Icon (20px) + Label (14px) + Badge (optional)
│ 📊 Dashboard     │     Active: Blue-700 bg, white text, blue-500 left border
│ 👥 Customers     │     Hover: Gray-100 bg
│ 📋 Queue         │     Inactive: Gray-800 text
│ ⚙️ Settings      │
│                  │
├─────────────────┤
│                  │  ← Flexible spacer
│                  │
├─────────────────┤
│ 👤 Agent Name    │  ← User section at bottom
│    Online ●      │     Status indicator
│ [Status Toggle]  │
└─────────────────┘
```

#### Sidebar Specifications
- **Width:** 240px (expanded), 64px (collapsed), 0px (mobile, as overlay)
- **Background:** White
- **Border-right:** 1px Gray-200
- **Nav item height:** 40px
- **Nav item padding:** 12px 16px
- **Nav item gap:** 2px between items
- **Icon size:** 20px
- **Label size:** 14px, font-weight 500
- **Badge (count):** Right-aligned, Gray-100 bg, Gray-800 text, or Blue-700 bg for new items
- **Collapse toggle:** Button at bottom or top of sidebar, ghost style
- **On collapse:** Only icons visible (centered), tooltip on hover shows label

### 4.3 Top Bar (Header)

#### Specifications
- **Height:** 56px
- **Background:** White
- **Border-bottom:** 1px Gray-200
- **Padding:** 0 24px (horizontal)
- **Layout:** Flex, items center, space-between

#### Left Section
- **Logo:** PutraKop wordmark, 32px height, with blue icon
- **Separator:** 1px vertical gray-200, 24px height, margin 16px
- **Page title:** heading-3 (16px/600), gray-950

#### Right Section (grouped, gap-3)
- **Search trigger:** Ghost button, search icon + "Search..." text (kbd shortcut hint: Ctrl+K)
- **Language toggle:** Ghost button, "EN" / "BM" text toggle
- **Notification bell:** Icon-only ghost button, with blue dot for unread
- **User avatar:** 32px circle, with status indicator dot (8px, positioned bottom-right)
- **Dropdown on avatar:** Profile, Settings, Logout

### 4.4 Content Area

#### Sizing Rules
- **Max width:** 1400px for dashboard content (prevents overly long lines)
- **Full width:** Chat interface, data tables (no max-width constraint)
- **Padding:** 24px on desktop, 16px on tablet/mobile
- **Vertical rhythm:** 24px between major sections, 16px between subsections
- **Grid system:** 12-column grid, 16px gutter

#### Dashboard Grid Layout
```
Row 1: 4 × Metric Cards (each 1/4 width, min-width: 200px)
Row 2: 2/3 + 1/3 split (Active Chats + Agent Status)
Row 3: Full width (Charts / Analytics)
Row 4: 1/2 + 1/2 split (Recent Activity + Quick Actions)
```

---

## 5. Screen-by-Screen Design Specifications

### 5.1 Customer Login/Register Page

#### Layout
```
┌──────────────────────────────────────────────────┐
│                                                  │
│    ┌──────────────┐    ┌──────────────────────┐  │
│    │              │    │                      │  │
│    │   PutraKop   │    │   Login Form         │  │
│    │   Logo       │    │                      │  │
│    │              │    │   [Email Input]      │  │
│    │   Live Chat  │    │   [Password Input]   │  │
│    │              │    │   [Forgot Password]  │  │
│    │   "We're     │    │   [Sign In Button]   │  │
│    │   here to    │    │                      │  │
│    │   help"      │    │   ── or ──           │  │
│    │              │    │   [Register Link]    │  │
│    │              │    │                      │  │
│    │              │    │   [Language Toggle]   │  │
│    │              │    │                      │  │
│    └──────────────┘    └──────────────────────┘  │
│                                                  │
└──────────────────────────────────────────────────┘
```

#### Specifications
- **Background:** Gray-50
- **Left panel (branding):** 40% width, Blue-900 background, white text
  - Large PutraKop logo (80px height)
  - "PutraKop Live Chat" title (display, white)
  - Tagline in current language (body-lg, white at 80% opacity)
  - Decorative pattern or abstract illustration (optional)
- **Right panel (form):** 60% width, white background
  - Max-width: 400px, centered vertically and horizontally
  - Form heading: heading-1 (24px/600)
  - Form subtext: body (14px), gray-600
  - Input spacing: 16px gap between fields
  - "Remember me" checkbox + "Forgot password" link on same row
  - Divider: "or" text with horizontal lines
  - Register CTA: "Don't have an account? Register here"
  - Language toggle: Top-right corner, ghost button
- **Mobile:** Stack vertically, left panel becomes a compact header (120px height)

#### Form Fields
- Email input with email icon (left)
- Password input with lock icon (left), eye toggle (right) to show/hide
- "Sign In" primary button (full width, xl size)
- Button loading state: Spinner + "Signing in..."

### 5.2 Chat Widget / Landing (Customer)

#### Collapsed State (Launcher)
- **Position:** Fixed, bottom-right corner, 24px from edges
- **Size:** 56px × 56px circle
- **Background:** Blue-700
- **Icon:** Chat bubble icon (white, 24px)
- **Shadow:** shadow-lg
- **Hover:** Scale to 1.1, shadow-xl
- **Badge:** Red circle with count if unread messages (positioned top-right, -4px offset)
- **Animation:** Gentle pulse on first visit to draw attention

#### Expanded State (Chat Window)
- **Container:** 360px wide × 560px tall (or 85vh on mobile)
- **Position:** Fixed, bottom-right, above the launcher button
- **Background:** White
- **Border-radius:** radius-xl (12px)
- **Shadow:** shadow-xl
- **Animation:** Scale from 0.9 + fade-in, 200ms ease-out

#### Chat Window Layout
```
┌─────────────────────────────────┐
│ Header (56px)                   │
│ [Logo] PutraKop    [Lang] [×]  │
│ "How can we help you today?"    │
├─────────────────────────────────┤
│ Welcome Message Area            │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ 🤖 AI Greeting              │ │
│ │ "Welcome to PutraKop!       │ │
│ │  How can I assist you?"     │ │
│ │                             │ │
│ │ [Quick Option Buttons]      │ │
│ │ ┌────────┐ ┌────────┐      │ │
│ │ │ Account │ │ Billing│      │ │
│ │ └────────┘ └────────┘      │ │
│ │ ┌────────┐ ┌────────┐      │ │
│ │ │ General │ │ Other  │      │ │
│ │ └────────┘ └────────┘      │ │
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│ Input Area (56px)               │
│ [📎] [Type a message...] [😀] [➤]│
└─────────────────────────────────┘
```

### 5.3 Chat Interface (Customer — After Connecting)

#### Message Area Specifications
- **Background:** Gray-50
- **Message max-width:** 80% of container (288px in 360px widget)
- **Message spacing:**
  - Same sender consecutive: 2px vertical gap
  - Different sender: 12px vertical gap
  - After system message: 16px vertical gap
- **Padding:** 16px horizontal, 12px top/bottom

#### Message Bubble Styling

**Customer Message (right-aligned):**
- Background: Blue-700
- Text: White, 14px/1.5
- Border-radius: 16px 16px 4px 16px
- Padding: 10px 14px
- Max-width: 80% of container

**Agent Message (left-aligned):**
- Background: White
- Text: Gray-950, 14px/1.5
- Border: 1px Gray-200
- Border-radius: 16px 16px 16px 4px
- Padding: 10px 14px
- Max-width: 80% of container

**AI Message (left-aligned):**
- Background: Success-50 (#F0FDF4)
- Text: Gray-950, 14px/1.5
- Border: 1px solid Success-500 at 30% opacity
- Border-radius: 16px 16px 16px 4px
- Padding: 10px 14px
- Left accent: 3px solid Success-700

**System Message (centered):**
- Background: Blue-50 (#EFF6FF)
- Text: Gray-600, 12px/caption
- Border-radius: 8px
- Padding: 6px 12px
- Alignment: Center
- Width: Auto (fits content)

#### Message Metadata (below each bubble)
- **Timestamp:** 11px caption, Gray-400, right-aligned for customer, left-aligned for agent
- **Read receipts (customer side):** 
  - ✓ Sent (single check, Gray-400)
  - ✓✓ Delivered (double check, Gray-400)
  - ✓✓ Read (double check, Blue-500)

#### Typing Indicator
- **Position:** Left-aligned, below last agent message
- **Container:** White bg, Gray-200 border, border-radius 16px 16px 16px 4px
- **Padding:** 12px 16px
- **Dots:** 3 circles, 6px diameter, Gray-400
- **Animation:** Sequential bounce (staggered 150ms), scale 0.6→1.0→0.6, infinite
- **Label (optional):** "Agent is typing..." in 12px caption, Gray-500, below dots

#### Input Area
- **Height:** 56px minimum, grows with content up to 120px
- **Background:** White
- **Border-top:** 1px Gray-200
- **Padding:** 8px 12px
- **Layout:** Horizontal flex, items-end
- **Elements (left to right):**
  1. File upload button (ghost, paperclip icon, 36px)
  2. Text input (flex-1, no border, transparent bg, auto-resize)
  3. Emoji picker trigger (ghost, smile icon, 36px)
  4. Send button (primary, send icon, 36px, only visible when text is entered)

#### Emoji Picker
- **Trigger:** Smile icon button in input area
- **Panel:** Pop-up above input, 320px × 280px
- **Layout:** Grid of emojis (8 columns), with category tabs at top
- **Search:** Text input at top of picker
- **Recently used:** First category
- **Selection:** Click inserts emoji at cursor position, picker stays open
- **Close:** Click outside or press Escape

#### File/Image Upload
- **Trigger:** Paperclip icon or drag-and-drop anywhere in message area
- **Preview:** Shows below input before sending
  - Image: Thumbnail (80px × 80px, object-fit cover, radius-sm)
  - File: Icon + filename + size (12px caption)
  - Remove: × button on top-right of preview
- **Upload progress:** Progress bar below preview (Blue-700, 3px height)
- **Max file size:** Displayed hint: "Max 10MB"

### 5.4 Queue Waiting Screen

#### Layout (Overlay within Chat Widget)
```
┌─────────────────────────────────┐
│ Header (same as chat)           │
├─────────────────────────────────┤
│                                 │
│         [Animated Queue         │
│          Illustration]          │
│                                 │
│      You're in the queue        │
│      Position: #3               │
│                                 │
│      Estimated wait: ~5 min     │
│                                 │
│      ┌───────────────────┐      │
│      │  ████████░░░░░░░  │      │
│      │  Queue Progress   │      │
│      └───────────────────┘      │
│                                 │
│      [Cancel Request]           │
│      (Secondary button)         │
│                                 │
├─────────────────────────────────┤
│ "While you wait, you can..."    │
│ [📖 Browse Help Center]         │
│ [📝 Leave a message]            │
└─────────────────────────────────┘
```

#### Specifications
- **Position number:** display-lg (36px/700), Blue-700
- **Estimated time:** body-lg (16px), Gray-800
- **Progress bar:** 4px height, Blue-700 fill, Gray-100 track, radius-full
- **Cancel button:** Secondary (outlined), centered
- **Help options:** Ghost buttons with icons, left-aligned, stacked

### 5.5 Rating/Feedback Screen

#### Layout (Overlay within Chat Widget, post-chat)
```
┌─────────────────────────────────┐
│ Header: "Chat Complete"         │
├─────────────────────────────────┤
│                                 │
│    Thank you for chatting       │
│    with PutraKop!               │
│                                 │
│    How was your experience?     │
│                                 │
│    ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐│
│    │ 😡 │ │ 😕 │ │ 😐 │ │ 🙂 │ │ 😊 ││
│    │ 1  │ │ 2  │ │ 3  │ │ 4  │ │ 5  ││
│    └───┘ └───┘ └───┘ └───┘ └───┘│
│    Very                   Very  │
│    Poor                  Good   │
│                                 │
│    ┌─────────────────────────┐  │
│    │ Tell us more (optional) │  │
│    │ [Textarea]              │  │
│    └─────────────────────────┘  │
│                                 │
│    ┌─────────────────────────┐  │
│    │    Submit Feedback      │  │
│    └─────────────────────────┘  │
│                                 │
│    [Skip — Close]               │
│                                 │
└─────────────────────────────────┘
```

#### Specifications
- **Rating emojis:** 40px each, 8px gap, horizontal row, centered
- **Selected state:** Scale(1.2), slight elevation, blue ring
- **Labels:** "Very Poor" to "Very Good" in 12px caption below each emoji
- **Textarea:** Optional, 3 rows, auto-grow, character limit 500
- **Submit:** Primary button, full width
- **Skip:** Ghost link below, "Skip — Close"
- **Thank you state:** After submit, replace form with checkmark animation + "Thank you for your feedback!"

### 5.6 Agent Dashboard

#### Metrics Row (Top)
```
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ 📊       │ │ 💬       │ │ ⏱️       │ │ ⭐       │
│ Active   │ │ Open     │ │ Avg Wait │ │ CSAT     │
│ Chats    │ │ Tickets  │ │ Time     │ │ Score    │
│          │ │          │ │          │ │          │
│   12     │ │   34     │ │ 2m 30s   │ │ 4.6/5    │
│ ▲ 8%     │ │ ▼ 3%     │ │ ▼ 12%    │ │ ▲ 0.2    │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
```

#### Metric Card Specifications
- **Width:** 1/4 of container (min 200px)
- **Height:** 100px
- **Padding:** 16px 20px
- **Background:** White
- **Border:** 1px Gray-200
- **Border-radius:** radius-lg (8px)
- **Icon:** 20px, Blue-500, in Blue-50 circle (32px)
- **Label:** 12px caption, Gray-600, uppercase (overline style)
- **Value:** heading-1 (24px/700), Gray-950
- **Trend:** 12px caption, Success-700 for positive, Error-700 for negative, with arrow icon

#### Active Conversations Grid
- **Layout:** Table/list view (toggleable)
- **List columns:** Avatar, Customer name, Preview (truncated), Time, Status badge, Unread count
- **Row height:** 64px
- **Row hover:** Gray-50 background
- **Row click:** Opens chat in workspace
- **Unread indicator:** Blue-700 dot on avatar, bold text for name/preview
- **Status badges:** New (blue), Active (green), Waiting (amber), Resolved (gray)

### 5.7 Agent Chat Workspace

#### Three-Panel Layout

**Left Panel — Conversation List (280px, collapsible)**
```
┌─────────────────────────────┐
│ Search conversations...     │
├─────────────────────────────┤
│ [Tabs: All | Mine | Queue]  │
├─────────────────────────────┤
│ ┌─────────────────────────┐ │
│ │ ● Ahmad bin Ali         │ │  ← Bold if unread
│ │ I need help with my...  │ │  ← 13px, gray-600, 1-line truncate
│ │ 2m ago        [🔴 2]   │ │  ← Time + unread badge
│ └─────────────────────────┘ │
│ ┌─────────────────────────┐ │
│ │ ○ Siti Aminah           │ │
│ │ Thank you for the...    │ │
│ │ 15m ago       [  ]     │ │
│ └─────────────────────────┘ │
│ ...                         │
└─────────────────────────────┘
```

**Center Panel — Active Chat (flex-1)**
```
┌─────────────────────────────────────────────┐
│ Chat Header (56px)                           │
│ [Avatar] Ahmad bin Ali  ● Online    [⋮] [→] │
│ Department: Account | Duration: 5m 30s       │
├─────────────────────────────────────────────┤
│ Message Area (flex-1, scroll)                │
│                                              │
│ [System] Chat started at 2:30 PM             │
│                                              │
│ [Customer] Hi, I need help with my account   │
│            balance                           │
│                              2:30 PM    ✓✓   │
│                                              │
│ [Agent] Hello Ahmad! I'd be happy to help.   │
│         Can you provide your account number? │
│ 2:31 PM                                      │
│                                              │
│ [Customer] Sure, it's 1234567890             │
│                              2:32 PM    ✓✓   │
│                                              │
│ [Internal Note] 📌 Account verified, balance │
│                 issue confirmed - Tier 2     │
│                                              │
├─────────────────────────────────────────────┤
│ Quick Reply Bar (36px)                       │
│ [Greeting] [FAQ] [Escalate] [+ Custom]      │
├─────────────────────────────────────────────┤
│ Input Area (56px+)                           │
│ [📎] [Internal Note Toggle] [Type...] [😀] [➤]│
└─────────────────────────────────────────────┘
```

**Right Panel — Context Sidebar (320px, collapsible)**
```
┌───────────────────────────────┐
│ [Tabs: Info | Notes | Actions]│
├───────────────────────────────┤
│ CUSTOMER DETAILS              │
│                               │
│ [Avatar 48px]                 │
│ Ahmad bin Ali                 │
│ ahmad@email.com               │
│ +60 12-345-6789              │
│                               │
│ 📋 Account: 1234567890       │
│ 🏢 Department: Account       │
│ 📅 Member since: Jan 2024    │
│ 💬 Previous chats: 5         │
│ ⭐ Last rating: 4/5          │
│                               │
│ ─────────────────────         │
│                               │
│ CONVERSATION TAGS             │
│ [Billing] [Urgent] [+ Add]   │
│                               │
│ ─────────────────────         │
│                               │
│ QUICK ACTIONS                 │
│ [Transfer Chat]               │
│ [View History]                │
│ [Block User]                  │
│ [Export Transcript]           │
│                               │
└───────────────────────────────┘
```

#### Internal Notes Styling
- **Appearance:** Dashed border (Gray-300), Yellow-50 background
- **Icon prefix:** 📌 pushpin emoji or Lucide `Pin` icon
- **Text:** Gray-800, 13px, italic optional
- **Not visible to customer** (clearly indicated with label: "Internal Note — Not visible to customer")
- **Add note:** Text input appears below existing notes, with "Add Note" button

### 5.8 Quick Replies Panel

#### Trigger
- Button in input area or keyboard shortcut (Ctrl+/)
- Opens as a popover or inline expansion above input

#### Layout
```
┌───────────────────────────────────┐
│ 🔍 Search quick replies...        │
├───────────────────────────────────┤
│ 📁 Greetings                      │
│   ├ Hello! How can I help?        │
│   └ Welcome to PutraKop!          │
│ 📁 Account Issues                 │
│   ├ Please provide your account # │
│   └ Let me check your account...  │
│ 📁 Billing                        │
│   ├ Your invoice is attached      │
│   └ Payment received, thanks!     │
│ 📁 Closing                        │
│   ├ Is there anything else?       │
│   └ Thank you for contacting us!  │
└───────────────────────────────────┘
```

#### Specifications
- **Width:** Same as chat input area
- **Max height:** 300px, scrollable
- **Category:** Collapsible sections with chevron toggle
- **Item height:** 40px, 12px 16px padding
- **Hover:** Gray-100 bg
- **Click:** Inserts text into chat input
- **Variables:** Support `{{name}}`, `{{account_number}}` placeholders with preview

### 5.9 Transfer Dialog

#### Layout
```
┌─────────────────────────────────────┐
│ Transfer Chat                   [×] │
├─────────────────────────────────────┤
│                                     │
│ Transfer this conversation to:      │
│                                     │
│ [🔍 Search agents or departments]   │
│                                     │
│ DEPARTMENTS                         │
│ ┌─────────────────────────────────┐ │
│ │ 📁 Billing Department     (3)   │ │  ← Agent count
│ │ 📁 Technical Support      (5)   │ │
│ │ 📁 Account Services       (2)   │ │
│ └─────────────────────────────────┘ │
│                                     │
│ AGENTS (Available)                  │
│ ┌─────────────────────────────────┐ │
│ │ ● Siti Aminah           Ready   │ │
│ │ ● Ahmad Khan             Busy    │ │
│ │ ○ Fatimah Hassan        Away    │ │
│ └─────────────────────────────────┘ │
│                                     │
│ Transfer note (visible to agent):   │
│ [Textarea — optional message]       │
│                                     │
│ [Cancel]              [Transfer]    │
└─────────────────────────────────────┘
```

#### Specifications
- **Modal max-width:** 480px
- **Department rows:** 48px height, icon + name + agent count
- **Agent rows:** 48px height, avatar (32px) + name + status dot + availability text
- **Selected state:** Blue-50 bg, Blue-700 border-left
- **Disabled for unavailable agents:** Gray-400 text, no click
- **Transfer button:** Primary, disabled until selection made

### 5.10 Manager — Live Monitoring

#### Conversation Grid View
```
┌─────────────────────────────────────────────────────────┐
│ Filter: [All Departments ▾] [Status ▾] [Agent ▾] [🔍]  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│ │ 👤 Ahmad │ │ 👤 Siti  │ │ 👤 Yusuf │ │ 👤 Lisa  │   │
│ │ → Agent A│ │ → Agent B│ │ → AI Bot │ │ → Agent C│   │
│ │ "Hi, I   │ │ "My      │ │ "Let me  │ │ "Thank   │   │
│ │  need..."│ │  bill..."│ │  check.."│ │  you!"   │   │
│ │ 2m ⚠️    │ │ 5m ●     │ │ 30s 🤖   │ │ 8m ✓     │   │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
│                                                         │
│ ┌──────────┐ ┌──────────┐                               │
│ │ 👤 Rashid│ │ 👤 Aisha │                               │
│ │ → Agent D│ │ → Queue  │                               │
│ │ "Can you │ │ Position │                               │
│ │  help.." │ │ #2       │                               │
│ │ 1m ●     │ │ Waiting  │                               │
│ └──────────┘ └──────────┘                               │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

#### Card Specifications
- **Width:** 1/4 of container (min 220px, max 280px)
- **Height:** 140px
- **Padding:** 14px
- **Background:** White
- **Border:** 1px Gray-200, left 3px accent color based on wait time:
  - < 2 min: Green
  - 2-5 min: Yellow
  - 5+ min: Red
- **Avatar:** 32px circle, top-left
- **Customer name:** 14px/600, below avatar
- **Assigned to:** 12px caption, Gray-600, with agent avatar (20px)
- **Message preview:** 13px, Gray-600, 2-line truncate
- **Footer:** Wait time + status icon, 12px caption
- **Hover:** Shadow-md, slight scale(1.02)
- **Click:** Opens full conversation view

### 5.11 Manager — Analytics Dashboard

#### Chart Layout
```
┌────────────────────────────────────────────────────┐
│ Date Range: [Last 7 Days ▾]  [Custom Range]        │
├────────────────────────────────────────────────────┤
│                                                    │
│ ┌────────────────────────────────────────────────┐ │
│ │  📈 Chat Volume Trend                          │ │
│ │  [Line Chart — 7-day, hourly/daily]            │ │
│ │  ── This Week  ── Last Week                    │ │
│ └────────────────────────────────────────────────┘ │
│                                                    │
│ ┌───────────────────┐ ┌──────────────────────────┐ │
│ │ 📊 Response Time   │ │ ⭐ Satisfaction Scores   │ │
│ │ [Bar Chart]        │ │ [Donut Chart]            │ │
│ │ Avg: 2m 15s        │ │ 4.6/5 overall            │ │
│ │ P95: 5m 30s        │ │ ████████░░ 86% positive  │ │
│ └───────────────────┘ └──────────────────────────┘ │
│                                                    │
│ ┌────────────────────────────────────────────────┐ │
│ │ 🏆 Agent Performance Rankings                   │ │
│ │ [Horizontal Bar Chart or Table]                 │ │
│ │ #1 Siti Aminah   — 4.8★ — 12m avg — 45 chats │ │
│ │ #2 Ahmad Khan    — 4.6★ — 8m avg  — 38 chats │ │
│ │ #3 Fatimah H.    — 4.5★ — 10m avg — 42 chats │ │
│ └────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

#### Chart Design Specifications
- **Chart library recommendation:** Recharts (React) or Chart.js
- **Color palette for charts:**
  - Series 1: Blue-700 (#2563EB)
  - Series 2: Blue-400 (#93C5FD)
  - Series 3: Success-500 (#22C55E)
  - Series 4: Warning-500 (#EAB308)
  - Series 5: Error-500 (#EF4444)
  - Background: Gray-50
  - Grid lines: Gray-200, dashed
  - Axis labels: 12px caption, Gray-600
  - Tooltip: White bg, shadow-md, 13px text
- **Chart height:** 240px (standard), 320px (featured)
- **Responsive:** Stack vertically on mobile, reduce to 200px height

### 5.12 Admin Panel Screens

#### User Management Table
```
┌─────────────────────────────────────────────────────────┐
│ User Management                    [+ Add User]         │
├─────────────────────────────────────────────────────────┤
│ [🔍 Search...] [Role ▾] [Department ▾] [Status ▾]      │
├─────────────────────────────────────────────────────────┤
│ ☐ │ Name          │ Email           │ Role    │ Status │ Actions │
├───┼───────────────┼─────────────────┼─────────┼────────┼─────────┤
│ ☐ │ Siti Aminah   │ siti@putrakop   │ Agent   │ ● On   │ [⋮]     │
│ ☐ │ Ahmad Khan    │ ahmad@putrakop  │ Manager │ ● On   │ [⋮]     │
│ ☐ │ Fatimah H.    │ fatimah@putrakop│ Agent   │ ○ Off  │ [⋮]     │
│ ☐ │ Admin User    │ admin@putrakop  │ Admin   │ ● On   │ [⋮]     │
├─────────────────────────────────────────────────────────┤
│ Showing 1-4 of 24 users              [< 1 2 3 ... 6 >] │
└─────────────────────────────────────────────────────────┘
```

#### Table Specifications
- **Header row:** Gray-50 bg, 13px overline, Gray-600 text, uppercase, border-bottom
- **Data row:** 52px height, border-bottom 1px Gray-200
- **Row hover:** Gray-50 bg
- **Checkbox column:** 48px width, centered
- **Avatar column (optional):** 40px, with name beside it
- **Sortable columns:** Click header to sort, chevron icon indicates direction
- **Pagination:** Bottom-right, "Showing X-Y of Z", page buttons

#### Knowledge Base Editor
```
┌─────────────────────────────────────────────────────────┐
│ Knowledge Base                      [+ New Article]     │
├───────────────────────────┬─────────────────────────────┤
│ Article List              │ Article Editor              │
│                           │                             │
│ [🔍 Search...]            │ Title: [___________]        │
│                           │                             │
│ 📁 Account Issues         │ Category: [Account ▾]       │
│   ├ How to check balance  │                             │
│   ├ Update personal info  │ ┌─────────────────────┐     │
│   └ Reset password        │ │ Rich Text Editor    │     │
│                           │ │ (Bold, italic, link,│     │
│ 📁 Billing                │ │  image, code)       │     │
│   ├ Understanding invoices│ │                     │     │
│   └ Payment methods       │ │                     │     │
│                           │ │                     │     │
│ 📁 Technical              │ └─────────────────────┘     │
│   └ App not working       │                             │
│                           │ [Preview] [Publish] [Draft] │
└───────────────────────────┴─────────────────────────────┘
```

#### Business Hours Configurator
```
┌─────────────────────────────────────────────────────────┐
│ Business Hours Settings                                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Timezone: [UTC+8 (Malaysia) ▾]                         │
│                                                         │
│ ┌─────────┬──────────┬──────────┬───────────────────┐   │
│ │ Day     │ Enabled  │ Open     │ Close             │   │
│ ├─────────┼──────────┼──────────┼───────────────────┤   │
│ │ Monday  │ [Toggle] │ 09:00    │ 18:00             │   │
│ │ Tuesday │ [Toggle] │ 09:00    │ 18:00             │   │
│ │ Wednes. │ [Toggle] │ 09:00    │ 18:00             │   │
│ │ Thursday│ [Toggle] │ 09:00    │ 18:00             │   │
│ │ Friday  │ [Toggle] │ 09:00    │ 17:30             │   │
│ │ Saturday│ [Toggle] │ [OFF]    │ —                 │   │
│ │ Sunday  │ [Toggle] │ [OFF]    │ —                 │   │
│ └─────────┴──────────┴──────────┴───────────────────┘   │
│                                                         │
│ ☐ Apply same hours to all departments                   │
│                                                         │
│ [Save Changes]                                          │
│                                                         │
│ ⚠️ Current status: OPEN (Until 6:00 PM)                │
└─────────────────────────────────────────────────────────┘
```

---

## 6. Chat Interface Deep-Dive

### 6.1 Message Flow & States

#### Message Lifecycle
```
[User types] → [Presses Send] → [Optimistic UI: shows immediately]
                                    ↓
                              [Sent state: single ✓]
                                    ↓
                              [Server confirms]
                                    ↓
                              [Delivered: double ✓✓]
                                    ↓
                              [Agent reads it]
                                    ↓
                              [Read: double ✓✓ in blue]
```

#### Optimistic UI Behavior
- Message appears instantly in the chat with 80% opacity
- Timestamp shows "Sending..." briefly
- On success: opacity transitions to 100%, timestamp updates, status icon appears
- On failure: red error banner on the message, retry button (ghost), message border turns Error-500

### 6.2 Message Grouping Rules

1. **Same sender, within 2 minutes:** Group messages, reduce spacing, show timestamp only on last message in group
2. **Different sender:** Full spacing, new avatar (if applicable), new timestamp
3. **System messages:** Always standalone, centered, with generous spacing above and below (16px)
4. **Date separators:** Centered line with date label (e.g., "Today", "June 15, 2026"), gray-200 line, 12px caption, gray-500 text

### 6.3 Rich Content in Messages

#### Links
- Auto-detected URLs become clickable
- Styled: Blue-700, underline on hover
- Show domain preview card for recognized links (optional, future feature)

#### Code Blocks
- Background: Gray-100
- Font: Monospace, 13px
- Border-radius: radius-sm (4px)
- Padding: 8px 12px
- Copy button in top-right corner

#### Quoted Messages
- Left border: 3px Blue-400
- Background: Gray-50
- Padding: 8px 12px
- Shows: Original sender name + truncated message text
- Click to scroll to original message

### 6.4 Image Handling in Chat

#### In-Message Image
- **Thumbnail:** Max 240px × 180px, radius-md
- **Click:** Opens lightbox/modal
- **Loading:** Skeleton rectangle with shimmer
- **Alt text:** Required, shown on hover

#### Lightbox/Viewer
- **Overlay:** Black at 80% opacity
- **Image:** Centered, max 90vw × 85vh, object-fit contain
- **Controls:**
  - Close (×): Top-right, white on black bg
  - Download: Top-right, next to close
  - Zoom in/out: Bottom-center
  - Navigation arrows: Left/right if multiple images
- **Keyboard:** Arrow keys for nav, Escape to close
- **Caption:** Image filename + size, below image, white text

### 6.5 File Attachment Cards

#### In-Message File Card
```
┌─────────────────────────────────┐
│ 📄  │  document.pdf             │
│     │  2.4 MB                   │
│     │  [Download]               │
└─────────────────────────────────┘
```

- **Width:** Full message width (up to max-width)
- **Height:** 56px
- **Background:** Gray-50
- **Border:** 1px Gray-200
- **Border-radius:** radius-md (6px)
- **Left:** File type icon (24px), colored by type (PDF=red, DOC=blue, XLS=green, etc.)
- **Center:** Filename (14px/600), file size (12px caption)
- **Right:** Download button (ghost, 16px icon)
- **Hover:** Gray-100 bg

---

## 7. Responsive Strategy

### 7.1 Breakpoints

| Breakpoint | Name | Width | Target |
|------------|------|-------|--------|
| `xs` | Mobile Small | 0 - 374px | Small phones |
| `sm` | Mobile | 375 - 639px | Standard phones |
| `md` | Tablet | 640 - 1023px | Tablets, small laptops |
| `lg` | Desktop | 1024 - 1279px | Standard laptops |
| `xl` | Desktop Large | 1280 - 1535px | Large monitors |
| `2xl` | Desktop XL | 1536px+ | Ultra-wide monitors |

### 7.2 Mobile-First Approach

**The design is mobile-first.** Base styles target mobile (375px+), with progressive enhancement for larger screens.

#### Rationale
- Chat applications are predominantly mobile (60%+ of customer interactions)
- Forces focus on essential content
- Easier to add complexity than remove it
- Better performance baseline

### 7.3 Layout Adaptations by Breakpoint

#### Mobile (0 - 639px)

**Customer Chat Widget:**
- Full screen (100vw × 100vh) when opened
- Header condensed: Logo only (no text), close button
- Input area: Bottom-fixed, safe-area-aware
- Emoji picker: Full width, 50vh height

**Agent Workspace:**
- Sidebar: Hidden by default, slide-in overlay from left (hamburger menu trigger)
- Right panel: Hidden by default, slide-in overlay from right
- Chat workspace: Full width
- Conversation list: Full screen, tap to open chat
- Bottom tab bar: [Chats] [Dashboard] [Profile] — persistent navigation

**Manager Dashboard:**
- Sidebar: Hidden, hamburger menu
- Metrics: 2-column grid (1/2 width each)
- Charts: Full width, 200px height
- Agent status: Single column list (not grid)

**Admin Panel:**
- Sidebar: Hidden, hamburger menu
- Tables: Horizontally scrollable
- Forms: Full width, stacked fields

#### Tablet (640 - 1023px)

**Agent Workspace:**
- Sidebar: Collapsed (64px, icons only), expandable on hover or tap
- Right panel: Hidden by default, toggleable
- Chat workspace: Takes remaining space
- Conversation list: 280px sidebar or full-screen toggle

**Manager Dashboard:**
- Sidebar: Collapsed (64px)
- Metrics: 2×2 grid
- Charts: 2-column layout

#### Desktop (1024px+)

**Agent Workspace:**
- Full three-panel layout as designed
- Sidebar: 240px, persistent
- Right panel: 320px, toggleable
- All features visible

**Manager Dashboard:**
- Full sidebar: 240px
- Full content area with all panels

### 7.4 Touch-Friendly Considerations

| Element | Minimum Touch Target | Notes |
|---------|---------------------|-------|
| Navigation items | 44px × 44px | Includes padding |
| Buttons | 44px × 44px | Even xs buttons have 44px touch area |
| Chat input | 48px height | Larger than desktop |
| Emoji picker items | 40px × 40px | Grid cells |
| File upload button | 44px × 44px | |
| Toggle switches | 44px × 24px | Vertical padding to 44px |
| Links in text | 44px min-height | Line-height adjustment |
| Close/dismiss buttons | 44px × 44px | |

### 7.5 Mobile-Specific Adaptations

#### Chat Widget Mobile
```
Desktop (360×560px floating) → Mobile (100vw × 100vh fullscreen)
```

Changes:
- **Border-radius:** 0 (full screen)
- **Shadow:** None (full screen)
- **Position:** Fixed, inset 0
- **Header:** 48px height, back arrow replaces close, logo centered
- **Input area:** 52px height, accounts for safe area (iPhone notch)
- **Keyboard:** Input stays visible when keyboard opens (scroll to bottom behavior)
- **Haptic feedback:** Vibrate on message send (if supported)

#### Conversation List Mobile
- Each item: 72px height (larger touch targets)
- Swipe left: Quick actions (Transfer, Close, Archive)
- Pull down: Refresh

#### Dashboard Mobile
- Metric cards: 2-column grid, 80px height (compact)
- Charts: Full width, scrollable horizontally if needed
- Tables: Card view instead of table rows

---

## 8. Accessibility Plan

### 8.1 WCAG 2.1 AA Compliance Checklist

#### Perceivable
- [ ] **Color contrast:** All text meets 4.5:1 ratio (body), 3:1 (large text)
- [ ] **Not color alone:** Status indicators use icon + text + color (never color alone)
- [ ] **Text resize:** Interface works at 200% zoom without horizontal scroll
- [ ] **Images:** All images have meaningful `alt` text
- [ ] **Focus indicators:** Visible focus ring on all interactive elements (2px Blue-500)

#### Operable
- [ ] **Keyboard navigation:** All features accessible via keyboard
- [ ] **No keyboard traps:** Tab can escape modals, widgets, and panels
- [ ] **Skip links:** "Skip to main content" link at top of page
- [ ] **Focus order:** Logical tab order matching visual layout (left-to-right, top-to-bottom)
- [ ] **Timing:** No time limits on chat input; queue timeout shows warning before expiry

#### Understandable
- [ ] **Language:** `lang` attribute set to "en" or "ms" based on selection
- [ ] **Consistent navigation:** Same nav structure across all pages
- [ ] **Error identification:** Form errors announced to screen readers
- [ ] **Labels:** All form inputs have associated `<label>` elements
- [ ] **Instructions:** Don't rely solely on visual cues

#### Robust
- [ ] **Semantic HTML:** Proper heading hierarchy (h1 → h2 → h3), landmarks, lists
- [ ] **ARIA:** Used only when semantic HTML is insufficient
- [ ] **Live regions:** Chat messages announced via `aria-live="polite"` (new messages)
- [ ] **Role attributes:** Custom components have appropriate ARIA roles

### 8.2 Screen Reader Considerations

#### Chat Interface
- **New message announcement:** `aria-live="polite"` region for incoming messages
- **Message role:** Each message wrapped in `<article>` or `role="article"` with aria-label
- **Timestamp:** Visually hidden but available to screen readers: `<span class="sr-only">at 2:30 PM</span>`
- **Read receipts:** `aria-label="Message read"` (not visual-only)
- **Typing indicator:** `aria-live="polite"` with "Agent is typing" announcement
- **Emoji picker:** `role="dialog"` with `aria-label="Emoji picker"`

#### Navigation
- **Sidebar:** `<nav>` with `aria-label="Main navigation"`
- **Active item:** `aria-current="page"`
- **Badge counts:** `aria-label="5 unread conversations"`
- **Search:** `role="search"` on search container

#### Modals
- **Focus trap:** Tab cycles within modal only
- **Return focus:** On close, focus returns to trigger element
- **Backdrop click:** Closes modal, returns focus to trigger
- **Role:** `role="dialog"` with `aria-modal="true"`
- **Title:** `aria-labelledby` pointing to modal title

### 8.3 Keyboard Shortcuts

| Shortcut | Action | Scope |
|----------|--------|-------|
| `Ctrl + K` | Open search | Global |
| `Ctrl + /` | Open quick replies | Chat input |
| `Ctrl + Enter` | Send message | Chat input |
| `Escape` | Close modal/popover | Global |
| `Ctrl + Shift + M` | Toggle internal note mode | Agent chat |
| `Ctrl + Shift + T` | Transfer chat | Agent chat |
| `Ctrl + 1-9` | Switch conversations | Agent workspace |
| `Alt + L` | Toggle language | Global |
| `Alt + S` | Toggle sidebar | Agent/Manager |

### 8.4 Focus State Design

#### Default Focus Ring
```css
:focus-visible {
  outline: 2px solid #60A5FA; /* Blue-500 */
  outline-offset: 2px;
}
```

#### Button Focus
- 2px Blue-500 ring, 2px offset
- Ring color adjusts on dark backgrounds (use white ring on Blue-700 buttons)

#### Input Focus
- Border transitions to Blue-400
- 2px Blue-500 ring with 1px offset (inner ring + outer glow)

#### Skip Link
- Hidden by default (visually hidden but focusable)
- On focus: Appears as banner at top of page, blue bg, white text
- "Skip to main content" → jumps to `<main>` element

### 8.5 Motion Preferences

```css
@media (prefers-reduced-motion: reduce) {
  /* Disable all non-essential animations */
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  
  /* Keep: Focus indicators, loading spinners */
  /* Remove: Message send animation, typing dots bounce, 
     page transitions, hover effects */
}
```

---

## 9. Animation & Micro-Interactions

### 9.1 Timing Functions

| Name | Value | Usage |
|------|-------|-------|
| `ease-default` | `cubic-bezier(0.4, 0, 0.2, 1)` | Most transitions |
| `ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Exiting elements |
| `ease-out` | `cubic-bezier(0, 0, 0.2, 1)` | Entering elements |
| `ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Bouncy, playful (sparingly) |

### 9.2 Duration Scale

| Token | Duration | Usage |
|-------|----------|-------|
| `duration-fast` | 100ms | Hover states, opacity changes |
| `duration-normal` | 200ms | Standard transitions (buttons, inputs) |
| `duration-slow` | 300ms | Panel slides, modal entrance |
| `duration-slower` | 500ms | Page transitions, complex animations |

### 9.3 Specific Animations

#### Message Send
- **Trigger:** User presses Send
- **Animation:** Message slides up from bottom + fades in
- **Duration:** 200ms, ease-out
- **Optimistic:** Message appears instantly at 80% opacity, transitions to 100% on confirmation

#### New Message Arrival
- **Trigger:** Incoming message from other party
- **Animation:** Message slides in from left (agent) or right (customer), fades in
- **Duration:** 300ms, ease-out
- **If scrolled up:** "New messages" indicator appears at bottom with bounce

#### Typing Indicator
- **Animation:** Three dots with staggered bounce
- **Timing:** Each dot delays 150ms, bounces for 600ms, repeats
- **Scale:** 0.5 → 1.0 → 0.5
- **Color:** Gray-400 at 100% when active, Gray-300 at 50% when idle

#### Status Change
- **Online → Offline:** Green dot fades to gray, 300ms
- **Read receipt:** Check marks animate: single → double (300ms) → blue color (200ms)
- **Queue position update:** Number slides up/down (like odometer), 200ms

#### Panel Transitions
- **Sidebar collapse:** Width transitions 240px → 64px, 300ms, ease-default
- **Right panel toggle:** Translate from right, 300ms, ease-out
- **Mobile sidebar:** Slide from left, 300ms, with backdrop fade (200ms)

#### Page Transitions
- **Approach:** Fade + slight upward slide
- **Duration:** 300ms
- **Old page:** Fade out + slide down 8px, 150ms
- **New page:** Fade in + slide up from 8px, 300ms
- **Loading state:** Skeleton shown during transition

#### Button Hover
- **Background darken:** 10% over 150ms
- **Scale:** 1.02 on press (optional, for primary CTA only)
- **Shadow increase:** shadow-sm → shadow-md on hover (cards)

#### Modal Entrance
- **Overlay:** Fade in, 200ms
- **Container:** Scale from 0.95 + fade in, 200ms, ease-out
- **Modal exit:** Reverse, 150ms, ease-in

#### Toast Notification
- **Enter:** Slide from right + fade in, 300ms, ease-out
- **Exit:** Slide to right + fade out, 200ms, ease-in
- **Stack:** When new toast appears, existing ones shift up, 200ms

#### Micro-Interactions Summary Table
| Interaction | Duration | Easing | Trigger |
|-------------|----------|--------|---------|
| Message send | 200ms | ease-out | Send button click |
| Message arrive | 300ms | ease-out | WebSocket event |
| Typing dots | 600ms loop | ease-in-out | Agent typing |
| Status dot change | 300ms | ease-default | Status event |
| Read receipt | 200ms | ease-default | Read event |
| Panel slide | 300ms | ease-default | Toggle button |
| Modal open | 200ms | ease-out | Trigger click |
| Toast enter | 300ms | ease-out | Notification |
| Toast exit | 200ms | ease-in | Auto/manual dismiss |
| Page transition | 300ms | ease-out | Route change |
| Button hover | 150ms | ease-default | Mouse enter |
| Card hover | 200ms | ease-default | Mouse enter |

---

## 10. Internationalization (i18n) Strategy

### 10.1 Bilingual Requirements (Bahasa Malaysia / English)

#### Text Expansion Considerations
| Language | Typical Expansion vs English | Example |
|----------|------------------------------|---------|
| Bahasa Malaysia | +15-25% longer | "Fail" → "Muat naik fail" |
| English | Baseline | "Upload file" |

**Design implication:** All containers must accommodate 25% longer text in BM without breaking layout. Use flexible widths, text wrapping, and avoid fixed-width text containers.

### 10.2 Language Toggle Design

#### Position: Top bar, right side (before user avatar)
```
┌──────────────────────────────┐
│ [EN] ←→ [BM]                │  ← Toggle switch style
└──────────────────────────────┘
```

Or:
```
┌──────────────────┐
│ 🌐 EN ▾          │  ← Dropdown with both options
│    BM            │
└──────────────────┘
```

#### Specifications
- **Toggle variant:** Pill-shaped toggle, 48px × 24px
- **Labels:** "EN" and "BM" inside the toggle
- **Active side:** White text on Blue-700 bg
- **Inactive side:** Gray-600 text on transparent
- **Animation:** Knob slides, 200ms ease-default
- **Persistence:** Saved to localStorage, defaults to browser language or EN

### 10.3 RTL Consideration
- **Not required** for this project (Malay uses Latin script, LTR)
- However, structure CSS with logical properties (start/end instead of left/right) for future-proofing

### 10.4 Translation Key Structure
Follow the pattern established in the existing PutraKop projects:
```json
{
  "chat": {
    "greeting": "How can we help you?" / "Bagaimana kami boleh membantu?",
    "typing": "{name} is typing..." / "{name} sedang menaip...",
    "queue_position": "Position: #{number}" / "Kedudukan: #{number}",
    ...
  }
}
```

---

## 11. Implementation Notes

### 11.1 Recommended Tech Stack

| Layer | Recommendation | Rationale |
|-------|---------------|-----------|
| **Framework** | Next.js 14+ (App Router) | SSR for performance, built-in routing, API routes |
| **Styling** | Tailwind CSS + shadcn/ui | Consistent design system, accessible components out of the box |
| **Component Library** | shadcn/ui (Radix primitives) | Accessible, customizable, no vendor lock-in |
| **State Management** | Zustand or Jotai | Lightweight, great for real-time state |
| **Real-time** | Socket.io or native WebSocket | Bidirectional communication for chat |
| **Charts** | Recharts or Tremor | React-native, accessible, consistent |
| **Icons** | Lucide React | Consistent, tree-shakeable, comprehensive |
| **Animations** | Framer Motion + CSS transitions | Complex animations (Framer) + simple ones (CSS) |
| **Forms** | React Hook Form + Zod | Performance, validation, accessibility |
| **i18n** | next-intl | Type-safe translations, SSR compatible |

### 11.2 Design Token Architecture

Use CSS custom properties for all design tokens:
```css
:root {
  /* Colors */
  --color-primary-700: #2563EB;
  --color-primary-600: #3B82F6;
  /* ... all tokens */
  
  /* Spacing */
  --space-1: 4px;
  --space-2: 8px;
  /* ... */
  
  /* Typography */
  --font-family-primary: 'Inter', sans-serif;
  --font-size-body: 14px;
  /* ... */
  
  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
  /* ... */
}
```

### 11.3 File Structure Recommendation

```
src/
├── app/                          # Next.js App Router
│   ├── (auth)/                   # Auth route group
│   │   ├── login/
│   │   └── register/
│   ├── (dashboard)/              # Dashboard route group
│   │   ├── agent/
│   │   │   ├── chats/
│   │   │   │   └── [chatId]/
│   │   │   └── dashboard/
│   │   ├── manager/
│   │   │   ├── live-monitor/
│   │   │   ├── analytics/
│   │   │   └── agents/
│   │   └── admin/
│   │       ├── users/
│   │       ├── departments/
│   │       ├── knowledge-base/
│   │       └── settings/
│   ├── widget/                   # Customer chat widget
│   │   └── page.tsx
│   └── layout.tsx                # Root layout
├── components/
│   ├── ui/                       # Base UI components (shadcn)
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   ├── modal.tsx
│   │   └── ...
│   ├── chat/                     # Chat-specific components
│   │   ├── message-bubble.tsx
│   │   ├── typing-indicator.tsx
│   │   ├── emoji-picker.tsx
│   │   ├── file-attachment.tsx
│   │   ├── chat-input.tsx
│   │   └── chat-window.tsx
│   ├── layout/                   # Layout components
│   │   ├── sidebar.tsx
│   │   ├── top-bar.tsx
│   │   └── page-container.tsx
│   ├── dashboard/                # Dashboard-specific
│   │   ├── metric-card.tsx
│   │   ├── conversation-list.tsx
│   │   └── agent-status-board.tsx
│   └── shared/                   # Shared components
│       ├── language-toggle.tsx
│       ├── status-badge.tsx
│       └── avatar.tsx
├── lib/
│   ├── utils.ts                  # Utility functions
│   ├── constants.ts              # App constants
│   └── hooks/                    # Custom hooks
│       ├── use-chat.ts
│       ├── use-realtime.ts
│       └── use-keyboard.ts
├── stores/                       # State management
│   ├── chat-store.ts
│   └── ui-store.ts
├── types/                        # TypeScript types
│   ├── chat.ts
│   ├── user.ts
│   └── api.ts
└── styles/
    └── globals.css               # Design tokens + global styles
```

### 11.4 Performance Considerations

- **Message virtualization:** Use `react-window` or `@tanstack/virtual` for chat lists with 100+ messages
- **Lazy loading:** Sidebar panels, emoji picker, file upload component
- **Image optimization:** Next.js `<Image>` for all user avatars and file previews
- **Bundle splitting:** Chat widget should be a separate bundle from dashboard
- **Debounced search:** 300ms debounce on search inputs
- **Optimistic updates:** Messages, reactions, status changes — apply immediately, reconcile on server response

---

## Appendix A: Design Decision Rationale

| Decision | Rationale |
|----------|-----------|
| Blue as primary | Corporate trust, cooperative identity, industry standard for financial/service platforms |
| Inter font | Best readability at small sizes, supports Malay characters, SaaS industry standard |
| 14px base font size | Optimal for data-dense interfaces, reduces eye strain for agents using 8+ hours/day |
| 4px spacing grid | Consistent rhythm, easy mental math, aligns with Tailwind's default scale |
| White sidebar (not dark) | Lighter feel, more approachable, easier to maintain contrast |
| Mobile-first | 60%+ of customer interactions expected on mobile devices |
| Optimistic UI for messages | Feels instant, reduces perceived latency, critical for chat UX |
| shadcn/ui over MUI/Chakra | Better accessibility, smaller bundle, more customizable, Tailwind-native |

## Appendix B: Color Contrast Verification

| Combination | Ratio | WCAG Level | Status |
|-------------|-------|------------|--------|
| Gray-950 on White | 18.1:1 | AAA | ✅ |
| Gray-800 on White | 10.4:1 | AAA | ✅ |
| Gray-600 on White | 5.7:1 | AA | ✅ |
| White on Blue-700 | 4.8:1 | AA | ✅ |
| White on Blue-800 | 6.4:1 | AA | ✅ |
| Gray-600 on Gray-50 | 5.4:1 | AA | ✅ |
| Blue-700 on Blue-50 | 4.6:1 | AA | ✅ |
| Success-700 on Success-50 | 5.8:1 | AA | ✅ |
| Error-700 on Error-50 | 6.1:1 | AA | ✅ |

---

*This design plan serves as the authoritative reference for the PutraKop Live Chat System. All design and development decisions should reference this document for consistency. Updates should be versioned and communicated to the full team.*
