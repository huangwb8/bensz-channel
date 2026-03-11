<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'role',
        'avatar_url',
        'bio',
        'password',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_recovery_codes' => 'array',
            'two_factor_enabled_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_enabled_at !== null;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    public function emailChannelSubscriptions(): HasMany
    {
        return $this->hasMany(ChannelEmailSubscription::class);
    }

    public function ensureNotificationPreference(): UserNotificationPreference
    {
        $preference = $this->relationLoaded('notificationPreference')
            ? $this->getRelation('notificationPreference')
            : $this->notificationPreference()->first();

        if ($preference instanceof UserNotificationPreference) {
            return $preference;
        }

        $preference = $this->notificationPreference()->create();
        $this->setRelation('notificationPreference', $preference);

        return $preference;
    }

    public function subscribesToChannelArticles(Channel|int $channel): bool
    {
        $channelId = $channel instanceof Channel ? $channel->id : $channel;
        $preference = $this->ensureNotificationPreference();

        if ($preference->email_all_articles) {
            return true;
        }

        $subscriptions = $this->relationLoaded('emailChannelSubscriptions')
            ? $this->getRelation('emailChannelSubscriptions')
            : $this->emailChannelSubscriptions()->get();

        return $subscriptions->contains(fn (ChannelEmailSubscription $subscription) => $subscription->channel_id === $channelId);
    }

    public function wantsMentionEmails(): bool
    {
        return $this->ensureNotificationPreference()->email_mentions;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
