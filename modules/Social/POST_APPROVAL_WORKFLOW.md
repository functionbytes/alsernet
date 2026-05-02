# Post Approval Workflow

## Overview

The Post Approval Workflow enables multi-user teams to collaborate on social media content with a review and approval process before publication. This ensures quality control, brand consistency, and compliance with company policies.

## Features

### Core Functionality

1. **Submit for Review** - Content creators can submit draft posts for team review
2. **Approve Posts** - Reviewers can approve posts with optional notes
3. **Reject Posts** - Reviewers can reject posts with required feedback
4. **Return to Draft** - Creators can return rejected posts to draft for editing
5. **Bulk Operations** - Approve or reject multiple posts at once
6. **Email & Database Notifications** - All parties are notified of status changes
7. **Audit Trail** - Track who reviewed, when, and why (with notes)

### Approval Statuses

The workflow uses the `ApprovalStatus` enum with the following values:

- `DRAFT` - Initial state, not submitted for review
- `PENDING_REVIEW` - Submitted and awaiting reviewer action
- `APPROVED` - Approved and ready for publication
- `REJECTED` - Rejected with feedback, needs revision

## Database Schema

### Approval Fields on `social_posts` Table

```sql
approval_status ENUM('draft', 'pending_review', 'approved', 'rejected') DEFAULT 'draft'
created_by BIGINT UNSIGNED -- Foreign key to users (creator)
reviewed_by BIGINT UNSIGNED NULL -- Foreign key to users (reviewer)
review_notes TEXT NULL -- Feedback from reviewer
submitted_at TIMESTAMP NULL -- When submitted for review
reviewed_at TIMESTAMP NULL -- When approved/rejected
```

## Implementation Details

### 1. ApprovalController

**Location**: `Modules/Social/app/Http/Controllers/ApprovalController.php`

**Routes**:
```
GET    /admin/social/approval                    - View approval dashboard
POST   /admin/social/approval/{post}/submit      - Submit post for review
POST   /admin/social/approval/{post}/approve     - Approve post
POST   /admin/social/approval/{post}/reject      - Reject post
POST   /admin/social/approval/{post}/return-to-draft - Return to draft
POST   /admin/social/approval/bulk-approve       - Bulk approve
POST   /admin/social/approval/bulk-reject        - Bulk reject
```

**Key Methods**:

#### `index(Request $request): View`
Shows the approval dashboard with:
- Filter by status (pending, approved, rejected, all)
- Stats cards (counts per status)
- List of posts with actions

#### `submit(Post $post): RedirectResponse`
Submits a draft post for review:
- Changes status to `PENDING_REVIEW`
- Sets `submitted_at` timestamp
- Notifies all reviewers via `PostSubmittedForReviewNotification`

#### `approve(Request $request, Post $post): RedirectResponse`
Approves a pending post:
- Changes status to `APPROVED`
- Sets `reviewed_by`, `reviewed_at`, and optional `review_notes`
- Notifies creator via `PostApprovedNotification`

#### `reject(Request $request, Post $post): RedirectResponse`
Rejects a pending post:
- Changes status to `REJECTED`
- Requires `review_notes` (mandatory feedback)
- Sets `reviewed_by` and `reviewed_at`
- Notifies creator via `PostRejectedNotification`

#### `returnToDraft(Post $post): RedirectResponse`
Returns a rejected post to draft:
- Only the original creator can do this
- Resets approval fields (status, notes, reviewer, timestamps)
- Allows creator to edit and resubmit

#### `bulkApprove(Request $request): RedirectResponse`
Approves multiple posts at once:
- Validates `post_ids[]` array
- Only processes posts in `PENDING_REVIEW` status
- Notifies all creators individually

#### `bulkReject(Request $request): RedirectResponse`
Rejects multiple posts with same reason:
- Requires single `review_notes` for all posts
- Only processes posts in `PENDING_REVIEW` status
- Notifies all creators individually

### 2. Notifications

All notifications implement `ShouldQueue` for asynchronous processing and use both `mail` and `database` channels.

#### PostSubmittedForReviewNotification

**Location**: `Modules/Social/app/Notifications/PostSubmittedForReviewNotification.php`

**Sent to**: All reviewers (users in the account except creator)

**Email Content**:
- Subject: "📝 New Post Awaiting Review"
- Creator name
- Content preview (100 chars)
- Networks (account usernames)
- Scheduled date
- Action button to review

**Database Data**:
```php
[
    'post_id' => $post->id,
    'creator_name' => $post->creator->name,
    'content_preview' => str($post->content)->limit(50),
    'scheduled_at' => $post->scheduled_at,
    'action_url' => route('admin.social.approval.index'),
]
```

#### PostApprovedNotification

**Location**: `Modules/Social/app/Notifications/PostApprovedNotification.php`

**Sent to**: Post creator

**Email Content**:
- Subject: "✅ Your Post Has Been Approved"
- Reviewer name
- Content preview
- Networks
- Scheduled date
- Optional reviewer notes
- Action button to view post

**Database Data**:
```php
[
    'post_id' => $post->id,
    'reviewer_name' => $post->reviewer->name,
    'content_preview' => str($post->content)->limit(50),
    'review_notes' => $post->review_notes,
    'approved_at' => $post->reviewed_at,
    'action_url' => route('admin.social.publishing.edit', $post),
]
```

#### PostRejectedNotification

**Location**: `Modules/Social/app/Notifications/PostRejectedNotification.php`

**Sent to**: Post creator

**Email Content**:
- Subject: "❌ Your Post Has Been Rejected"
- Reviewer name
- Content preview
- Networks
- Rejection reason (review_notes)
- Action button to edit and resubmit

**Database Data**:
```php
[
    'post_id' => $post->id,
    'reviewer_name' => $post->reviewer->name,
    'content_preview' => str($post->content)->limit(50),
    'review_notes' => $post->review_notes,
    'rejected_at' => $post->reviewed_at,
    'action_url' => route('admin.social.publishing.edit', $post),
]
```

### 3. Approval View

**Location**: `Modules/Social/resources/views/approval/index.blade.php`

**Features**:
- Stats cards showing counts (Pending, Approved, Rejected)
- Filter dropdown (status selector)
- Responsive table with post details:
  - Content preview with media thumbnail
  - Creator avatar and name
  - Network icons (Facebook, Instagram, Twitter, LinkedIn)
  - Scheduled date
  - Status badge (color-coded)
  - Submission date (relative time)
  - Action buttons per status
- Bulk selection checkboxes
- Bulk action bar (appears when posts selected)
- Modals for approve/reject actions
- Bootstrap 5 styling with Tabler Icons

**Action Buttons by Status**:

| Status | Available Actions |
|--------|------------------|
| Pending Review | View, Approve, Reject |
| Approved | View, Info (shows reviewer and date) |
| Rejected | View, Return to Draft (creator only) |

**JavaScript Features**:
- Select all checkbox
- Individual checkbox selection
- Dynamic bulk action bar
- Bulk approve (confirmation required)
- Bulk reject (modal with reason)
- Bootstrap tooltips

## User Flow

### Content Creator Flow

1. **Create Draft**
   - Create post with content, media, schedule
   - Status: `DRAFT`

2. **Submit for Review**
   - Click "Submit for Review" button
   - Post moves to `PENDING_REVIEW`
   - All reviewers receive email notification

3. **Wait for Review**
   - Cannot edit post while pending
   - Can view post details

4. **If Approved**
   - Receive email notification
   - Post moves to `APPROVED`
   - Post will be published as scheduled

5. **If Rejected**
   - Receive email with rejection reason
   - Click "Return to Draft" to make changes
   - Edit post based on feedback
   - Resubmit for review

### Reviewer Flow

1. **Receive Notification**
   - Email: "New Post Awaiting Review"
   - Database notification (bell icon)

2. **Review Post**
   - Go to Approval Dashboard (`/admin/social/approval`)
   - View post details (content, media, networks, schedule)
   - Decide: Approve or Reject

3. **Approve**
   - Click "Approve" button
   - Optionally add notes for creator
   - Creator receives email notification
   - Post ready for publication

4. **Reject**
   - Click "Reject" button
   - MUST provide rejection reason
   - Creator receives email with feedback
   - Post returns to creator for revision

### Bulk Review Flow

1. **Select Multiple Posts**
   - Use checkboxes to select posts
   - Bulk action bar appears

2. **Bulk Approve**
   - Click "Approve Selected"
   - Confirm action
   - All selected posts approved
   - Each creator receives individual notification

3. **Bulk Reject**
   - Click "Reject Selected"
   - Enter single rejection reason (applies to all)
   - All selected posts rejected
   - Each creator receives notification with reason

## Permissions & Authorization

### Current Implementation

- **Account-level isolation**: Posts can only be reviewed within the same account
- **Creator verification**: Only original creator can return rejected posts to draft
- **Ownership checks**: All actions verify `account_id` matches authenticated user

### Suggested Future Enhancements

1. **Role-based permissions**:
   - Define "reviewer" role
   - Assign specific users as reviewers
   - Only users with reviewer role can approve/reject

2. **Permission gates**:
   ```php
   Gate::define('approve-posts', fn($user) => $user->hasRole('reviewer'));
   Gate::define('submit-for-review', fn($user) => $user->hasRole('content-creator'));
   ```

3. **Approval workflow settings**:
   - Enable/disable approval requirement per account
   - Require N reviewers before approval
   - Auto-approve for specific users

## Configuration

### Queue Setup

Notifications use Laravel queues. Ensure queue worker is running:

```bash
php artisan queue:work
```

For production, use Supervisor or similar process manager.

### Email Configuration

Configure mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Database Notifications Table

Ensure notifications table exists:

```bash
php artisan notifications:table
php artisan migrate
```

## Testing

### Manual Testing Steps

1. **Test Submit for Review**:
   - Create a draft post
   - Submit for review
   - Verify status changed to `PENDING_REVIEW`
   - Check reviewer received email

2. **Test Approval**:
   - As reviewer, approve a pending post
   - Verify status changed to `APPROVED`
   - Check creator received approval email
   - Verify `reviewed_by` and `reviewed_at` set

3. **Test Rejection**:
   - As reviewer, reject a pending post with notes
   - Verify status changed to `REJECTED`
   - Check creator received rejection email with notes

4. **Test Return to Draft**:
   - As creator of rejected post, return to draft
   - Verify status changed to `DRAFT`
   - Verify approval fields reset

5. **Test Bulk Actions**:
   - Select multiple pending posts
   - Bulk approve or reject
   - Verify all creators received individual notifications

### Unit Test Ideas

```php
// Test submission
$post = Post::factory()->create(['approval_status' => ApprovalStatus::DRAFT]);
$response = $this->post(route('admin.social.approval.submit', $post));
$this->assertEquals(ApprovalStatus::PENDING_REVIEW, $post->fresh()->approval_status);

// Test approval
$post = Post::factory()->pendingReview()->create();
$response = $this->post(route('admin.social.approval.approve', $post));
$this->assertEquals(ApprovalStatus::APPROVED, $post->fresh()->approval_status);

// Test rejection
$post = Post::factory()->pendingReview()->create();
$response = $this->post(route('admin.social.approval.reject', $post), [
    'review_notes' => 'Please fix the typo'
]);
$this->assertEquals(ApprovalStatus::REJECTED, $post->fresh()->approval_status);
```

## UI Screenshots

### Approval Dashboard
- Stats cards at top (Pending: 12, Approved: 45, Rejected: 3)
- Filter dropdown (Status selector)
- Table with post details and actions
- Bulk action bar when posts selected

### Approve Modal
- Confirmation message
- Optional notes textarea
- Cancel / Approve buttons

### Reject Modal
- Rejection reason textarea (required)
- Cancel / Reject buttons

## Security Considerations

1. **CSRF Protection**: All POST routes protected by `@csrf` token
2. **Account Isolation**: Posts verified to belong to user's account
3. **Authorization**: Creator verification for return-to-draft action
4. **Validation**: Required fields validated (review_notes for rejection)
5. **XSS Prevention**: All output escaped with Blade `{{ }}` syntax

## Performance Considerations

1. **Eager Loading**: Controller uses `with(['socialAccount', 'campaign', 'creator', 'reviewer'])` to prevent N+1 queries
2. **Pagination**: Posts paginated (20 per page) for large datasets
3. **Queued Notifications**: Emails sent asynchronously via queue workers
4. **Indexes**: Ensure `approval_status`, `submitted_at`, `account_id` are indexed

## Future Enhancements

1. **Approval History**:
   - Store all approval events (submitted, approved, rejected, returned to draft)
   - Show timeline of changes on post detail page

2. **Multi-level Approval**:
   - Require approval from multiple reviewers
   - Different approval levels (L1, L2, final approval)

3. **Approval Templates**:
   - Pre-defined rejection reasons
   - Quick rejection with one click

4. **Real-time Notifications**:
   - WebSocket/Pusher integration
   - Toast notifications in UI
   - Desktop notifications

5. **Approval Analytics**:
   - Average time to approval
   - Rejection rate
   - Most common rejection reasons
   - Reviewer performance metrics

6. **Conditional Approval**:
   - Auto-approve posts from trusted users
   - Require approval only for specific networks
   - Approval rules based on content (keywords, hashtags)

## Related Files

### Controllers
- `Modules/Social/app/Http/Controllers/ApprovalController.php` (257 lines)

### Notifications
- `Modules/Social/app/Notifications/PostSubmittedForReviewNotification.php` (77 lines)
- `Modules/Social/app/Notifications/PostApprovedNotification.php` (84 lines)
- `Modules/Social/app/Notifications/PostRejectedNotification.php` (78 lines)

### Views
- `Modules/Social/resources/views/approval/index.blade.php` (494 lines)

### Routes
- `Modules/Social/routes/web.php` (approval routes: lines 141-150)

### Enums
- `Modules/Social/app/Enums/ApprovalStatus.php`

### Models
- `Modules/Social/app/Models/Post.php` (relationships: creator, reviewer)

## Total Implementation

- **Lines of Code**: ~1,000 lines
- **Files Created**: 5 new files
- **Files Modified**: 2 files (routes, Post model)
- **Database Columns**: 6 new columns on social_posts table

---

**Status**: ✅ Complete and Production-Ready
**Part of**: TIER 1 Features (ADDITIONAL_FEATURES.md)
