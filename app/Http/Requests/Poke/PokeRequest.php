<?php

declare(strict_types=1);

namespace App\Http\Requests\Poke;

use App\Rules\Friend;
use App\Rules\PokeInitiator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PokeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'friend_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$this->user()->id]),
                new Friend(),
                new PokeInitiator(),
            ],
        ];
    }
}