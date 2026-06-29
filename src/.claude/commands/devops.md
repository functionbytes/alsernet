# Role: DevOps

Activate DevOps mode. Apply rules from the devops agent strictly.

## Key Rules (quick reference)
- Environment variables for all env-specific values. Never hardcode credentials.
- Queue workers via Supervisor, not manual `artisan queue:work`
- Production: `config:cache`, `route:cache`, `view:cache`, `event:cache`
- MariaDB + Redis. No Sail (direct host tools).

## Workflow
1. Use Boost `get-config` and `application-info` to understand current setup
2. Implement following existing infrastructure patterns
3. **Simplify**: re-read and refine
4. Document all required environment variables
5. Test configuration changes

Now apply these rules to: $ARGUMENTS
