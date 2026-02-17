<?php

namespace Modules\Mailrelay\Services;

use DOMDocument;
use Modules\Mailrelay\Entities\EmailTemplate;
use Modules\Mailrelay\Entities\MailrelayLayout;

class TemplateRendererService
{
    protected VariableReplacementService $variableService;

    public function __construct(VariableReplacementService $variableService)
    {
        $this->variableService = $variableService;
    }

    /**
     * Render a template with its layout and variable replacements.
     *
     * @param  bool  $preview  If true, use example values instead of real data
     */
    public function render(EmailTemplate $template, array $context = [], bool $preview = false): string
    {
        // Get the template content
        $content = $template->html_content ?? $template->text_content ?? '';

        // Apply variable replacements
        if ($preview) {
            $content = $this->variableService->previewWithExamples($content);
        } else {
            $content = $this->variableService->replaceVariables($content, $context);
        }

        // Apply layout if enabled
        if ($template->use_layout && $template->layout_id) {
            $layout = $template->layout;
            if ($layout && $layout->status === 'active') {
                $content = $this->renderWithLayout($content, $layout, $context, $preview);
            }
        }

        // Beautify the HTML
        $content = $this->beautifyHtml($content);

        return $content;
    }

    /**
     * Render content with a layout.
     */
    public function renderWithLayout(string $content, MailrelayLayout $layout, array $context = [], bool $preview = false): string
    {
        $layoutContent = $layout->content ?? '';

        // Apply variable replacements to layout
        if ($preview) {
            $layoutContent = $this->variableService->previewWithExamples($layoutContent);
        } else {
            $layoutContent = $this->variableService->replaceVariables($layoutContent, $context);
        }

        // Replace the {CONTENT} placeholder in the layout with the actual template content
        $rendered = str_replace('{CONTENT}', $content, $layoutContent);

        // If there's no {CONTENT} placeholder, just append the content
        if ($rendered === $layoutContent) {
            $rendered = $layoutContent.PHP_EOL.$content;
        }

        return $rendered;
    }

    /**
     * Beautify HTML by properly formatting it.
     */
    public function beautifyHtml(string $html): string
    {
        if (empty(trim($html))) {
            return $html;
        }

        // Remove existing whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Use DOMDocument to format the HTML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Load HTML (suppress errors for malformed HTML)
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Save and return formatted HTML
        $formatted = $dom->saveHTML();

        // Remove the XML declaration that DOMDocument adds
        $formatted = preg_replace('/^<!DOCTYPE.+?>/', '', $formatted);
        $formatted = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $formatted);

        return trim($formatted);
    }

    /**
     * Inline CSS styles into HTML (useful for email clients).
     */
    public function inlineCss(string $html): string
    {
        // This is a basic implementation
        // For production, consider using a library like tijsverkoyen/css-to-inline-styles

        // Extract <style> tags
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatches);

        if (empty($styleMatches[1])) {
            return $html;
        }

        $styles = implode(' ', $styleMatches[1]);

        // Parse CSS rules (very basic parser)
        preg_match_all('/([^{]+)\{([^}]+)\}/i', $styles, $cssMatches, PREG_SET_ORDER);

        foreach ($cssMatches as $cssMatch) {
            $selector = trim($cssMatch[1]);
            $properties = trim($cssMatch[2]);

            // Only handle simple selectors (class, id, tag)
            if (preg_match('/^([a-z0-9_-]+)$/i', $selector)) {
                // Tag selector
                $html = preg_replace_callback(
                    '/<'.$selector.'([^>]*)>/i',
                    function ($matches) use ($properties) {
                        return $this->addStylesToTag($matches[0], $properties);
                    },
                    $html
                );
            }
        }

        // Remove <style> tags
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        return $html;
    }

    /**
     * Add inline styles to an HTML tag.
     */
    protected function addStylesToTag(string $tag, string $styles): string
    {
        if (strpos($tag, 'style=') !== false) {
            // Tag already has styles, append to existing
            $tag = str_replace('style="', 'style="'.$styles.'; ', $tag);
        } else {
            // Add new style attribute
            $tag = str_replace('>', ' style="'.$styles.'">', $tag);
        }

        return $tag;
    }

    /**
     * Render a template for preview purposes (with example data).
     */
    public function renderPreview(EmailTemplate $template): string
    {
        return $this->render($template, [], true);
    }

    /**
     * Render a template for actual sending (with real data).
     */
    public function renderForSending(EmailTemplate $template, array $context): string
    {
        return $this->render($template, $context, false);
    }

    /**
     * Extract the subject with variable replacements.
     */
    public function renderSubject(EmailTemplate $template, array $context = [], bool $preview = false): string
    {
        $subject = $template->subject ?? '';

        if ($preview) {
            return $this->variableService->previewWithExamples($subject);
        }

        return $this->variableService->replaceVariables($subject, $context);
    }
}
