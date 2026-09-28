<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Support;

use Modules\HelpdeskTickets\Support\InboundEmailMessageParser;
use Tests\TestCase;
use Webklex\PHPIMAP\Attribute as ImapAttribute;

/**
 * Movido de FetchTicketEmailsJobTest (30-sep-2026, refactor estructural que
 * extrajo la orquestación IMAP de FetchTicketEmailsJob a delegados finos en
 * app/Support) a apuntar directamente a InboundEmailMessageParser: son
 * métodos públicos sin dependencias de IMAP en vivo ni de BD, así que no
 * necesitan pasar por el job ni por reflexión.
 */
class InboundEmailMessageParserTest extends TestCase
{
    private function parser(): InboundEmailMessageParser
    {
        return new InboundEmailMessageParser;
    }

    // -------------------------------------------------------------------------
    // extractEmailAddress — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_address_from_angle_bracket_format(): void
    {
        $result = $this->parser()->extractEmailAddress('John Doe <john@example.com>');

        $this->assertSame('john@example.com', $result);
    }

    public function test_extract_email_address_plain(): void
    {
        $result = $this->parser()->extractEmailAddress('john@example.com');

        $this->assertSame('john@example.com', $result);
    }

    // -------------------------------------------------------------------------
    // extractEmailName — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_name_from_display_name(): void
    {
        $result = $this->parser()->extractEmailName('John Doe <john@example.com>');

        $this->assertSame('John Doe', $result);
    }

    public function test_extract_email_name_returns_null_for_plain_email(): void
    {
        $result = $this->parser()->extractEmailName('john@example.com');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // detectPriority — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_detect_priority_urgent_keyword(): void
    {
        $this->assertSame('urgent', $this->parser()->detectPriority('URGENT: Help needed'));
    }

    public function test_detect_priority_low_keyword(): void
    {
        $this->assertSame('low', $this->parser()->detectPriority('low priority request'));
    }

    public function test_detect_priority_defaults_to_normal(): void
    {
        $this->assertSame('normal', $this->parser()->detectPriority('Hello there'));
    }

    // -------------------------------------------------------------------------
    // webklex/php-imap adapter helpers — migración desde PhpImap\Mailbox
    // (barbushin/php-imap), que nunca estuvo instalado (composer.json solo
    // trae webklex/php-imap): cualquier canal real con create_tickets/
    // create_replies activo hacía fallar el job entero con "Class not found"
    // en cuanto intentaba conectar de verdad.
    // -------------------------------------------------------------------------

    public function test_decode_mime_header_decodes_encoded_word(): void
    {
        $result = $this->parser()->decodeMimeHeader('=?utf-8?b?V2hhdOKAmXM=?= new for developers at Oracle');

        $this->assertSame("What\u{2019}s new for developers at Oracle", $result);
    }

    public function test_decode_mime_header_leaves_plain_text_untouched(): void
    {
        $result = $this->parser()->decodeMimeHeader('Plain subject, no encoding');

        $this->assertSame('Plain subject, no encoding', $result);
    }

    public function test_string_attribute_returns_null_for_null_attribute(): void
    {
        $this->assertNull($this->parser()->stringAttribute(null));
    }

    public function test_string_attribute_returns_plain_value(): void
    {
        $attribute = new ImapAttribute('message_id', 'abc123@example.com');

        $this->assertSame('abc123@example.com', $this->parser()->stringAttribute($attribute));
    }

    public function test_format_address_attribute_returns_null_for_null_attribute(): void
    {
        $this->assertNull($this->parser()->formatAddressAttribute(null));
    }

    public function test_format_address_attribute_formats_name_and_email(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => 'John Doe',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->parser()->formatAddressAttribute($attribute);

        $this->assertSame('John Doe <john@example.com>', $result);
    }

    public function test_format_address_attribute_omits_brackets_without_a_display_name(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => '',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->parser()->formatAddressAttribute($attribute);

        $this->assertSame('john@example.com', $result);
    }

    public function test_format_address_attribute_joins_multiple_recipients(): void
    {
        $attribute = new ImapAttribute('to', [
            (object) ['personal' => '', 'mailbox' => 'a', 'host' => 'example.com'],
            (object) ['personal' => 'B Person', 'mailbox' => 'b', 'host' => 'example.com'],
        ]);

        $result = $this->parser()->formatAddressAttribute($attribute);

        $this->assertSame('a@example.com, B Person <b@example.com>', $result);
    }

    public function test_split_references_returns_empty_array_for_null(): void
    {
        $this->assertSame([], $this->parser()->splitReferences(null));
    }

    public function test_split_references_trims_brackets_and_whitespace(): void
    {
        $result = $this->parser()->splitReferences('<a@example.com>, b@example.com , <c@example.com>');

        $this->assertSame(['a@example.com', 'b@example.com', 'c@example.com'], $result);
    }

    public function test_format_address_attribute_decodes_mime_encoded_display_name(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => '=?utf-8?b?V2hhdOKAmXM=?=',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->parser()->formatAddressAttribute($attribute);

        $this->assertSame("What\u{2019}s <john@example.com>", $result);
    }
}
