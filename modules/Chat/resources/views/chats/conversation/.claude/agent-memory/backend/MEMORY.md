# Backend Agent Memory

## Project Structure
- Module root: `modules/Chat/`
- Services live in: `modules/Chat/app/Services/<Domain>/`
- Base Controller: `modules/Chat/app/Http/Controllers/Controller.php` (no helper methods - pure base)

## Settings Pattern
- All helpdesk settings use `HelpdeskSettingsRepository` (Cache + DB `settings` table)
  - `get(string $key, array $defaults): array`
  - `save(string $key, array $values): void`
- File: `modules/Chat/app/Services/Settings/HelpdeskSettingsRepository.php`
- Settings are cached with `Cache::put($key, $values, now()->addDays(365))` and persisted to `settings` table as JSON

## Extracted Services (Settings)
- `HelpdeskSettingsRepository` — shared get/save for all setting groups (Cache + DB)
- `ConversationAttachmentsService` — disk metadata, disk stats, activity history
- `NotificationSettingsService` — checkbox boolean normalisation (absent = false)

## Checkbox Normalisation
- HTML checkboxes absent from POST when unchecked → use `isset($validated['key'])` not direct value
- The `ticketsUpdate` loop coerces `null` values of boolean-like fields to `false` (excluding string fields)

## Pattern Reference
- Service example: `modules/Chat/app/Services/Conversations/ConversationIndexService.php`
- Use `Cache::remember()` with TTL for expensive reads
- Use `Model::query()` not `DB::` for Eloquent
