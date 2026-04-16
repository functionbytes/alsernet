---
name: team-review
description: "Launch an agent team for parallel code review with security, performance, and quality reviewers. Use when doing thorough PR reviews, pre-release reviews, or comprehensive code audits."
disable-model-invocation: false
---

# Parallel Team Review

Create an agent team to review: **$ARGUMENTS**

## Team Setup
Create an agent team with 3 teammates:

### Teammate 1: Security Reviewer
Using the `security` agent type, audit for:
- SQL injection, XSS, CSRF vulnerabilities
- Authentication and authorization gaps
- Input validation and sanitization
- Exposed secrets or credentials
- Mass assignment vulnerabilities

### Teammate 2: Performance Reviewer
Using the `performance` agent type, analyze:
- N+1 queries and missing eager loading
- Missing database indexes
- Uncached data that should be cached
- Memory-intensive operations without chunking
- Unnecessary data loading

### Teammate 3: Quality Reviewer
Using the `review` agent type, check:
- Code conventions and naming standards
- Dead code and unused imports
- Fat controllers (logic in services?)
- Missing return types
- Anti-patterns

## Coordination
- Each reviewer works independently on their domain
- Reviewers should challenge each other's findings
- The leader synthesizes all findings into a final report
- Wait for all teammates to complete before summarizing

## Output Format
```
## Team Review: [Scope]

### Security (Teammate 1)
[Findings with severity ratings]

### Performance (Teammate 2)
[Findings with impact ratings]

### Quality (Teammate 3)
[Findings with priority ratings]

### Combined Priority List
1. [Most critical across all domains]
2. [Next priority]
...
```
