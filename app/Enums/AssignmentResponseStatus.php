<?php

namespace App\Enums;

/**
 * What a single recorded history entry says about an assignment.
 *
 * Notified means "informed, no reply requested" and never changes the response state.
 */
enum AssignmentResponseStatus: string
{
    case Notified = 'notified';
    case Invited = 'invited';
    case ReconfirmRequested = 'reconfirm_requested';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Notified => __('Informed'),
            self::Invited => __('Invited'),
            self::ReconfirmRequested => __('Please confirm again'),
            self::Confirmed => __('Confirmed'),
            self::Declined => __('Declined'),
            self::Cancelled => __('Cancelled'),
            self::Unknown => __('Unknown'),
        };
    }

    public function changesResponse(): bool
    {
        return $this !== self::Notified;
    }
}
