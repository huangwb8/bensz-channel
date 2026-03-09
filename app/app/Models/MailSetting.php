<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'enabled',
        'smtp_scheme',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'test_recipient',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->smtp_host)
            && $this->smtp_port !== null
            && filled($this->from_address)
            && filled($this->from_name);
    }
}
