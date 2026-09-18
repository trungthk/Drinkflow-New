<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Models\ContactInquiry;
use Illuminate\Support\Str;

class ContactService
{
    /**
     * Store a new contact inquiry.
     *
     * @param array<string, mixed> $data Form input payload.
     * @param string|null $ipAddress Client IP address.
     * @param string|null $userAgent Client user-agent string.
     * @return ContactInquiry Created contact inquiry instance.
     */
    public function createInquiry(array $data, ?string $ipAddress = null, ?string $userAgent = null): ContactInquiry
    {
        $ticketCode = $this->generateUniqueTicketCode();

        return ContactInquiry::create([
            'ticket_code' => $ticketCode,
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'work_email' => strtolower(trim((string) ($data['work_email'] ?? ''))),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'company' => trim((string) ($data['company'] ?? '')),
            'topic' => (string) ($data['topic'] ?? ''),
            'message' => trim((string) ($data['message'] ?? '')),
            'status' => ContactInquiry::STATUS_PENDING,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500) : null,
        ]);
    }

    /**
     * Generate a unique ticket code with format #DF-XXXXX.
     *
     * @return string Unique ticket code string.
     */
    protected function generateUniqueTicketCode(): string
    {
        do {
            $code = '#TK-' . random_int(10000, 99999);
        } while (ContactInquiry::where('ticket_code', $code)->exists());

        return $code;
    }
}
