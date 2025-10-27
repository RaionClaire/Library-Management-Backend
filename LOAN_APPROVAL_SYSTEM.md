# Loan Approval System Documentation

## Overview
The library management system now uses an approval workflow for member loan requests. Members propose loans, admins review and approve/reject them.

## Workflow

```
Member Proposes Loan
        ↓
   Status: PENDING
        ↓
    Admin Reviews
        ↓
    ┌─────────┴─────────┐
    ↓                   ↓
APPROVE              REJECT
    ↓                   ↓
Status: BORROWED    Status: REJECTED
(loaned_at set)     (with reason)
(due_at = +7 days)
```

## Loan Statuses

| Status | Description | Who Can Set |
|--------|-------------|-------------|
| `pending` | Loan request awaiting admin approval | Member (via propose) |
| `approved` | Admin approved, waiting for pickup | Admin (deprecated - now goes directly to borrowed) |
| `borrowed` | Book is currently loaned out | Admin (approve) |
| `rejected` | Loan request denied | Admin (reject) |
| `returned` | Book has been returned | Admin (return) |
| `overdue` | Loan is past due date | System (auto) |

## Database Changes

### New Columns in `loans` table:
- `loaned_at` - **nullable** (set when approved)
- `due_at` - **nullable** (calculated when approved: loaned_at + 7 days)
- `notes` - Text field for member notes or admin rejection reason
- `approved_by` - Foreign key to `users` table (admin who approved/rejected)
- `approved_at` - Timestamp when admin took action

## API Endpoints

### Member Routes (`/api/member/loans`)

#### 1. Propose a Loan
```http
POST /api/member/loans
Authorization: Bearer {member_token}
Content-Type: application/json

{
    "book_id": 1,
    "notes": "Need this for research project"  // optional
}
```

**Success Response (201):**
```json
{
    "message": "Loan request submitted successfully. Waiting for admin approval.",
    "loan": {
        "id": 1,
        "member_id": 1,
        "book_id": 1,
        "status": "pending",
        "notes": "Need this for research project",
        "loaned_at": null,
        "due_at": null,
        "book": {...},
        "created_at": "2025-10-27T10:00:00.000000Z"
    }
}
```

**Error Cases:**
- Book not available (400)
- Already has pending/active loan for this book (400)
- Member profile not found (404)

#### 2. View Own Loans
```http
GET /api/member/loans
GET /api/member/loans?status=pending  // Filter by status
Authorization: Bearer {member_token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "status": "pending",
            "book": {...},
            "notes": "...",
            "created_at": "..."
        },
        {
            "id": 2,
            "status": "borrowed",
            "loaned_at": "2025-10-20",
            "due_at": "2025-10-27",
            "book": {...}
        }
    ],
    "total": 2
}
```

### Admin Routes (`/api/admin/loans`)

#### 1. View All Pending Requests
```http
GET /api/admin/loans/pending/all
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "pending_loans": [
        {
            "id": 1,
            "member": {
                "id": 1,
                "user": {
                    "name": "John Doe",
                    "email": "john@example.com"
                },
                "code": "MBR000001"
            },
            "book": {
                "title": "Clean Code",
                "author": {...},
                "category": {...}
            },
            "notes": "Need for research",
            "created_at": "2025-10-27T10:00:00.000000Z"
        }
    ],
    "total": 1
}
```

#### 2. Approve Loan Request
```http
POST /api/admin/loans/{loan_id}/approve
Authorization: Bearer {admin_token}
```

**What Happens:**
- Status changes to `borrowed`
- `loaned_at` set to current date
- `due_at` set to loaned_at + 7 days
- `approved_by` set to current admin
- `approved_at` set to current timestamp

**Success Response:**
```json
{
    "message": "Loan request approved successfully",
    "loan": {
        "id": 1,
        "status": "borrowed",
        "loaned_at": "2025-10-27",
        "due_at": "2025-11-03",
        "approved_by": 2,
        "approved_at": "2025-10-27T14:30:00.000000Z",
        "approver": {
            "id": 2,
            "name": "Admin User"
        },
        "member": {...},
        "book": {...}
    }
}
```

**Error Cases:**
- Can only approve pending loans (400)
- Book no longer available (400)

#### 3. Reject Loan Request
```http
POST /api/admin/loans/{loan_id}/reject
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "reason": "Book reserved for another member"  // optional
}
```

**Success Response:**
```json
{
    "message": "Loan request rejected",
    "loan": {
        "id": 1,
        "status": "rejected",
        "notes": "Book reserved for another member",
        "approved_by": 2,
        "approved_at": "2025-10-27T14:30:00.000000Z",
        "approver": {
            "name": "Admin User"
        }
    }
}
```

#### 4. Direct Loan Creation (Skip Approval)
Admins can still create loans directly without approval process:

```http
POST /api/admin/loans
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "member_id": 1,
    "book_id": 1
}
```

This creates a loan with status `borrowed` immediately, bypassing the approval workflow.

#### 5. View All Loans with Filters
```http
GET /api/admin/loans
GET /api/admin/loans?status=pending
GET /api/admin/loans?status=borrowed
GET /api/admin/loans?status=rejected
Authorization: Bearer {admin_token}
```

## Book Availability Logic

A book copy is considered **unavailable** if it has a loan with status:
- `pending` - Someone requested it
- `approved` - Approved but not yet picked up
- `borrowed` - Currently loaned out
- `overdue` - Loaned and overdue

Available copies = `stock` - count of loans with above statuses

## Member Notifications

When a member's loan request is:
- **Approved**: They should be notified to pick up the book
- **Rejected**: They should be notified with the reason

(Note: Actual notification implementation can be added separately)

## Common Use Cases

### Scenario 1: Member Requests a Book
1. Member calls `POST /api/member/loans` with book_id
2. System creates loan with status `pending`
3. Admin sees it in `GET /api/admin/loans/pending/all`
4. Admin approves via `POST /api/admin/loans/{id}/approve`
5. Member can see status changed to `borrowed` in their loans

### Scenario 2: Admin Creates Loan Directly
1. Member comes to library desk
2. Admin calls `POST /api/admin/loans` with member_id and book_id
3. Loan created immediately with status `borrowed`
4. No approval needed

### Scenario 3: Book Not Available
1. Member requests book
2. System checks `availableCopies()`
3. If 0, returns error "Book is not available"
4. Member can check back later or request different book

## Migration Notes

If you have existing loans in the database, you may need to:
1. Set `approved_by` to a default admin user
2. Set `approved_at` to the loan's `created_at`
3. Ensure `loaned_at` and `due_at` are not null for borrowed loans

Run migration:
```bash
php artisan migrate:fresh --seed
```

## Testing the System

### Test as Member:
```bash
# 1. Register as member
POST /api/register {"role": "member", ...}

# 2. Propose a loan
POST /api/member/loans {"book_id": 1}

# 3. Check status
GET /api/member/loans
```

### Test as Admin:
```bash
# 1. Login as admin
POST /api/login {admin credentials}

# 2. View pending requests
GET /api/admin/loans/pending/all

# 3. Approve a request
POST /api/admin/loans/1/approve

# 4. Or reject it
POST /api/admin/loans/1/reject {"reason": "Out of stock"}
```

## Future Enhancements

- [ ] Email/push notifications when loan is approved/rejected
- [ ] Auto-reject if book remains unavailable after X days
- [ ] Priority queue for popular books
- [ ] Reservation system for books currently loaned out
- [ ] Bulk approve/reject for admins
- [ ] Member can cancel their pending requests
