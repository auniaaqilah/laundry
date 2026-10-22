# User Stories & Requirement Traceability

This document maps all user stories for the **Laundry** On-Demand Laundry & Dry-Cleaning Platform to their respective requirement IDs.

---

## 🧺 Core Functional User Stories

### US-01: Create and Update Laundry Orders
- **User Story ID:** US-01
- **Requirement:** `REQ-1`
- **As a:** Customer
- **I want to:** Create and modify my laundry service orders
- **So that:** I can select my desired laundry services, specify item counts, and set pickup schedules.
- **Acceptance Criteria:**
  - [ ] User can select laundry categories ( Wash & Fold, Dry Cleaning, Ironing).
  - [ ] User can specify item quantities and special instructions.
  - [ ] User can edit or cancel an order while the status is still `Pending`.

---

### US-02: Admin Order Tracking & Management
- **User Story ID:** US-02
- **Requirement:** `REQ-4`
- **As an:** Administrator / Laundry Staff
- **I want to:** View incoming customer orders and update their progress status
- **So that:** I can manage daily operations and keep customers updated in real time.
- **Acceptance Criteria:**
  - [ ] Admin dashboard lists all orders sorted by date and urgency.
  - [ ] Admin can update order status (`Pending` ➔ `In Wash` ➔ `Ready for Delivery` ➔ `Completed`).
  - [ ] Status updates reflect immediately on the customer’s dashboard.

---

### US-03: AI RAG/NLP Chatbot Assistant
- **User Story ID:** US-03
- **Requirement:** `REQ-1`, `REQ-4`
- **As a:** Customer
- **I want to:** Chat with an AI assistant about my order status, care instructions, and pricing
- **So that:** I can get immediate answers without navigating complex menus.
- **Acceptance Criteria:**
  - [ ] Chatbot accurately parses user natural language queries for order tracking.
  - [ ] Chatbot uses Retrieval-Augmented Generation (RAG) to query system knowledge base for laundry care policies.
  - [ ] Chatbot provides clear fallback messages if context is unavailable.

---

## ⚡ Non-Functional User Stories

### US-04: Dashboard Load Performance
- **User Story ID:** US-04
- **Requirement:** `REQ-5`
- **As a:** Customer / Admin
- **I want:** My user dashboard pages to load rapidly
- **So that:** I can access my active orders and system status without waiting.
- **Acceptance Criteria:**
  - [ ] Dashboard pages load in **< 2 seconds** under normal network conditions.

---

### US-05: Session Security & Authentication
- **User Story ID:** US-05
- **Requirement:** `REQ-6`
- **As a:** Registered User
- **I want:** My session data and credentials to be securely stored and authenticated
- **So that:** My personal data and laundry orders remain safe from unauthorized access.
- **Acceptance Criteria:**
  - [ ] Passwords are hashed before storage.
  - [ ] Unauthenticated requests to protected endpoints redirect to `/login`.

---

### US-06: Responsive UI Design
- **User Story ID:** US-06
- **Requirement:** `REQ-7`
- **As a:** Mobile / Desktop User
- **I want:** The web application layout to adapt smoothly to my device's screen size
- **So that:** I can easily manage my orders on both mobile phones and laptops.
- **Acceptance Criteria:**
  - [ ] UI layout adjusts cleanly across viewports from 320px (mobile) to 1024px+ (desktop).
  - [ ] All interactive components remain accessible and readable on touch displays.
