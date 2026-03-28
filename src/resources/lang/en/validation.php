<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attributeを承認してください',
    'active_url' => ':attributeは有効なURLではありません',
    'after' => ':attributeには:dateより後の日付を指定してください',
    'alpha' => ':attributeは英字のみで入力してください',
    'alpha_dash' => ':attributeは英数字・ハイフン・アンダースコアのみで入力してください',
    'alpha_num' => ':attributeは英数字のみで入力してください',
    'array' => ':attributeは配列で指定してください',
    'before' => ':attributeには:dateより前の日付を指定してください',
    'between' => [
        'string' => ':attributeは:min文字以上:max文字以下で入力してください',
        'numeric' => ':attributeは:minから:maxの間で入力してください',
        'file' => ':attributeは:min KBから:max KBの間で指定してください',
        'array' => ':attributeは:min個以上:max個以下にしてください',
    ],
    'boolean' => ':attributeはtrueまたはfalseで指定してください',
    'confirmed' => ':attributeと一致しません',
    'date' => ':attributeは正しい日付を入力してください',
    'date_format' => ':attributeの形式が正しくありません',
    'different' => ':attributeと:otherは異なる値を指定してください',
    'digits' => ':attributeは:digits桁で入力してください',
    'digits_between' => ':attributeは:min桁から:max桁で入力してください',
    'email' => ':attributeはメールアドレス形式で入力してください',
    'exists' => '選択された:attributeは正しくありません',
    'file' => ':attributeはファイルを指定してください',
    'filled' => ':attributeを入力してください',
    'image' => ':attributeは画像ファイルを指定してください',
    'in' => '選択された:attributeは正しくありません',
    'integer' => ':attributeは整数で入力してください',
    'max' => [
        'string' => ':attributeは:max文字以下で入力してください',
        'numeric' => ':attributeは:max以下で入力してください',
        'file' => ':attributeは:max KB以下で指定してください',
        'array' => ':attributeは:max個以下にしてください',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
        'numeric' => ':attributeは:min以上で入力してください',
        'file' => ':attributeは:min KB以上で指定してください',
        'array' => ':attributeは:min個以上にしてください',
    ],
    'numeric' => ':attributeは数字で入力してください',
    'regex' => ':attributeの形式が正しくありません',
    'required' => ':attributeを入力してください',
    'same' => ':attributeと:otherが一致しません',
    'string' => ':attributeは文字列で入力してください',
    'unique' => 'この:attributeは既に登録されています',
    'url' => ':attributeは正しいURL形式で入力してください',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'passwors_confirmation' => 'パスワード',
    ],

];
