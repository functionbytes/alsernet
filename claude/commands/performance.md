# Workflow: Performance Optimization

Profile and optimize the specified scope. Execute these steps:

## Steps to Execute

1. **Profile first** (measure, don't guess):
   - Use Boost `database-query` with `EXPLAIN` on slow queries
   - Use Boost `read-log-entries` for slow query warnings
   - Use Redis MCP `get_memory_info` for cache stats
   - Use Chrome DevTools `performance_start_trace` for page load analysis

2. **Identify bottlenecks**:
   - N+1 queries (missing eager loading)
   - Missing indexes (check with `database-schema`)
   - Uncached frequently-accessed data
   - Loading too many columns (`select()` needed)
   - Memory issues (need chunking/lazy collections)

3. **Optimize** following these priorities:
   - Add missing indexes (cheapest win)
   - Add eager loading (prevent N+1)
   - Add Redis caching (reduce DB hits)
   - Optimize queries (select, paginate)
   - Queue heavy operations

4. **Simplify**: re-read optimized code and refine (early returns, clear names, no nested ternaries)

5. **Measure after** and compare with baseline

6. **Report** before/after metrics

If $ARGUMENTS is provided, focus optimization on those areas.

$ARGUMENTS
