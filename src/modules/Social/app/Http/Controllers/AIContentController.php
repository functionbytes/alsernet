<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Services\AIContentGenerator;

class AIContentController extends Controller
{
    protected AIContentGenerator $aiGenerator;

    public function __construct(AIContentGenerator $aiGenerator)
    {
        $this->aiGenerator = $aiGenerator;
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'tone' => 'nullable|string|in:professional,casual,friendly,formal,humorous',
            'max_length' => 'nullable|integer|min:50|max:1000',
        ]);

        try {
            $content = $this->aiGenerator->generateContent(
                $validated['topic'],
                $validated['tone'] ?? 'professional',
                $validated['max_length'] ?? 280
            );

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error generando contenido: '.$e->getMessage(),
            ], 500);
        }
    }

    public function suggestHashtags(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'count' => 'nullable|integer|min:1|max:15',
        ]);

        try {
            $hashtags = $this->aiGenerator->suggestHashtags(
                $validated['content'],
                $validated['count'] ?? 8
            );

            return response()->json([
                'success' => true,
                'hashtags' => $hashtags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error generando hashtags: '.$e->getMessage(),
            ], 500);
        }
    }

    public function improve(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'goal' => 'nullable|string|in:engagement,clicks,conversions,awareness',
        ]);

        try {
            $improved = $this->aiGenerator->improveContent(
                $validated['content'],
                $validated['goal'] ?? 'engagement'
            );

            return response()->json([
                'success' => true,
                'improved_content' => $improved,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error mejorando contenido: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateVariations(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'count' => 'nullable|integer|min:2|max:5',
        ]);

        try {
            $variations = $this->aiGenerator->generateVariations(
                $validated['content'],
                $validated['count'] ?? 3
            );

            return response()->json([
                'success' => true,
                'variations' => $variations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error generando variaciones: '.$e->getMessage(),
            ], 500);
        }
    }
}
