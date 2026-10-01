<?php

return [
    'after_or_equal' => ':attribute 必須是 :date 當天或之後的日期。',
    'array' => ':attribute 必須是陣列。',
    'boolean' => ':attribute 必須是是或否。',
    'date' => ':attribute 必須是有效的日期。',
    'date_format' => ':attribute 必須符合 :format 格式。',
    'distinct' => ':attribute 有重複的值。',
    'enum' => '所選的 :attribute 無效。',
    'exists' => '所選的 :attribute 無效。',
    'gt' => [
        'numeric' => ':attribute 必須大於 :value。',
    ],
    'gte' => [
        'numeric' => ':attribute 必須大於或等於 :value。',
    ],
    'in' => '所選的 :attribute 無效。',
    'integer' => ':attribute 必須是整數。',
    'max' => [
        'numeric' => ':attribute 不可大於 :max。',
        'string' => ':attribute 不可超過 :max 個字元。',
    ],
    'min' => [
        'numeric' => ':attribute 不可小於 :min。',
        'string' => ':attribute 至少需要 :min 個字元。',
    ],
    'numeric' => ':attribute 必須是數字。',
    'present' => ':attribute 欄位必須存在。',
    'required' => ':attribute 為必填。',
    'string' => ':attribute 必須是文字。',
    'unique' => ':attribute 已經被使用。',
];
