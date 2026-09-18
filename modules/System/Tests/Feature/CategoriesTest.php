<?php

namespace Modules\System\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Modules\System\Http\Requests\StoreCategorieRequest;
use Modules\System\Tests\TestCase;

/**
 * Validation tests for StoreCategorieRequest.
 *
 * The System CategoriesController is not registered in any route file,
 * so HTTP-level tests are not possible. These tests exercise the
 * validation rules directly through the Validator facade using the same
 * rules() array declared by StoreCategorieRequest, which is the
 * meaningful testable unit.
 */
class CategoriesTest extends TestCase
{
    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'title' => 'Test Category',
            'available' => true,
        ];
    }

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = new StoreCategorieRequest;

        return Validator::make($data, $request->rules());
    }

    // ---------------------------------------------------------------
    // title field
    // ---------------------------------------------------------------

    public function test_store_fails_validation_when_title_is_missing(): void
    {
        $data = $this->validPayload();
        unset($data['title']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_store_fails_validation_when_title_is_empty_string(): void
    {
        $data = array_merge($this->validPayload(), ['title' => '']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_store_fails_validation_when_title_exceeds_255_characters(): void
    {
        $data = array_merge($this->validPayload(), ['title' => str_repeat('a', 256)]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_store_passes_validation_with_valid_title(): void
    {
        $validator = $this->validate($this->validPayload());

        $this->assertFalse($validator->fails());
    }

    // ---------------------------------------------------------------
    // available field
    // ---------------------------------------------------------------

    public function test_store_fails_validation_when_available_is_missing(): void
    {
        $data = $this->validPayload();
        unset($data['available']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('available', $validator->errors()->toArray());
    }

    public function test_store_fails_validation_when_available_is_not_boolean(): void
    {
        $data = array_merge($this->validPayload(), ['available' => 'yes']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('available', $validator->errors()->toArray());
    }

    public function test_store_passes_validation_with_available_false(): void
    {
        $data = array_merge($this->validPayload(), ['available' => false]);

        $validator = $this->validate($data);

        $this->assertFalse($validator->fails());
    }

    // ---------------------------------------------------------------
    // Combined: full valid payload
    // ---------------------------------------------------------------

    public function test_store_passes_validation_with_complete_valid_payload(): void
    {
        $validator = $this->validate([
            'title' => 'My Category',
            'available' => 1,
        ]);

        $this->assertFalse($validator->fails());
    }
}
