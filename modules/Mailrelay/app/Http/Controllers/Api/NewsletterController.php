<?php

namespace Modules\Mailrelay\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Enums\SubscriberStatus;
use Modules\Mailrelay\Exceptions\EmailValidationException;
use Modules\Mailrelay\Services\EmailValidation\EmailValidatorService;

/**
 * NewsletterController
 *
 * API controller for managing newsletter subscriptions.
 * Provides endpoints for subscribing, unsubscribing, checking status,
 * and listing subscribers with email validation integration.
 *
 * Reference: FASE 8 - Section 8.2
 */
class NewsletterController extends Controller
{
    private EmailValidatorService $emailValidator;

    public function __construct(EmailValidatorService $emailValidator)
    {
        $this->emailValidator = $emailValidator;
    }

    /**
     * Subscribe a new email to the newsletter
     *
     * Validates the email using EmailValidatorService before subscribing.
     * Only accepts valid emails (not disposable, not suspicious).
     *
     *
     * @throws ValidationException
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'name' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            $email = strtolower(trim($validated['email']));
            $name = $validated['name'] ?? null;
            $metadata = $validated['metadata'] ?? [];

            Log::info("NewsletterController: Subscribe request for {$email}");

            // Validate email using EmailValidatorService
            try {
                $validationResult = $this->emailValidator->validate($email, [
                    'level' => 'standard',
                    'store_result' => true,
                ]);
            } catch (EmailValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email validation failed',
                    'error' => $e->getMessage(),
                ], 422);
            }

            // Check validation result
            if (! $validationResult['valid']) {
                Log::warning('NewsletterController: Invalid email rejected', [
                    'email' => $email,
                    'status' => $validationResult['status'],
                    'score' => $validationResult['score'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Email address is not valid',
                    'validation' => [
                        'status' => $validationResult['status'],
                        'score' => $validationResult['score'],
                        'issues' => $validationResult['issues'],
                        'summary' => $validationResult['summary'],
                    ],
                ], 422);
            }

            // Check if email is disposable or suspicious
            if ($validationResult['is_disposable']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disposable email addresses are not allowed',
                    'validation' => [
                        'status' => $validationResult['status'],
                        'is_disposable' => true,
                    ],
                ], 422);
            }

            // Check if subscriber already exists
            $subscriber = Subscriber::where('email', $email)->first();

            if ($subscriber) {
                // If already subscribed and active
                if ($subscriber->status === SubscriberStatus::ACTIVE) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Email is already subscribed to the newsletter',
                        'subscriber' => [
                            'id' => $subscriber->id,
                            'email' => $subscriber->email,
                            'name' => $subscriber->name,
                            'status' => $subscriber->status->value,
                            'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                        ],
                    ], 200);
                }

                // Reactivate if previously unsubscribed
                $subscriber->subscribe();
                if ($name) {
                    $subscriber->name = $name;
                }
                if (! empty($metadata)) {
                    $subscriber->metadata = array_merge($subscriber->metadata ?? [], $metadata);
                }
                $subscriber->save();

                Log::info('NewsletterController: Subscriber reactivated', ['email' => $email]);

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully resubscribed to the newsletter',
                    'subscriber' => [
                        'id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'name' => $subscriber->name,
                        'status' => $subscriber->status->value,
                        'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                    ],
                ], 200);
            }

            // Create new subscriber
            $subscriber = Subscriber::create([
                'email' => $email,
                'name' => $name,
                'status' => SubscriberStatus::ACTIVE,
                'metadata' => $metadata,
                'subscribed_at' => now(),
            ]);

            Log::info('NewsletterController: New subscriber created', [
                'id' => $subscriber->id,
                'email' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed to the newsletter',
                'subscriber' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'name' => $subscriber->name,
                    'status' => $subscriber->status->value,
                    'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                ],
                'validation' => [
                    'score' => $validationResult['score'],
                    'status' => $validationResult['status'],
                ],
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('NewsletterController: Subscribe error', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your subscription',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Unsubscribe an email from the newsletter
     *
     *
     * @throws ValidationException
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ]);

            $email = strtolower(trim($validated['email']));

            Log::info("NewsletterController: Unsubscribe request for {$email}");

            // Find subscriber
            $subscriber = Subscriber::where('email', $email)->first();

            if (! $subscriber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address not found in our newsletter list',
                ], 404);
            }

            // Check if already unsubscribed
            if ($subscriber->status === SubscriberStatus::UNSUBSCRIBED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email is already unsubscribed from the newsletter',
                    'subscriber' => [
                        'id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'status' => $subscriber->status->value,
                        'unsubscribed_at' => $subscriber->unsubscribed_at?->toIso8601String(),
                    ],
                ], 200);
            }

            // Unsubscribe
            $subscriber->unsubscribe();

            Log::info('NewsletterController: Subscriber unsubscribed', [
                'id' => $subscriber->id,
                'email' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from the newsletter',
                'subscriber' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'status' => $subscriber->status->value,
                    'unsubscribed_at' => $subscriber->unsubscribed_at?->toIso8601String(),
                ],
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('NewsletterController: Unsubscribe error', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your unsubscription',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get subscription status for an email
     *
     *
     * @throws ValidationException
     */
    public function status(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ]);

            $email = strtolower(trim($validated['email']));

            Log::info("NewsletterController: Status check for {$email}");

            // Find subscriber
            $subscriber = Subscriber::where('email', $email)->first();

            if (! $subscriber) {
                return response()->json([
                    'success' => true,
                    'subscribed' => false,
                    'message' => 'Email address not found in our newsletter list',
                    'status' => null,
                ], 200);
            }

            // Get validation history if available
            $validationHistory = $this->emailValidator->getValidationHistory($email);

            return response()->json([
                'success' => true,
                'subscribed' => $subscriber->status === SubscriberStatus::ACTIVE,
                'subscriber' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'name' => $subscriber->name,
                    'status' => $subscriber->status->value,
                    'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                    'unsubscribed_at' => $subscriber->unsubscribed_at?->toIso8601String(),
                    'last_synced_at' => $subscriber->last_synced_at?->toIso8601String(),
                ],
                'validation' => $validationHistory ? [
                    'score' => $validationHistory['score'],
                    'status' => $validationHistory['status'],
                    'validated_at' => $validationHistory['validated_at'],
                ] : null,
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('NewsletterController: Status check error', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking subscription status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List all newsletter subscribers with optional filtering and pagination
     *
     *
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'status' => 'nullable|string|in:active,unsubscribed,bounced,pending,banned',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:email,name,created_at,subscribed_at,unsubscribed_at',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            Log::info('NewsletterController: Index request', $validated);

            // Build query
            $query = Subscriber::query();

            // Filter by status
            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Search by email or name
            if (! empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $validated['per_page'] ?? 15;
            $subscribers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $subscribers->map(function ($subscriber) {
                    return [
                        'id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'name' => $subscriber->name,
                        'status' => $subscriber->status->value,
                        'mailrelay_id' => $subscriber->mailrelay_id,
                        'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                        'unsubscribed_at' => $subscriber->unsubscribed_at?->toIso8601String(),
                        'last_synced_at' => $subscriber->last_synced_at?->toIso8601String(),
                        'created_at' => $subscriber->created_at?->toIso8601String(),
                    ];
                }),
                'meta' => [
                    'current_page' => $subscribers->currentPage(),
                    'per_page' => $subscribers->perPage(),
                    'total' => $subscribers->total(),
                    'last_page' => $subscribers->lastPage(),
                    'from' => $subscribers->firstItem(),
                    'to' => $subscribers->lastItem(),
                ],
                'links' => [
                    'first' => $subscribers->url(1),
                    'last' => $subscribers->url($subscribers->lastPage()),
                    'prev' => $subscribers->previousPageUrl(),
                    'next' => $subscribers->nextPageUrl(),
                ],
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('NewsletterController: Index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching subscribers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
