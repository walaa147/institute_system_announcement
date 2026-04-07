<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class BelongsToInstitute implements ValidationRule
{
    /**
     * @param string $tableName اسم الجدول المراد التحقق منه
     */
    public function __construct(private readonly string $tableName) {}

    /**
     * تنفيذ عملية التحقق.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $user = Auth::user();

        if (! $user instanceof User) {
            $fail('غير مصرح لك بالقيام بهذه العملية.');
            return;
        }

        $exists = DB::table($this->tableName)
            ->where('id', $value)
            ->where('institute_id', $user->institute_id)
            ->exists();

        if (! $exists) {
            $fail('العنصر المحدد غير موجود أو لا ينتمي للمعهد الخاص بك.');
        }
    }
}
