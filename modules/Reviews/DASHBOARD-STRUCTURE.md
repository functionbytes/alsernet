# Reviews Analytics Dashboard - Visual Structure

## Layout Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     Dashboard de reseñas                                │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────┬──────────────────┬──────────────────┬──────────────────┐
│  📊 Total        │  📈 Rating       │  ⚠️  Sin         │  📊 Tasa de      │
│  reseñas         │  promedio        │  responder       │  respuesta       │
│                  │                  │                  │                  │
│  0               │  0.0             │  0               │  0%              │
│                  │  de 5.0          │                  │                  │
└──────────────────┴──────────────────┴──────────────────┴──────────────────┘

┌───────────────────────────────────────────────────────┬─────────────────────┐
│  Tendencias de calificación      [📊 Análisis]       │  Distribución       │
│  Últimos 12 meses                                     │  Por estrellas      │
│                                                       │                     │
│  ┌──────────────────────────────────────────┐        │  ┌────────────┐    │
│  │  Line Chart (Dual Y-Axis)                │        │  │  Doughnut  │    │
│  │  - Avg Rating (left, 0-5)                │        │  │   Chart    │    │
│  │  - Review Count (right)                  │        │  │            │    │
│  │  - 12 months of data                     │        │  │  1-5 Stars │    │
│  │  - Green + Primary colors                │        │  │   Color    │    │
│  └──────────────────────────────────────────┘        │  │   Coded    │    │
│                                                       │  └────────────┘    │
└───────────────────────────────────────────────────────┴─────────────────────┘

┌───────────────────────────────────────────────────────┬─────────────────────┐
│  Reseñas por día                [📅 Diario]           │  Top ubicaciones    │
│  Últimos 30 días                                      │  Más reseñas        │
│                                                       │                     │
│  ┌──────────────────────────────────────────┐        │  ┌────────────┐    │
│  │  Area Chart                              │        │  │ Horizontal │    │
│  │  - Daily review count                    │        │  │ Bar Chart  │    │
│  │  - Last 30 days                          │        │  │            │    │
│  │  - Filled area under line                │        │  │ Top 10     │    │
│  │  - Primary color                         │        │  │ Locations  │    │
│  └──────────────────────────────────────────┘        │  └────────────┘    │
│                                                       │                     │
└───────────────────────────────────────────────────────┴─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│  Análisis de sentimiento          [🔍 Tendencia]                           │
│  Últimos 30 días                                                            │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │  Stacked Area Chart                                                   │ │
│  │  - Positive (4-5★) - Green line                                       │ │
│  │  - Neutral (3★) - Yellow line                                         │ │
│  │  - Negative (1-2★) - Red line                                         │ │
│  │  - 30 days of data                                                    │ │
│  │  - Interactive tooltips                                               │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────┬──────────────────────────────────┐
│  Reseñas recientes                       │  Requieren atención      [0]    │
│  Últimas 10 reseñas recibidas            │  Sin responder y baja calif.    │
│                                          │                                  │
│  ┌────────────────────────────────────┐ │  ┌────────────────────────────┐ │
│  │ Revisor  │ Ubicación │ ⭐ │ Estado │ │  │ Revisor │ Ubicación │ ⭐ │ P│ │
│  ├──────────┼───────────┼────┼────────┤ │  ├─────────┼───────────┼────┼──┤ │
│  │ Juan P.  │ Madrid    │⭐⭐│✅ Resp.│ │  │ Ana G.  │ Barcelona │⭐  │🔴│ │
│  │ María G. │ Valencia  │⭐⭐│⏱ Pend.│ │  │ Luis M. │ Madrid    │⭐⭐│🟡│ │
│  │ Pedro L. │ Sevilla   │⭐⭐│✅ Resp.│ │  │ Sofia R.│ Valencia  │⭐  │🔴│ │
│  │ ...      │ ...       │... │...     │ │  │ ...     │ ...       │... │..│ │
│  └────────────────────────────────────┘ │  └────────────────────────────┘ │
│                                          │  * Clickable rows              │
│                                          │  * Priority badges             │
└──────────────────────────────────────────┴──────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│  Acciones rápidas                                                           │
│  Gestiona tus reseñas                                                       │
│                                                                             │
│  ┌────────────────┬────────────────┬────────────────┬────────────────┐    │
│  │  📋 Ver todas  │  📄 Plantillas │  ⚙️  Config.   │  💾 Exportar   │    │
│  │  las reseñas   │  de respuesta  │                │  datos         │    │
│  └────────────────┴────────────────┴────────────────┴────────────────┘    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Component Breakdown

### KPI Cards (Row 1)
4 cards with:
- Large number display
- Icon with color-coded background circle
- Title and optional subtitle
- Hover animation (lift + shadow)
- Auto-updating values

### Chart Cards (Rows 2-4)
All cards include:
- Header with title and subtitle
- Badge indicator (Análisis, Diario, Tendencia)
- Canvas element for Chart.js
- Empty state fallback
- Responsive height

### Data Tables (Row 5)
Features:
- Sticky header
- Hover row highlighting
- Badge indicators
- Star rating visualization
- Truncated text previews
- Custom scrollbar
- Click-to-navigate (attention table)

### Quick Actions (Row 6)
4 action buttons:
- Primary (Ver todas)
- Outline Primary (Plantillas)
- Outline Secondary (Config)
- Outline Success (Exportar)
- Icon + text layout
- Hover lift effect

## Color Coding System

### By Rating:
- ⭐⭐⭐⭐⭐ (5 stars) → Green (#13C672)
- ⭐⭐⭐⭐ (4 stars) → Primary (#90bb13)
- ⭐⭐⭐ (3 stars) → Warning (#FEC90F)
- ⭐⭐ (2 stars) → Danger (#FA896B)
- ⭐ (1 star) → Dark Red (#d32f2f)

### By Status:
- ✅ Respondida → Success badge (green)
- ⏱ Pendiente → Warning badge (yellow)
- 🔴 Alta prioridad → Danger badge (red)
- 🟡 Media prioridad → Warning badge (yellow)

## Interactions

### Auto-Refresh
```javascript
// Loads on page ready
loadDashboardData();

// Refreshes every 5 minutes
setInterval(loadDashboardData, 300000);
```

### Chart Updates
```javascript
// Destroys old chart instances
if (charts.ratingTrends) {
    charts.ratingTrends.destroy();
}

// Creates new chart with updated data
charts.ratingTrends = new Chart(ctx, config);
```

### Empty States
```javascript
// Shows message when no data
if (data.labels.length === 0) {
    showEmptyState(canvas, 'No hay datos...');
}
```

### Table Navigation
```javascript
// Attention table rows are clickable
onclick="window.location.href='/reviews?review_id=123'"
```

## Responsive Behavior

### Desktop (1200px+)
- 4 KPI cards in 1 row (3 cols each)
- 8-4 split for main charts
- 12 cols for sentiment chart
- 6-6 split for tables

### Tablet (768px-1199px)
- 2 KPI cards per row (6 cols each)
- Charts stack 12 cols
- Tables stack 12 cols

### Mobile (<768px)
- 1 KPI card per row (12 cols)
- All charts full width
- All tables full width
- Scrollable horizontally if needed

## Performance Optimizations

### Database Level
- Aggregation in SQL (AVG, COUNT, SUM)
- Date grouping at query level
- Limited result sets (top 10, last 30 days)
- Proper indexing on review_time, star_rating

### Frontend Level
- Single AJAX call for all data
- Chart instance caching and reuse
- Debounced updates
- Lazy chart rendering

### Caching Strategy
```php
// No caching currently - real-time data
// Future: Cache for 5 minutes per user
Cache::remember("dashboard.{$userId}", 300, fn() => $data);
```

## Accessibility

- Semantic HTML structure
- ARIA labels on charts
- Keyboard navigation support
- Screen reader friendly
- Color contrast compliance
- Focus states on interactive elements

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Opera 76+
- No IE support (Chart.js requirement)
