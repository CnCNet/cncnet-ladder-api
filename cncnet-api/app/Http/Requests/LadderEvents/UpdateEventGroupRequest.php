<?php

namespace App\Http\Requests\LadderEvents;

use App\Models\LadderEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add or remove a named group member (FR-031).
 */
class UpdateEventGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware: auth + restrict:canAdminLadder
    }

    public function rules(): array
    {
        /** @var LadderEvent $event */
        $event = $this->attributes->get('ladderEvent');

        return [
            'action' => ['required', Rule::in(['add', 'remove'])],
            'group_key' => ['required', 'string', Rule::in($event->definition()->groups)],
            'user_id' => ['required', 'integer', $this->input('action') === 'add' ? 'exists:users,id' : 'min:1'],
        ];
    }
}
