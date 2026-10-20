PROMPT FINAL ANTIGRAVITY
# ============================================================
# FINAL UI/UX + FUNCTIONAL REVISION
# MOBILE WEB ONLY
# MENU → JELAJAH → AKUN → menuDelete.account
# ============================================================

ROLE:
Act as a Senior Vue 3 + Laravel + Mobile UI/UX + Security +
Realtime + PWA engineer.

===============================================================
1. ABSOLUTE SCOPE LOCK
===============================================================

TASK INI HANYA UNTUK:

Mobile Web
→ Menu / Jelajahi
→ Akun
→ Delete Account
→ target page: menuDelete.account

DO NOT redesign or modify unrelated pages.

DO NOT change:

- Desktop UI
- Dashboard
- Shipment
- Tracking
- Tariff
- Reports
- Security
- Cache
- Storage
- Login
- Navbar
- Bottom Navigation
- Profile Photo System
- Existing shared components unless absolutely required
- Global CSS unless absolutely required

DO NOT perform global refactoring.

DO NOT create unnecessary files.

If a bug is found outside this page:
DO NOT fix it in this task.

===============================================================
2. CURRENT PAGE
===============================================================

The current Delete Account page is functional but visually too long,
fragmented, and not sufficiently premium.

The redesign must be a complete visual restructuring of:

menuDelete.account

while preserving all existing business/security behavior that already works.

===============================================================
3. FINAL DESIGN CONCEPT
===============================================================

Create:

"Premium Account Deletion Center"

Characteristics:

- premium
- elegant
- serious
- secure
- modern
- sophisticated
- compact
- mobile-first
- highly responsive
- clean hierarchy
- realistic enterprise application
- not AI-template looking

===============================================================
4. PAGE HEADER
===============================================================

Header:

← Delete Account

Subtitle:

"Manage permanent account deletion."

IMPORTANT:

The BACK action must return to:

Menu / Jelajahi / Akun

The back control must be in the header.

DO NOT create a large back button at the bottom.

Use existing navigation mechanism whenever possible.

Icon:

ArrowLeft

from Lucide.

===============================================================
5. TITLE
===============================================================

Use UI text:

"Delete Account"

Do not use:

"Delect Account"

"Delet Account"

Even if the technical route/component remains:

menuDelete.account

UI copy must be professional English.

===============================================================
6. PREMIUM BACKGROUND
===============================================================

Create a semi-circle background at the upper section.

It must reach the full left and right edge of the mobile viewport.

NO WHITE SIDE STRIPS.

Shape:

large half-circle / half-ellipse.

Colors:

Deep Navy
→ Royal Blue
→ Indigo
→ subtle Deep Purple

Use a soft gradient.

===============================================================
7. DUAL PATTERN
===============================================================

Inside the upper colored area create TWO distinct patterns.

PATTERN A:
smooth curved horizontal arcs.

PATTERN B:
opposing curved/diagonal arcs.

The two patterns MUST:

- have different directions
- be visually distinguishable
- overlap subtly
- have low opacity
- look premium
- not interfere with text readability

Do NOT use:

- rough lines
- excessive lines
- sharp random scribbles
- particle animation
- excessive glow

===============================================================
8. DANGER HEADER
===============================================================

Display:

Delete Account

with:

TriangleAlert
or
Trash2

Use Lucide.

Description:

"This action is permanent and your account will no longer be
available for login."

Keep wording clear.

Do not exaggerate.

===============================================================
9. DANGER INFORMATION CARD
===============================================================

Create a compact warning card.

Structure:

[ warning icon ]

Permanent deletion

"Your account, active sessions, and access will be permanently removed
according to the application's deletion policy."

Do not make this card excessively tall.

Use subtle red accent.

===============================================================
10. REALTIME DATE/TIME
===============================================================

Display small metadata:

Day
Date
Month
Year
Time
Timezone

Example:

Thursday, 27 August 2026 • 11:56 WIB

The timestamp MUST come from the actual application/server time source.

DO NOT hardcode.

DO NOT generate random time.

If existing realtime/Pusher infrastructure is already used for time/state,
reuse it where appropriate.

===============================================================
11. DELETE REASON
===============================================================

REMOVE the existing multiple reason cards/radio items.

Do NOT use:

- radio card list
- multiple reason cards
- large selectable reason blocks

Use ONE textarea.

Label:

DELETION REASON

Placeholder:

"Explain why you want to delete your account..."

Properties:

- mobile-friendly
- 3–4 visible lines
- auto-grow within reasonable max height
- readable
- comfortable keyboard interaction
- no layout overflow

===============================================================
12. CHARACTER COUNTER
===============================================================

Display:

0 / 500

Update realtime as user types.

Maximum:

500 characters

unless existing backend validation specifies another limit.

Frontend and backend rules MUST match.

===============================================================
13. ACKNOWLEDGEMENT CHECKBOX
===============================================================

Use PrimeVue Checkbox.

Use the following component style/architecture as the basis:

<template>
    <div class="flex items-center justify-center">
        <div class="flex items-center gap-2">
            <Checkbox v-model="checked" binary inputId="preview-checkbox" />
            <Label for="preview-checkbox">
                I accept the <a class="font-semibold">Terms of Service</a>
            </Label>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const checked = ref(false);
</script>

IMPORTANT:

Do NOT literally keep "Terms of Service".

Use the actual Delete Account acknowledgement.

Example:

"I understand that this action is permanent and cannot be undone."

The checkbox must be:

- PrimeVue Checkbox
- binary
- accessible
- properly associated with its label
- visually aligned
- mobile friendly

===============================================================
14. DELETE BUTTON
===============================================================

Primary action:

DELETE ACCOUNT

Use Lucide:

Trash2

Button states:

DISABLED
ACTIVE
LOADING

Before:

Reason valid
+
Checkbox checked

the button MUST remain disabled.

===============================================================
15. BUTTON DESIGN
===============================================================

Use:

- destructive red
- rounded
- full width within content container
- compact height
- strong contrast
- subtle press interaction

Do not make the button excessively large.

===============================================================
16. DELETE CONFIRMATION
===============================================================

Clicking:

DELETE ACCOUNT

must NOT immediately delete the account.

Open the existing confirmation modal/bottom sheet if available.

Do NOT create another modal system if the project already has one.

===============================================================
17. CONFIRMATION DESIGN
===============================================================

Use:

Delete Account Confirmation

Message:

"Are you sure you want to permanently delete this account?"

Show summary:

Reason:
[user entered reason]

Then:

PASSWORD

[ password field ]

Use Lucide:

LockKeyhole
Eye
EyeOff

Actions:

CANCEL

DELETE ACCOUNT

===============================================================
18. CONFIRMATION ANIMATION
===============================================================

Keep animation lightweight.

Bottom sheet:

translateY(100%)
→
translateY(0)

Backdrop:

opacity 0
→
opacity 1

Duration:

220–300ms

No bounce.

No excessive animation.

===============================================================
19. PASSWORD SECURITY
===============================================================

Password must be verified by backend.

NEVER:

- log plaintext password
- store plaintext password
- put password in localStorage
- send password through Pusher
- put password inside notification payload
- expose password in URL

===============================================================
20. DELETE FLOW
===============================================================

Final flow:

Reason
↓
Acknowledgement
↓
Delete Account
↓
Confirmation Bottom Sheet
↓
Password verification
↓
Backend transaction
↓
Deletion audit
↓
Revoke active sessions
↓
Invalidate authentication
↓
Delete account according to existing data policy
↓
Success
↓
Logout
↓
Login page

DO NOT:

- open new tab
- hard reload unnecessarily
- redirect to invalid route
- generate 404

===============================================================
21. PERMANENT LOGIN BLOCK
===============================================================

After deletion:

The deleted account MUST NOT be able to log in again.

Invalidate:

- sessions
- remember tokens
- authentication access

according to existing authentication architecture.

Do NOT automatically recreate the account.

===============================================================
22. AUDIT HISTORY
===============================================================

Keep deletion audit metadata.

Minimum:

- email/account identifier
- deleted_at
- reason
- audit identifier
- actor/operator if applicable

NEVER store:

- password
- plaintext credentials
- biometric secret
- session tokens

===============================================================
23. HISTORY MAIN PAGE
===============================================================

Show maximum:

5 newest deletion records.

Order:

Newest → Oldest

Do NOT display more than 5 in the main page.

===============================================================
24. ARCHIVE
===============================================================

When the sixth deletion record exists:

Move older records into archive.

Main page:

maximum 5 latest records.

Archive:

older records.

===============================================================
25. ARCHIVE ACCESS
===============================================================

Provide compact control:

[ ] View Archive

When activated:

open archive panel/modal.

Use existing modal system if available.

===============================================================
26. ARCHIVE POPUP
===============================================================

Popup title:

Deletion Archive

Each batch:

maximum 5 records.

Group each batch by actual date.

Example:

27 August 2026
────────────────

fitri*****@gmail.com
Reason...
11:56 WIB

andi*****@gmail.com
Reason...
09:14 WIB

────────────────

22 August 2026
────────────────

...

===============================================================
27. ARCHIVE SCROLL
===============================================================

The archive popup must have:

INTERNAL SCROLL.

DO NOT create browser/page scrollbar.

DO NOT create an additional website scrollbar.

The popup itself may scroll vertically when content exceeds its height.

===============================================================
28. EMAIL PRIVACY
===============================================================

Mask emails in the UI.

Example:

fitri*****@gmail.com

Do not expose full email unnecessarily.

Database may retain the required audit value according to the existing policy.

===============================================================
29. EMPTY STATE
===============================================================

If no deletion history exists:

Use:

ArchiveX
or
ShieldAlert

Text:

"No account deletion history."

Keep it compact and elegant.

===============================================================
30. REALTIME HISTORY
===============================================================

If existing Pusher/realtime infrastructure supports audit updates:

reuse it.

New deletion:

history updates without page refresh.

Do NOT create polling.

Do NOT create duplicate channels.

===============================================================
31. LOADING POPUP
===============================================================

When permanent deletion is processing:

Open lightweight processing state.

Example:

[ subtle spinner ]

Deleting account securely...

or status based on actual backend process.

Do NOT use fake percentages.

Do NOT use random delay.

Do NOT force long artificial loading.

===============================================================
32. SUCCESS STATE
===============================================================

After successful deletion:

Show:

Account successfully deleted.

Then:

invalidate session
↓
logout
↓
existing login page

No new tab.

No unnecessary hard reload.

===============================================================
33. ERROR STATE
===============================================================

If deletion fails:

Stay on current page.

Do not pretend deletion succeeded.

Do not clear data unnecessarily.

Display appropriate error.

Examples:

Incorrect password.

Unable to delete account.

Please try again.

===============================================================
34. MOBILE KEYBOARD UX
===============================================================

When textarea is focused:

keyboard must not hide primary controls unnecessarily.

When password confirmation opens:

password field must remain accessible.

Use safe viewport handling.

===============================================================
35. RESPONSIVE
===============================================================

Test:

320px
360px
375px
390px
414px
430px
480px

Must have:

- no horizontal overflow
- no clipping
- no overlapping
- no broken text
- no button overflow
- no popup overflow

===============================================================
36. I18N
===============================================================

All UI strings must use existing global i18n.

Examples:

accountDelete.title
accountDelete.description
accountDelete.warning
accountDelete.reason
accountDelete.reasonPlaceholder
accountDelete.characterCount
accountDelete.acknowledgement
accountDelete.delete
accountDelete.cancel
accountDelete.confirm
accountDelete.password
accountDelete.processing
accountDelete.success
accountDelete.error
accountDelete.history
accountDelete.archive
accountDelete.empty

Do NOT hardcode text.

===============================================================
37. VISUAL DESIGN TARGET
===============================================================

Target structure:

┌───────────────────────────────────────────┐
│ ← Delete Account                       │
├───────────────────────────────────────────┤
│                                           │
│       BLUE / INDIGO SEMI-CIRCLE           │
│      ╭──────────────────────────╮         │
│      │ curved pattern            │         │
│      │ opposing curved pattern   │         │
│      ╰──────────────────────────╯         │
│                                           │
│  🗑 Delete Account                         │
│     Manage permanent account deletion.    │
│                                           │
│  ┌─────────────────────────────────────┐  │
│  │ ⚠ Permanent deletion                │  │
│  │ This action cannot be undone.       │  │
│  └─────────────────────────────────────┘  │
│                                           │
│  THURSDAY, 27 AUGUST 2026 • 11:56 WIB    │
│                                           │
│  DELETION REASON                         │
│  ┌─────────────────────────────────────┐  │
│  │ Explain why you want to delete...  │  │
│  │                                     │  │
│  │                                     │  │
│  └─────────────────────────────────────┘  │
│                              0 / 500      │
│                                           │
│  ☐ I understand this action is permanent. │
│                                           │
│  ┌─────────────────────────────────────┐  │
│  │       🗑 DELETE ACCOUNT             │  │
│  └─────────────────────────────────────┘  │
│                                           │
│  DELETION HISTORY                         │
│  [ ] VIEW ARCHIVE                        │
└───────────────────────────────────────────┘

===============================================================
38. DATA INTEGRITY
===============================================================

The UI must reflect actual backend state.

Do not use:

- fake deletion history
- fake timestamps
- fake loading
- fake deleted state

===============================================================
39. PERFORMANCE
===============================================================

Keep this page lightweight.

Do not add heavy libraries.

Reuse existing:

- modal/bottom-sheet
- auth service
- user store
- API service
- notification service
- Pusher
- i18n
- PrimeVue
- Lucide

Do not create duplicate implementations.

===============================================================
40. FILE MINIMALITY
===============================================================

IMPORTANT:

DO NOT CREATE FILES THAT ARE NOT NECESSARY.

First inspect existing architecture.

Reuse existing files/components/services.

Create a new file only when there is a real architectural reason.

Do not create:

- duplicate deletion service
- duplicate modal service
- duplicate history service
- duplicate auth service
- duplicate notification service

===============================================================
41. BUG FIX
===============================================================

Fix only bugs directly related to:

- menuDelete.account
- deletion form
- checkbox
- delete button
- confirmation
- password verification
- deletion transaction
- session revoke
- history
- archive
- realtime timestamp
- popup scrolling
- loading state

Do NOT fix unrelated application issues.

===============================================================
42. FINAL VALIDATION
===============================================================

[ ] Back returns to Menu/Jelajahi
[ ] Header correct
[ ] Semi-circle background full width
[ ] No white side strips
[ ] Two different patterns visible
[ ] UI compact
[ ] UI responsive
[ ] Realtime date/time
[ ] Reason is textarea
[ ] Character counter works
[ ] PrimeVue Checkbox works
[ ] Delete disabled initially
[ ] Delete enabled only when valid
[ ] Confirmation works
[ ] Password validation works
[ ] Loading works
[ ] Permanent deletion works
[ ] Sessions revoked
[ ] Deleted account cannot login
[ ] Audit record stored
[ ] Main history max 5
[ ] Older records archived
[ ] Archive popup works
[ ] Archive internal scroll works
[ ] No browser/page scrollbar caused by archive
[ ] No fake data
[ ] No 404
[ ] No duplicate request
[ ] No console errors
[ ] No unnecessary files
[ ] No unrelated pages changed

===============================================================
43. FINAL SCOPE AUDIT
===============================================================

Before finishing:

Inspect git diff.

Every changed file must be justified.

If a changed file is unrelated:
REVERT it.

If a new file is unnecessary:
DELETE it.

If an unrelated page changed:
REVERT it.

STOP after all acceptance criteria pass.

DO NOT continue with additional improvements.

===============================================================
44. DEFINITION OF DONE
===============================================================

The task is COMPLETE only when:

- menuDelete.account has the new premium mobile-first UI
- reason uses textarea
- PrimeVue Checkbox is implemented
- realtime time/date works
- confirmation flow works
- permanent deletion works
- account cannot login after deletion
- history limit = 5
- archive works
- archive scrolling is internal
- loading animation works
- all relevant text uses i18n
- no unnecessary files are created
- no unrelated UI changes occur
- no new errors exist

STOP.

Urutan pengerjaan
STEP 01 — INSPEKSI
Cari:
- menuDelete.account
- existing delete logic
- auth/session
- history
- archive
- existing modal
- PrimeVue
- Lucide
- i18n
- Pusher

Jangan edit dahulu.

STEP 02 — LOCK SCOPE
Tetapkan hanya menuDelete.account.

STEP 03 — AUDIT EXISTING BUSINESS LOGIC
Jangan membuat ulang logic yang sudah benar.

STEP 04 — REDESIGN UI
Buat ulang layout:
Header
→ Semi-circle
→ Danger Card
→ Date/Time
→ Reason
→ Checkbox
→ Delete Button
→ History

STEP 05 — PRIMEVUE CHECKBOX
Ganti checkbox lama dengan PrimeVue Checkbox.

STEP 06 — REASON
Hapus reason radio/card list.
Gunakan satu textarea.

STEP 07 — DATE/TIME
Gunakan actual application/server time.

STEP 08 — DELETE BUTTON
Implement valid/disabled/loading states.

STEP 09 — CONFIRMATION
Reuse existing bottom-sheet/modal.

STEP 10 — PASSWORD
Connect existing backend verification.

STEP 11 — DELETE TRANSACTION
Ensure permanent deletion + session revoke.

STEP 12 — HISTORY
Keep latest 5 only.

STEP 13 — ARCHIVE
Move older records into archive batches.

STEP 14 — ARCHIVE POPUP
Internal scrolling only.

STEP 15 — REALTIME
Reuse Pusher if applicable.

STEP 16 — I18N
Ensure all page text uses translation keys.

STEP 17 — MOBILE QA
Test:
320
360
375
390
414
430
480

STEP 18 — SECURITY QA
Test:
wrong password
correct password
double click
failure
success
login after deletion

STEP 19 — ERROR CHECK
Console
Network
Promise
Routing

STEP 20 — FILE AUDIT
Check git diff.

Remove unnecessary new files.

STEP 21 — SCOPE AUDIT
Only menuDelete.account should have visual changes.

STEP 22 — STOP.

Visual target paling ringkas
             ┌─────────────────────┐
             │ ← Delete Account    │
             ├─────────────────────┤
             │   SEMI-CIRCLE BG    │
             │  ╭───────────────╮  │
             │  │ curved lines  │  │
             │  │ opposite lines│  │
             │  ╰───────────────╯  │
             │                     │
             │ 🗑 Delete Account   │
             │                     │
             │ ⚠ Permanent Action  │
             │                     │
             │ DATE • TIME         │
             │                     │
             │ DELETION REASON     │
             │ ┌─────────────────┐ │
             │ │ Type reason...  │ │
             │ │                 │ │
             │ └─────────────────┘ │
             │              0/500  │
             │                     │
             │ ☐ I understand...  │
             │                     │
             │ [ DELETE ACCOUNT ]  │
             │                     │
             │ DELETION HISTORY    │
             │ [ View Archive ]    │
             └─────────────────────┘