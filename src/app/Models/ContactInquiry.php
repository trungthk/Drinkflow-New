<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESPONDED = 'responded';
    public const STATUS_CLOSED = 'closed';

    protected $table = 'contact_inquiries';

    protected $fillable = [
        'ticket_code',
        'full_name',
        'work_email',
        'phone',
        'company',
        'topic',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
