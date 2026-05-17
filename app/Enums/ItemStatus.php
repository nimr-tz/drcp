<?php

namespace App\Enums;

enum ItemStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingExternalResponse = 'waiting_external_response';
    case AtDirector = 'at_director';
    case ReturnedForAction = 'returned_for_action';
    case Completed = 'completed';
    case Closed = 'closed';

    /** Statuses that represent a terminal (non-editable) state. */
    public function isTerminal(): bool
    {
        return match($this) {
            self::Completed, self::Closed => true,
            default => false,
        };
    }

    /**
     * Returns the valid next statuses from this status.
     * An empty array means any transition is blocked.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::New => [
                self::Assigned,
                self::InProgress,
            ],
            self::Assigned => [
                self::InProgress,
                self::WaitingExternalResponse,
                self::AtDirector,
                self::Completed,
                self::Closed,
            ],
            self::InProgress => [
                self::WaitingExternalResponse,
                self::AtDirector,
                self::ReturnedForAction,
                self::Completed,
                self::Closed,
            ],
            self::WaitingExternalResponse => [
                self::InProgress,
                self::AtDirector,
                self::Completed,
                self::Closed,
            ],
            self::AtDirector => [
                self::ReturnedForAction,
                self::Completed,
                self::Closed,
            ],
            self::ReturnedForAction => [
                self::InProgress,
                self::WaitingExternalResponse,
                self::AtDirector,
                self::Completed,
                self::Closed,
            ],
            self::Completed, self::Closed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** Human-readable label for display. */
    public function label(): string
    {
        return match($this) {
            self::New                    => 'New',
            self::Assigned               => 'Assigned',
            self::InProgress             => 'In Progress',
            self::WaitingExternalResponse => 'Waiting External Response',
            self::AtDirector             => 'At Director',
            self::ReturnedForAction      => 'Returned for Action',
            self::Completed              => 'Completed',
            self::Closed                 => 'Closed',
        };
    }
}
