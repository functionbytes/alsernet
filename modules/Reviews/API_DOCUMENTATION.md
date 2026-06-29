# Reviews API Documentation

## Overview

RESTful API endpoints for managing Google Business Profile reviews.

**Base URL**: `/api/reviews`

**Authentication**: Sanctum (Bearer Token)

**Rate Limiting**: 60 requests per minute

---

## Endpoints

### 1. List Reviews

Get a paginated list of reviews with filtering options.

**Endpoint**: `GET /api/reviews`

**Query Parameters**:
- `location_id` (integer) - Filter by location ID
- `rating` (string) - Filter by rating (ONE, TWO, THREE, FOUR, FIVE)
- `is_visible` (boolean) - Filter by visibility (requires moderation)
- `is_featured` (boolean) - Filter featured reviews
- `has_reply` (boolean) - Filter reviews with/without Google reply
- `has_comment` (boolean) - Filter reviews with/without comment
- `per_page` (integer) - Items per page (default: 20)
- `page` (integer) - Page number

**Example Request**:
```bash
GET /api/reviews?location_id=1&rating=FIVE&is_featured=true&per_page=10
```

**Example Response**:
```json
{
  "data": [
    {
      "id": 1,
      "googleReviewId": "ChZDSUhNMG9nS0VJQ0FnSUNuOXFIekRREAE",
      "reviewerName": "John Doe",
      "reviewerPhotoUrl": "https://lh3.googleusercontent.com/...",
      "rating": 5,
      "ratingLabel": "FIVE",
      "comment": "Excellent service!",
      "reviewTime": "2024-02-15T10:30:00+00:00",
      "updateTime": null,
      "googleReplyText": "Thank you for your review!",
      "googleReplyTime": "2024-02-16T09:00:00+00:00",
      "hasGoogleReply": true,
      "hasComment": true,
      "syncedAt": "2024-02-20T08:00:00+00:00",
      "createdAt": "2024-02-15T10:35:00+00:00",
      "updatedAt": "2024-02-16T09:05:00+00:00",
      "location": {
        "id": 1,
        "googleLocationId": "ChIJN1t_tDeuEmsRUsoyG83frY4",
        "name": "My Business Location",
        "address": "123 Main St, City",
        "phone": "+1234567890",
        "websiteUrl": "https://example.com",
        "averageRating": 4.85,
        "totalReviews": 142,
        "isVerified": true,
        "isActive": true,
        "syncedAt": "2024-02-20T08:00:00+00:00",
        "createdAt": "2024-01-01T00:00:00+00:00",
        "updatedAt": "2024-02-20T08:00:00+00:00"
      },
      "moderation": {
        "id": 1,
        "isVisible": true,
        "isFeatured": true,
        "tags": ["excellent", "customer-service"],
        "internalNotes": "Great review, feature on homepage",
        "moderatedBy": 1,
        "moderatedAt": "2024-02-15T11:00:00+00:00",
        "createdAt": "2024-02-15T10:35:00+00:00",
        "updatedAt": "2024-02-15T11:00:00+00:00"
      },
      "replies": []
    }
  ],
  "links": {
    "first": "/api/reviews?page=1",
    "last": "/api/reviews?page=5",
    "prev": null,
    "next": "/api/reviews?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "/api/reviews",
    "per_page": 20,
    "to": 20,
    "total": 95
  }
}
```

---

### 2. Get Single Review

Get detailed information about a specific review.

**Endpoint**: `GET /api/reviews/{review}`

**Path Parameters**:
- `review` (integer) - Review ID

**Example Request**:
```bash
GET /api/reviews/1
```

**Example Response**:
```json
{
  "data": {
    "id": 1,
    "googleReviewId": "ChZDSUhNMG9nS0VJQ0FnSUNuOXFIekRREAE",
    "reviewerName": "John Doe",
    "reviewerPhotoUrl": "https://lh3.googleusercontent.com/...",
    "rating": 5,
    "ratingLabel": "FIVE",
    "comment": "Excellent service!",
    "reviewTime": "2024-02-15T10:30:00+00:00",
    "updateTime": null,
    "googleReplyText": "Thank you for your review!",
    "googleReplyTime": "2024-02-16T09:00:00+00:00",
    "hasGoogleReply": true,
    "hasComment": true,
    "syncedAt": "2024-02-20T08:00:00+00:00",
    "createdAt": "2024-02-15T10:35:00+00:00",
    "updatedAt": "2024-02-16T09:05:00+00:00",
    "location": {
      "id": 1,
      "googleLocationId": "ChIJN1t_tDeuEmsRUsoyG83frY4",
      "name": "My Business Location",
      "address": "123 Main St, City",
      "phone": "+1234567890",
      "websiteUrl": "https://example.com",
      "averageRating": 4.85,
      "totalReviews": 142,
      "isVerified": true,
      "isActive": true,
      "syncedAt": "2024-02-20T08:00:00+00:00",
      "createdAt": "2024-01-01T00:00:00+00:00",
      "updatedAt": "2024-02-20T08:00:00+00:00"
    },
    "moderation": {
      "id": 1,
      "isVisible": true,
      "isFeatured": true,
      "tags": ["excellent", "customer-service"],
      "internalNotes": "Great review, feature on homepage",
      "moderatedBy": 1,
      "moderatedAt": "2024-02-15T11:00:00+00:00",
      "createdAt": "2024-02-15T10:35:00+00:00",
      "updatedAt": "2024-02-15T11:00:00+00:00"
    },
    "replies": [
      {
        "id": 1,
        "replyText": "Thank you for your feedback!",
        "status": "published",
        "statusLabel": "Published",
        "createdBy": 1,
        "approvedBy": 2,
        "approvedAt": "2024-02-16T08:30:00+00:00",
        "publishedAt": "2024-02-16T09:00:00+00:00",
        "createdAt": "2024-02-16T08:00:00+00:00",
        "updatedAt": "2024-02-16T09:00:00+00:00"
      }
    ]
  }
}
```

---

### 3. Get Review Statistics

Get aggregated statistics for reviews with optional filtering.

**Endpoint**: `GET /api/reviews/stats`

**Query Parameters**:
- `location_id` (integer) - Filter by location ID
- `days` (integer) - Filter reviews from last N days (default: 30)

**Cache**: Results are cached for 5 minutes

**Example Request**:
```bash
GET /api/reviews/stats?location_id=1&days=90
```

**Example Response**:
```json
{
  "data": {
    "totalReviews": 142,
    "averageRating": 4.85,
    "ratingDistribution": {
      "5": 98,
      "4": 32,
      "3": 8,
      "2": 3,
      "1": 1
    },
    "totalVisible": 135,
    "totalFeatured": 12,
    "totalWithComment": 108,
    "totalWithGoogleReply": 87
  }
}
```

---

## Resources

### ReviewResource

Main resource for review data.

**Fields**:
- `id` - Review ID
- `googleReviewId` - Google's review identifier
- `reviewerName` - Name of the reviewer
- `reviewerPhotoUrl` - URL to reviewer's profile photo
- `rating` - Numeric rating (1-5)
- `ratingLabel` - Rating enum value (ONE-FIVE)
- `comment` - Review text (nullable)
- `reviewTime` - When review was posted (ISO 8601)
- `updateTime` - When review was updated (ISO 8601, nullable)
- `googleReplyText` - Business reply text (nullable)
- `googleReplyTime` - When reply was posted (ISO 8601, nullable)
- `hasGoogleReply` - Boolean flag
- `hasComment` - Boolean flag
- `syncedAt` - Last sync timestamp (ISO 8601)
- `createdAt` - Record creation timestamp (ISO 8601)
- `updatedAt` - Record update timestamp (ISO 8601)
- `location` - ReviewLocationResource (when loaded)
- `moderation` - ReviewModerationResource (when loaded, requires permissions)
- `replies` - Array of ReviewReplyResource (when loaded)

### ReviewLocationResource

Location information resource.

**Fields**:
- `id` - Location ID
- `googleLocationId` - Google's location identifier
- `name` - Business name
- `address` - Business address
- `phone` - Business phone
- `websiteUrl` - Business website
- `averageRating` - Average rating (2 decimal places)
- `totalReviews` - Total review count
- `isVerified` - Verification status
- `isActive` - Active status
- `syncedAt` - Last sync timestamp (ISO 8601)
- `createdAt` - Record creation timestamp (ISO 8601)
- `updatedAt` - Record update timestamp (ISO 8601)

### ReviewModerationResource

Moderation data resource.

**Fields**:
- `id` - Moderation record ID
- `isVisible` - Visibility flag
- `isFeatured` - Featured flag
- `tags` - Array of tags
- `internalNotes` - Internal notes (only visible with moderate permission)
- `moderatedBy` - User ID of moderator
- `moderatedAt` - Moderation timestamp (ISO 8601)
- `createdAt` - Record creation timestamp (ISO 8601)
- `updatedAt` - Record update timestamp (ISO 8601)

### ReviewReplyResource

Reply data resource.

**Fields**:
- `id` - Reply ID
- `replyText` - Reply content
- `status` - Status value (draft, approved, published, failed)
- `statusLabel` - Human-readable status
- `errorMessage` - Error message (only on failed status with permissions)
- `errorCount` - Error count (only on failed status with permissions)
- `createdBy` - User ID of creator
- `approvedBy` - User ID of approver
- `approvedAt` - Approval timestamp (ISO 8601, nullable)
- `publishedAt` - Publish timestamp (ISO 8601, nullable)
- `createdAt` - Record creation timestamp (ISO 8601)
- `updatedAt` - Record update timestamp (ISO 8601)

### ReviewStatsResource

Aggregated statistics resource.

**Fields**:
- `totalReviews` - Total review count
- `averageRating` - Average rating (2 decimal places)
- `ratingDistribution` - Object with counts per rating (1-5)
- `totalVisible` - Count of visible reviews
- `totalFeatured` - Count of featured reviews
- `totalWithComment` - Count of reviews with comment
- `totalWithGoogleReply` - Count of reviews with Google reply

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200  | Success (GET, PUT, PATCH) |
| 201  | Created (POST) |
| 204  | No Content (DELETE) |
| 401  | Unauthenticated |
| 403  | Forbidden (authorization failed) |
| 404  | Resource not found |
| 422  | Validation failed |
| 429  | Rate limit exceeded |

---

## Authentication

All endpoints require Sanctum authentication. Include the bearer token in the Authorization header:

```bash
Authorization: Bearer {your-token-here}
```

---

## Permissions

The API respects the following permissions:
- `reviews.view` - View reviews
- `reviews.moderate` - Access moderation data and internal notes

Users without appropriate permissions will receive 403 Forbidden responses.

---

## Examples with cURL

### List featured reviews for a location

```bash
curl -X GET "https://example.com/api/reviews?location_id=1&is_featured=true&per_page=5" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get review statistics

```bash
curl -X GET "https://example.com/api/reviews/stats?days=30" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get single review

```bash
curl -X GET "https://example.com/api/reviews/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Notes

- All timestamps are in ISO 8601 format with timezone
- Pagination follows Laravel's standard pagination structure
- Results are cached for 5 minutes for the stats endpoint
- Rate limiting is set to 60 requests per minute per user
- JSON keys use camelCase for consistency
- All numeric ratings are normalized to 1-5 scale
