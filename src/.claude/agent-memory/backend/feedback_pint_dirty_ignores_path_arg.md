---
name: feedback-pint-dirty-ignores-path-arg
description: vendor/bin/pint --dirty <path> does NOT scope to that path — it scans every git-dirty file repo-wide and can reformat unrelated in-progress work from other agents
metadata:
  type: feedback
---

`vendor/bin/pint --dirty <specific-file-path>` does not restrict Pint to that file. In this shared,
heavily multi-agent checkout, running it that way reformatted an unrelated file
(`modules/HelpdeskAgents/app/Http/Controllers/Managers/Settings/AgentSettingsController.php`) that
another concurrent agent had mid-refactor (two methods removed, one import removed) — Pint applied
style-only fixes (`class_attributes_separation`, `braces_position`, etc.) on top of that WIP content.
The method deletions were NOT caused by Pint/me — `git status` showed 561 modified files at the time,
confirming many parallel agents share this working tree — but my Pint invocation still touched a file
outside my task scope.

**Why:** `--dirty` computes its file set from `git diff --name-only` (repo-wide) and uses that instead
of/in addition to any path argument you pass; the path argument is effectively ignored once `--dirty`
is present.

**How to apply:** When asked to "run pint on file X" (scoped to one file), run
`vendor/bin/pint modules/Path/To/File.php` **without** `--dirty`. Only use `--dirty` (no path) when you
genuinely want to format everything currently modified in the tree, and be aware in this project that
may include other agents' in-flight work — check `git status` first if the diff count looks unexpectedly
large, and don't try to "undo" other agents' content changes; only worry about your own file's diff.
