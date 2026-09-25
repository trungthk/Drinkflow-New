# DrinkFlow SaaS Extension — Development Tasks

> Tài liệu triển khai tuần tự cho AI Agent. Mỗi task phải hoàn thành, chạy test và xử lý lỗi trước khi sang task kế tiếp.

## Architecture Target

```text
DrinkFlow Platform
├── Superadmins → Permissions + Scope → manage one/many Agents
├── Admins / Agents → Registration → Package → Subscription → Billing → Rooms
├── Rooms → Campaigns → Orders → Room Users
└── Global Users
```

**Financial boundary**

```text
Room Finance: User / Sponsor / Campaign → Admin quản lý
Platform Finance: Package / Subscription / Invoice / Agent Payment → Superadmin quản lý
```

Không dùng chung debt/payment logic giữa hai domain.

## Quy tắc thực hiện

```text
Read existing code → Analyze impact → Implement → Authorization
→ Automated Tests → Run Tests → Fix Failures → Summarize → STOP
```

AI Agent không tự chuyển task nếu test/migration/authorization chưa hoàn chỉnh.


# PHASE 1 — Account & Authorization Foundation

## T01 — Tách Admin và Superadmin

**Goal / Requirements**

Tạo model/table `admins`, `superadmins` riêng. Admin status: pending/active/suspended/rejected/cancelled. Preserve existing data.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T02 — Auth Guards

**Goal / Requirements**

Tạo guard `admin` cho `/admin/*` và `superadmin` cho `/superadmin/*`; test cross-guard.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T03 — Migration dữ liệu cũ

**Goal / Requirements**

Migrate account cũ sang architecture mới; không duplicate/mất dữ liệu; có rollback strategy.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T04 — Permission Schema

**Goal / Requirements**

Tạo `permissions`, `superadmin_permissions`; permissions cho agent/package/subscription/revenue/debt/room/global_user/feedback/version/settings/security/audit/queue/superadmin.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T05 — Gate + Policy + Permission Scope

**Goal / Requirements**

Laravel Gate cho feature, Policy cho resource, Query Scope cho visibility. Scope hỗ trợ `all` và `managed`. Authorization server-side bắt buộc.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T06 — Superadmin Management

**Goal / Requirements**

CRUD Superadmin + gán permission/scope. Không cho disable/demote Superadmin cuối cùng.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T07 — Superadmin ↔ Agent

**Goal / Requirements**

Tạo `superadmin_admins(superadmin_id, admin_id, is_primary, assigned_at, assigned_by, ...)`.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T08 — Agent visibleTo()

**Goal / Requirements**

Implement `Admin::visibleTo($superadmin)`: all → tất cả, managed → assigned Agents, không quyền → 403. Dùng cả cho aggregate.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 2 — Package & Admin Registration

## T09 — Package Schema

**Goal / Requirements**

Tạo `packages`: code, name, description, monthly_price, room_limit, status, sort_order. Không hard-code package.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T10 — Package Management

**Goal / Requirements**

CRUD `/superadmin/packages`; không hard delete package đã có subscription.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T11 — Public Admin Registration

**Goal / Requirements**

`/admin/register`; name/email/phone/company/password/package; submit → pending.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T12 — Admin Email Verification

**Goal / Requirements**

Verify email trước approval/activation theo workflow.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T13 — Package Selection

**Goal / Requirements**

Admin chọn package lúc đăng ký; chỉ lưu requested package, chưa cấp quota.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T14 — Pending Registration

**Goal / Requirements**

`/superadmin/agents/registrations`; list/review pending Agent.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T15 — Approve / Reject

**Goal / Requirements**

Approve trong DB transaction: lock Admin, validate pending/package, init subscription, activate Admin, assign manager nếu cần. Reject lưu reason.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T16 — Approval Email

**Goal / Requirements**

Queue `AdminApproved`/`AdminRejected`; gửi mail sau transaction.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T17 — Subscription Initialization

**Goal / Requirements**

Tạo `admin_subscriptions`: admin_id, package_id, status, price_snapshot, room_limit_snapshot, starts_at, expires_at, approved_by_superadmin_id.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T18 — Package Snapshot

**Goal / Requirements**

Snapshot price/room_limit lúc activate; thay đổi package sau này không silently đổi subscription cũ.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 3 — Agent Room Ownership

## T19 — Room Ownership

**Goal / Requirements**

Thêm `rooms.owner_admin_id`; Admin trở thành owner.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T20 — Migrate Existing Ownership

**Goal / Requirements**

Map Room cũ về Admin đúng; nếu không đủ dữ liệu thì report unresolved, không tự đoán.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T21 — Admin Room CRUD

**Goal / Requirements**

Admin create/edit/archive/restore Room của mình.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T22 — Room Quota Service

**Goal / Requirements**

Quota lấy từ active subscription `room_limit_snapshot`.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T23 — Room Usage UI

**Goal / Requirements**

Hiển thị `Rooms X / Limit` và trạng thái quota.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T24 — Server-side Room Limit

**Goal / Requirements**

Backend reject create Room nếu vượt quota; frontend disable chỉ là UX.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T25 — Archive & Quota Rule

**Goal / Requirements**

active + disabled tính quota; archived/soft-deleted không tính.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T26 — Room Authorization Refactor

**Goal / Requirements**

Update Policy/query/controller/routes/Blade/realtime để chống cross-Agent Room access.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 4 — Subscription Management

## T27 — Admin Subscription Page

**Goal / Requirements**

`/admin/subscription`: current package, price, room usage, status, dates.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T28 — Subscription & Quota UI

**Goal / Requirements**

Hiển thị package/quota/current usage rõ ràng.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T29 — Package Upgrade

**Goal / Requirements**

Implement upgrade và giữ subscription history.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T30 — Package Downgrade

**Goal / Requirements**

Validate Room quota; không downgrade nếu số Room vượt target limit.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T31 — Subscription History

**Goal / Requirements**

Admin/Superadmin xem lịch sử theo permission.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T32 — Subscription Lifecycle

**Goal / Requirements**

Hỗ trợ active/suspended/expired/cancelled qua Service/Action.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T33 — Access Enforcement

**Goal / Requirements**

Enforce subscription status; không silently delete Room/business data.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 5 — Platform Billing

## T34 — Admin Invoice Schema

**Goal / Requirements**

Tạo `admin_invoices`: invoice_number, admin/subscription, billing period, subtotal/discount/total/paid/remaining, status, due_at, paid_at.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T35 — Admin Payments

**Goal / Requirements**

Tạo `admin_payments` riêng cho Agent → Platform; không reuse Room payments.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T36 — Monthly Invoice Service

**Goal / Requirements**

Service `GenerateMonthlyAdminInvoices` tính theo subscription snapshot.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T37 — Billing Scheduler

**Goal / Requirements**

Scheduler tạo missing invoice và cập nhật trạng thái.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T38 — Billing Idempotency

**Goal / Requirements**

Unique theo subscription + billing period; scheduler chạy lại không duplicate.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T39 — Overdue Processing

**Goal / Requirements**

Invoice quá due_at và còn outstanding → overdue.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T40 — Superadmin Revenue Dashboard

**Goal / Requirements**

`/superadmin/revenue`: billed, collected, outstanding, paid/free Agents. Chỉ Platform Revenue.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T41 — Agent Billing Detail

**Goal / Requirements**

`/superadmin/agents/{admin}/billing`: package, invoices, payments, outstanding.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T42 — Manual Mark Paid

**Goal / Requirements**

Superadmin có quyền ghi nhận amount/date/method/reference/note; phải audit.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T43 — Outstanding Management

**Goal / Requirements**

Filter issued/partially_paid/overdue/paid; respect all/managed scope.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T44 — Admin Billing History

**Goal / Requirements**

`/admin/billing`; Admin chỉ thấy invoice/payment của chính mình.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 6 — Superadmin Agent Management

## T45 — Agent List

**Goal / Requirements**

`/superadmin/agents`; enforce `agent.view + scope`.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T46 — Agent Detail

**Goal / Requirements**

Tabs Overview/Subscription/Rooms/Campaigns/Revenue-Billing/Activity/Audit.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T47 — Agent Subscription Tab

**Goal / Requirements**

Current package/status/room limit/price snapshot/history.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T48 — Agent Rooms Tab

**Goal / Requirements**

Chỉ Rooms thuộc Agent.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T49 — Agent Campaigns Tab

**Goal / Requirements**

Campaigns thuộc Rooms Agent; không leak Agent khác.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T50 — Agent Revenue/Billing Tab

**Goal / Requirements**

Chỉ Platform Billing; không trộn Room revenue.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T51 — Agent Activity & Audit

**Goal / Requirements**

Agent-related activity/audit theo permission.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T52 — Assign / Unassign Agent

**Goal / Requirements**

Manage Superadmin-Agent assignment; mọi thay đổi audit.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T53 — Suspend / Reactivate Agent

**Goal / Requirements**

Không hard delete Rooms/Campaigns/Orders/Global Users.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T54 — Apply all/managed Everywhere

**Goal / Requirements**

Áp dụng Agent/Room/Campaign/Revenue/Billing/Dashboard/Reports.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 7 — Scoped Superadmin Dashboard

## T55 — Permission-aware Dashboard

**Goal / Requirements**

Chỉ render/query module được phép.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T56 — Agent Metrics

**Goal / Requirements**

Total/Active/Pending/Suspended theo scope.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T57 — Revenue Metrics

**Goal / Requirements**

Monthly billed/collected/outstanding theo `revenue.view` scope.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T58 — Room Metrics

**Goal / Requirements**

Room metrics chỉ tính Agents visible.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T59 — Campaign Metrics

**Goal / Requirements**

Campaign metrics kế thừa Agent → Room scope.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T60 — Pending Registration Widget

**Goal / Requirements**

Chỉ hiện nếu có quyền view/approve.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T61 — System/Security Widgets

**Goal / Requirements**

Global widgets chỉ hiện khi có permission.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 8 — Audit & Security

## T62 — Audit Log Service

**Goal / Requirements**

Chuẩn hóa actor/action/target/before/after/metadata/ip/timestamp; không lưu secret.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T63 — Registration Audit

**Goal / Requirements**

admin.registered/approved/rejected/suspended/reactivated.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T64 — Package & Subscription Audit

**Goal / Requirements**

package created/updated/disabled; subscription created/upgraded/downgraded/suspended/cancelled.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T65 — Invoice & Payment Audit

**Goal / Requirements**

invoice generated/overdue/cancelled; payment recorded/updated.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T66 — Permission Audit

**Goal / Requirements**

Lưu who/target/before/after/timestamp cho thay đổi permission.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T67 — Agent Assignment Audit

**Goal / Requirements**

Audit assigned/unassigned.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T68 — Horizontal Privilege Tests

**Goal / Requirements**

Managed Superadmin không truy cập Agent khác qua URL/API.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T69 — Cross-Agent / Cross-Room Tests

**Goal / Requirements**

Admin A không view/update Room/Campaign/Order của Admin B.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T70 — Permission Scope Isolation Tests

**Goal / Requirements**

Test all/managed/none cho Agent/Room/Campaign/Revenue/Billing/Dashboard/Reports.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# PHASE 9 — Optional Future Improvements

## T71 — Superadmin Roles

**Goal / Requirements**

Chuẩn bị roles/role_permissions/superadmin_roles; role + user overrides.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T72 — Subscription Grace Period

**Goal / Requirements**

Cấu hình grace_period_days; không disable ngay khi invoice overdue.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T73 — Automatic Suspension

**Goal / Requirements**

Overdue → grace period → notification → suspension policy; định nghĩa Live Campaign trước.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.


## T74 — Online Payment Integration

**Goal / Requirements**

Sau MVP mới tích hợp VietQR/bank webhook/payment gateway.

**Definition of Done**

- Business rule của task hoạt động đúng.
- Authorization server-side hoàn chỉnh nếu task có protected resource.
- Migration/schema an toàn nếu có thay đổi DB.
- Có automated tests phù hợp.
- Relevant tests pass.
- Không regression chức năng hiện tại.
- AI Agent report changed files, migrations, routes, tests và unresolved issues rồi **STOP**.



# Database Target

```text
admins
superadmins
superadmin_admins
permissions
superadmin_permissions
packages
admin_subscriptions
admin_invoices
admin_payments
rooms
```

```text
superadmins
      │
      ▼
superadmin_admins
      │
      ▼
admins
      ├──────────────┐
      ▼              ▼
admin_subscriptions  rooms
      │              │
      ▼              ▼
packages          campaigns
      │              │
      ▼              ▼
admin_invoices     orders
      │
      ▼
admin_payments
```

# Ownership Rules

```text
rooms.owner_admin_id
→ ownership + subscription + quota
```

Nếu sau này có collaborator:

```text
room_admins
→ operational access
```

Không dùng collaborator assignment để xác định billing ownership.

# Authorization Rules

```text
PLATFORM       → Superadmin
AGENT TENANT   → Admin
ROOM TENANT    → Room
END USER       → Global User / Room User
```

Superadmin resource access:

```text
Permission + Scope + Agent Assignment
```

Admin resource access:

```text
Authenticated Admin + Room Ownership/Authorized Collaboration
```

# Navigation Target

## Admin

```text
Dashboard
ROOMS
  Rooms
  + Create Room
CAMPAIGNS
  Campaigns
OPERATIONS
  Orders
  Payments & Debt
  Users
ANALYTICS
  Reports
ACCOUNT
  Subscription
  Package
  Billing History
SETTINGS
  Profile
Logout
```

## Superadmin

```text
Dashboard
AGENTS
  Agents
  Pending Registrations
  Subscriptions
PLANS
  Packages
PLATFORM FINANCE
  Revenue
  Outstanding Invoices
SYSTEM
  Rooms
  Global Users
  Campaigns
  Feedback
CONTENT
  Versions
  Notifications
GOVERNANCE
  Superadmins / Permissions
  Audit Logs
  Security
INFRASTRUCTURE
  Queue
  System Health
  Maintenance
Settings
Logout
```

Superadmin menu phải respect Laravel Gate.

# AI Agent Task Template

```text
TASK: Txx - <Task Name>

GOAL
<kết quả cần đạt>

DEPENDENCIES
<các task phải hoàn thành>

CONTEXT
- Đọc implementation hiện tại trước khi thay đổi.
- Reuse architecture/service/model hiện có nếu phù hợp.

REQUIREMENTS
- ...

DATABASE
- Migration/schema/index/foreign key.
- Không làm mất dữ liệu hiện tại.

BACKEND
- Model / Enum
- FormRequest
- Action / Service
- Controller
- Events / Listeners / Jobs nếu cần

AUTHORIZATION
- Laravel Gate
- Policy
- Query Scope
- Server-side authorization bắt buộc
- Không dựa vào hidden UI

FRONTEND
- Blade
- Blade Components
- TailwindCSS
- Alpine.js chỉ cho interaction nhỏ
- Không tạo SPA

SECURITY
- Không expose secrets
- Không cross-Agent
- Không cross-Room
- Validate ownership
- Validate permission + scope

TESTS
- Feature tests
- Authorization tests
- Validation tests
- Business rule tests

IMPLEMENTATION
1. Analyze current implementation.
2. Report impacted modules.
3. Implement migration/schema.
4. Implement models/enums.
5. Implement services/actions.
6. Implement requests/controllers.
7. Implement authorization.
8. Implement Blade UI.
9. Add automated tests.
10. Run tests.
11. Fix failures.

DO NOT
- Không refactor ngoài phạm vi task nếu không cần.
- Không hard-code package/permission.
- Không bỏ authorization để làm test pass.
- Không duplicate business logic.
- Không expose secrets.
- Không tự thay đổi business requirements.

FINISH
1. Run relevant tests.
2. Report changed files.
3. Report migrations.
4. Report routes.
5. Report tests added.
6. Report unresolved issues.
7. STOP.
8. Chờ task tiếp theo.
```

# Execution Order

```text
T01 → T08
↓ CHECKPOINT
T09 → T18
↓ CHECKPOINT
T19 → T26
↓ CHECKPOINT
T27 → T33
↓ CHECKPOINT
T34 → T44
↓ CHECKPOINT
T45 → T54
↓ CHECKPOINT
T55 → T61
↓ CHECKPOINT
T62 → T70
↓ PRODUCTION READINESS REVIEW
T71 → T74 OPTIONAL
```

Không chạy song song các task có dependency hoặc cùng chỉnh một domain/schema quan trọng.

# Checkpoint Rules

Sau mỗi Phase:

```text
Migrations              ✓
Feature Tests           ✓
Authorization Tests     ✓
Existing Tests          ✓
No Cross-Agent Leak     ✓
No Cross-Room Leak      ✓
No Secret Exposure      ✓
```

Nếu fail:

```text
STOP → Fix → Run Tests Again
```

# Definition of Done

Task chỉ DONE khi:

- Migration chạy thành công.
- Business logic hoàn chỉnh.
- Authorization server-side hoàn chỉnh.
- UI cần thiết hoạt động.
- Automated tests được bổ sung.
- Relevant tests pass.
- Không regression đã biết.
- Không security issue đã biết.
- Report đầy đủ changed files/migrations/routes/tests.
- Không còn unresolved blocker.

# MVP Scope

```text
Separate Admin / Superadmin
Separate Auth Guards
Superadmin Permissions
Permission Scope all / managed
Agent Assignment
Packages
Admin Registration
Email Verification
Pending Approval
Approve / Reject
Subscription Initialization
Room Ownership
Room Quota
Subscription Management
Platform Invoice
Platform Payment
Platform Revenue
Outstanding Invoice
Agent Management
Scoped Dashboard
Audit Logs
Security Tests
```

Optional:

```text
Superadmin Roles
Grace Period Automation
Auto Suspension
Online Payment Gateway
Advanced Billing
Advanced Security Analytics
```

# Final Architecture Principles

```text
SUPERADMIN
→ Platform Governance
→ Agent Management
→ Package / Subscription
→ Platform Revenue / Debt
→ Permission + Scope

ADMIN / AGENT
→ SaaS Customer
→ Own Rooms
→ Room Quota
→ Campaign Operations
→ Room Finance

ROOM
→ Campaign → Order → Sponsor → Room User

GLOBAL USER
→ End User Identity
```

Luôn giữ:

```text
Platform Billing != Room Finance
Permission != Visibility Scope
UI Restriction != Authorization
Package Configuration != Hard-coded Business Rule
```
