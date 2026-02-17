<?php

namespace Modules\Mailrelay\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Mailrelay\Services\EmailValidation\EmailValidatorService;

/**
 * EmailValidationController
 *
 * API controller for email validation endpoints.
 * Provides single and bulk email validation via the EmailValidatorService.
 *
 * Reference: FASE 8 - Section 8.1
 *
 * Endpoints:
 * - POST /api/v1/email/validate - Validate single email
 * - POST /api/v1/email/validate-bulk - Validate multiple emails
 */
class EmailValidationController extends Controller
{
    private EmailValidatorService $validatorService;

    public function __construct(EmailValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * Validate a single email address
     *
     * @return JsonResponse
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "validators": ["syntax", "dns", "smtp", "external"], // optional
     *   "level": "quick|standard|thorough", // optional
     *   "cache": true // optional
     * }
     *
     * Response:
     * {
     *   "data": {
     *     "email": "user@example.com",
     *     "valid": true,
     *     "status": "valid",
     *     "score": 95,
     *     "domain": "example.com",
     *     "is_disposable": false,
     *     "is_role_account": false,
     *     "validators": {
     *       "syntax": {"valid": true, "score": 100, "message": "Valid format"},
     *       "dns": {"valid": true, "score": 100, "message": "DNS records found"},
     *       "smtp": {"valid": true, "score": 100, "message": "Mailbox exists"}
     *     },
     *     "issues": [],
     *     "cost": 1,
     *     "validated_at": "2025-01-15T10:30:00Z",
     *     "summary": "Email is valid and safe to use (score: 95/100)"
     *   }
     * }
     */
    public function validateEmail(Request $request): JsonResponse
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'validators' => 'sometimes|array',
            'validators.*' => 'string|in:syntax,email_utilities,dns,smtp,external,zerobounce',
            'level' => 'sometimes|string|in:quick,standard,thorough',
            'cache' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $options = [];

        // Build validation options
        if ($request->has('validators')) {
            $options['validators'] = $request->input('validators');
        }

        if ($request->has('level')) {
            $options['level'] = $request->input('level');
        }

        if ($request->has('cache') && $request->input('cache') === false) {
            $options['skip_cache'] = true;
        }

        try {
            Log::info("EmailValidationController: Validating email {$email}");

            $result = $this->validatorService->validate($email, $options);

            return response()->json([
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("EmailValidationController: Validation error for {$email}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Email validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate multiple email addresses in bulk
     *
     * @return JsonResponse
     *
     * Request body:
     * {
     *   "emails": ["user1@example.com", "user2@example.com", "user3@example.com"],
     *   "validators": ["syntax", "dns", "smtp"], // optional
     *   "level": "quick|standard|thorough", // optional
     *   "cache": true // optional
     * }
     *
     * Response:
     * {
     *   "data": {
     *     "total": 3,
     *     "processed": 3,
     *     "results": {
     *       "user1@example.com": {
     *         "email": "user1@example.com",
     *         "valid": true,
     *         "status": "valid",
     *         "score": 95,
     *         ...
     *       },
     *       "user2@example.com": {
     *         "email": "user2@example.com",
     *         "valid": false,
     *         "status": "invalid",
     *         "score": 0,
     *         ...
     *       }
     *     },
     *     "summary": {
     *       "valid": 2,
     *       "invalid": 1,
     *       "disposable": 0,
     *       "risky": 0,
     *       "total_cost": 3
     *     }
     *   }
     * }
     */
    public function validateBulk(Request $request): JsonResponse
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'emails' => 'required|array|min:1|max:1000',
            'emails.*' => 'email|max:255',
            'validators' => 'sometimes|array',
            'validators.*' => 'string|in:syntax,email_utilities,dns,smtp,external,zerobounce',
            'level' => 'sometimes|string|in:quick,standard,thorough',
            'cache' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $emails = $request->input('emails');
        $options = [];

        // Build validation options
        if ($request->has('validators')) {
            $options['validators'] = $request->input('validators');
        }

        if ($request->has('level')) {
            $options['level'] = $request->input('level');
        }

        if ($request->has('cache') && $request->input('cache') === false) {
            $options['skip_cache'] = true;
        }

        try {
            Log::info('EmailValidationController: Bulk validating '.count($emails).' emails');

            $results = $this->validatorService->validateBatch($emails, $options);

            // Generate summary statistics
            $summary = $this->generateBulkSummary($results);

            return response()->json([
                'data' => [
                    'total' => count($emails),
                    'processed' => count($results),
                    'results' => $results,
                    'summary' => $summary,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('EmailValidationController: Bulk validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Bulk email validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate summary statistics for bulk validation results
     */
    private function generateBulkSummary(array $results): array
    {
        $summary = [
            'valid' => 0,
            'invalid' => 0,
            'risky' => 0,
            'disposable' => 0,
            'suspicious' => 0,
            'failed' => 0,
            'total_cost' => 0,
        ];

        foreach ($results as $result) {
            $status = $result['status'] ?? 'failed';

            switch ($status) {
                case 'valid':
                    $summary['valid']++;
                    break;
                case 'invalid':
                    $summary['invalid']++;
                    break;
                case 'risky':
                    $summary['risky']++;
                    break;
                case 'disposable':
                    $summary['disposable']++;
                    break;
                case 'suspicious':
                    $summary['suspicious']++;
                    break;
                default:
                    $summary['failed']++;
                    break;
            }

            $summary['total_cost'] += $result['cost'] ?? 0;
        }

        return $summary;
    }
}
