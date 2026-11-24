<?php

namespace App\Http\Requests\LadderEvents;

use App\Models\LadderEvent;
use App\Models\LadderEventAwardResult;
use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\LadderEventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Set a manual award winner (FR-020) or an override on a computed award (FR-023),
 * validating the optional game (FR-022).
 */
class SaveEventResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware: auth + restrict:canAdminLadder
    }

    public function event(): LadderEvent
    {
        return $this->attributes->get('ladderEvent');
    }

    public function award(): ?AwardDefinition
    {
        return $this->event()->definition()->award((string) $this->input('award_key'));
    }

    public function kind(): string
    {
        return $this->award()?->type === AwardDefinition::TYPE_MANUAL
            ? LadderEventAwardResult::KIND_MANUAL
            : LadderEventAwardResult::KIND_OVERRIDE;
    }

    public function rules(): array
    {
        $cardKeys = array_map(fn ($a) => $a->key, $this->event()->definition()->cardAwards());

        return [
            'award_key' => ['required', 'string', Rule::in($cardKeys)],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'game_id' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || !$this->filled('game_id'))
                {
                    return;
                }

                $error = app(LadderEventService::class)->validateResultGame(
                    $this->event(),
                    $this->award(),
                    (int) $this->input('user_id'),
                    (int) $this->input('game_id')
                );

                if ($error !== null)
                {
                    $validator->errors()->add('game_id', $error);
                }
            },
        ];
    }
}
