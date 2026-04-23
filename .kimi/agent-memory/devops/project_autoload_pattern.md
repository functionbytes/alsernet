---
name: Module autoload registration pattern
description: New nwidart modules are NOT auto-discovered in autoload — must be added manually to composer.json psr-4 and composer dump-autoload run
type: project
---

This project does NOT use nwidart's built-in autoload scanning. Every new module requires a manual entry in the root `composer.json` `autoload.psr-4` section:

```json
"Modules\\NewModule\\": "modules/NewModule/app/"
```

After adding, run `composer dump-autoload --no-scripts` to regenerate.

**Why:** The project uses explicit psr-4 mappings in composer.json instead of nwidart's path-based discovery. Several modules were missing (Attention, Blog, Cache, Captcha, Cookie, Database, Helpdesk, Mailrelay, Reviews, Storage, Widget) causing a fatal `Class not found` on all requests when `modules_statuses.json` had them enabled.

**How to apply:** Whenever a new module is created or enabled in `modules_statuses.json`, verify its namespace entry exists in `composer.json autoload.psr-4` before testing.
