<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job_posting'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '求人タイトルは必須です',
            'title.max' => '求人タイトルは150文字以内で入力して下さい',
            'description.required' => '業務内容・応募条件は必須です',
            'description.max' => '業務内容・応募条件は4000文字以内で入力して下さい',
        ];
    }
}
