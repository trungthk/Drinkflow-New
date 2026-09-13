<?php

namespace App\Services\Contact;

use App\Models\ContactInquiry;
use Illuminate\Support\Str;

class ContactService
{
    /**
     * Store a new contact inquiry.
     *
     * @param array<string, mixed> $data
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return ContactInquiry
     */
    public function createInquiry(array $data, ?string $ipAddress = null, ?string $userAgent = null): ContactInquiry
    {
        $ticketCode = $this->generateUniqueTicketCode();

        return ContactInquiry::create([
            'ticket_code' => $ticketCode,
            'full_name' => trim($data['full_name']),
            'work_email' => strtolower(trim($data['work_email'])),
            'phone' => trim($data['phone']),
            'company' => trim($data['company']),
            'topic' => $data['topic'],
            'message' => trim($data['message']),
            'status' => 'pending',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500) : null,
        ]);
    }

    /**
     * Generate unique ticket code format #DF-XXXXX
     */
    protected function generateUniqueTicketCode(): string
    {
        do {
            $code = '#DF-' . random_int(10000, 99999);
        } while (ContactInquiry::where('ticket_code', $code)->exists());

        return $code;
    }
}
