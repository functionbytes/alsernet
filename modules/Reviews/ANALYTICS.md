# Reviews Analytics Dashboard

## Overview
Comprehensive analytics dashboard for the Reviews module providing real-time insights into review performance, sentiment analysis, and actionable intelligence.

## Features

### 1. KPI Metrics (Top Cards)
- **Total Reviews**: Total count of all reviews received
- **Average Rating**: Mean star rating across all reviews (out of 5.0)
- **Unanswered Reviews**: Count of reviews with comments that haven't been replied to
- **Response Rate**: Percentage of reviews that have been answered (%)

### 2. Charts & Visualizations

#### Rating Trends (12 Months)
- Dual-axis line chart showing:
  - Average rating per month (left y-axis, 0-5 scale)
  - Review count per month (right y-axis)
- Helps identify seasonal patterns and rating trends over time

#### Rating Distribution (Pie Chart)
- Breakdown of reviews by star rating (1-5 stars)
- Color-coded:
  - 5 stars: Green (#13C672)
  - 4 stars: Primary (#b10100)
  - 3 stars: Warning (#FEC90F)
  - 2 stars: Danger (#FA896B)
  - 1 star: Dark Red (#d32f2f)

#### Reviews by Day (30 Days)
- Area chart showing daily review volume
- Helps identify peak days and recent activity patterns

#### Top Locations (Bar Chart)
- Horizontal bar chart of locations with most reviews
- Shows up to 10 locations
- Sorted by review count descending

#### Sentiment Analysis (Stacked Area Chart)
- 30-day trend of review sentiment:
  - Positive: 4-5 star reviews (green)
  - Neutral: 3 star reviews (yellow)
  - Negative: 1-2 star reviews (red)
- Helps track customer satisfaction trends

### 3. Data Tables

#### Recent Reviews (Last 10)
Displays:
- Reviewer name
- Location
- Star rating (visual stars)
- Response status (Respondida/Pendiente badge)
- Review date
- Preview of comment text (truncated to 50 chars)

#### Reviews Requiring Attention
Filters reviews that need immediate action:
- **Criteria**:
  - Unanswered reviews (no google_reply_text)
  - With comments (not empty)
  - Either: Low rating (1-3 stars) OR long comments (>100 chars)
- **Priority Levels**:
  - High (red): 1-2 star reviews
  - Medium (yellow): 3 star reviews or long comments
- **Features**:
  - Click row to navigate to review
  - Badge showing count in header
  - Truncated comment preview (150 chars)

### 4. Additional Metrics (Backend Only - Not Displayed Yet)

#### Average Response Time
- Calculates mean time from review_time to google_reply_time
- Returns both hours and formatted string (X días or X horas)

#### Top Reviewers
- Lists up to 5 most frequent reviewers
- Shows reviewer name and review count

## API Endpoint

### GET `/reviews/dashboard/data`
**Authentication**: Required (auth middleware)

**Response Structure**:
```json
{
  "kpis": {
    "total": 142,
    "avgRating": 4.3,
    "unanswered": 12,
    "responseRate": 85.2
  },
  "ratingTrends": {
    "labels": ["2024-01", "2024-02", ...],
    "datasets": [
      {
        "label": "Calificación promedio",
        "data": [4.5, 4.2, ...],
        "yAxisID": "y-rating"
      },
      {
        "label": "Cantidad de reseñas",
        "data": [23, 45, ...],
        "yAxisID": "y-count"
      }
    ]
  },
  "ratingDistribution": {
    "labels": ["5 estrellas", "4 estrellas", ...],
    "datasets": [...]
  },
  "locationStats": {
    "labels": ["Location A", "Location B", ...],
    "datasets": [...]
  },
  "reviewsByDay": {
    "labels": ["2024-02-01", "2024-02-02", ...],
    "datasets": [...]
  },
  "sentimentTrend": {
    "labels": [...],
    "datasets": [
      { "label": "Positivas (4-5★)", "data": [...] },
      { "label": "Neutrales (3★)", "data": [...] },
      { "label": "Negativas (1-2★)", "data": [...] }
    ]
  },
  "recentReviews": [...],
  "attentionNeeded": [...],
  "avgResponseTime": {
    "hours": 12.5,
    "formatted": "12.5 horas"
  },
  "topReviewers": [...]
}
```

## Service Methods

All analytics logic is in `ReviewDashboardService`:

- `getKpiMetrics()`: KPI card data
- `getRatingTrends($months)`: Monthly rating/count trends
- `getRatingDistribution()`: Star rating breakdown
- `getLocationStats()`: Top locations by review count
- `getReviewsByDay($days)`: Daily review counts
- `getSentimentTrend($days)`: Positive/neutral/negative breakdown by day
- `getRecentReviews($limit)`: Latest reviews
- `getReviewsNeedingAttention($limit)`: Unanswered + (low rating OR long)
- `getAverageResponseTime()`: Mean response time in hours
- `getTopReviewers($limit)`: Most frequent reviewers

## Technologies

- **Backend**: Laravel 12, Eloquent ORM
- **Frontend**: Bootstrap 5.3, jQuery, AJAX
- **Charts**: Chart.js 4.4.0
- **Notifications**: Toastr
- **Icons**: Font Awesome 6

## Auto-Refresh

Dashboard auto-refreshes every 5 minutes (300,000ms) to show latest data without manual reload.

## Empty States

All charts and tables gracefully handle empty data with descriptive messages:
- "No hay datos de tendencias aún"
- "No hay reseñas aún"
- "No hay ubicaciones con reseñas"
- "No hay reseñas que requieran atención"

## Styling

- Project color palette (Primary: #b10100, Success: #13C672, etc.)
- Smooth hover animations on widgets and cards
- Responsive design (mobile, tablet, desktop)
- Scrollable tables with custom scrollbar styling
- Shadow effects for depth
- Badge color coding for status and priority
