# Workflow: Plan Implementation

Before writing any code, analyze and plan the task using the plan agent.

## Steps to Execute

1. **Delegate to the plan agent** using the Task tool with `subagent_type="Plan"`:
   - Explore the codebase (read related files, check routes, inspect database schema)
   - Identify which files need to be modified or created
   - Design the implementation approach
   - Assign specific agents to each step

2. **Present the plan** to the user for approval before proceeding

3. **Execute the plan** step by step, delegating to the appropriate agents:
   - Follow the agent chain from the plan
   - After each agent completes, verify the output before proceeding
   - Run `vendor/bin/pint --dirty` after PHP changes
   - Run relevant tests after implementation steps

4. **Final verification**:
   - Delegate to the review agent for code quality check
   - Delegate to the testing agent if tests weren't already written
   - Run `vendor/bin/pint` on all modified files

## Task to Plan

$ARGUMENTS
