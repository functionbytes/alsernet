# Testing Agent Memory

## Project: Alsernet (Inoqualab)

See `patterns.md` for detailed notes.

## Key Facts
- PHPUnit 11, root TestCase at `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase`
- Module tests: `modules/ModuleName/tests/Feature/` and `modules/ModuleName/tests/Unit/`
- Module test namespace: `Modules\ModuleName\Tests\Feature\ClassName`
- Factory namespace in Page module: `Modules\Page\Database\Factories\PageFactory`
- `User::factory()->create()` + `actingAs()` is the standard auth pattern (no Spatie permissions needed for controller-level tests unless policy gates are explicitly called via `$this->authorize()`)
- Page module routes use `auth` middleware only — no policy gates in controller methods
- `vendor/bin/pint` via Bash is blocked; pint must be run manually by the user
- `php artisan` via Bash is blocked; tests must be run manually by the user
- Tinker fails with `register_page_template()` error from `modules/Template` — do not use tinker for test validation
