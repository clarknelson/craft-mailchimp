<?php

namespace clarknelson\mailchimp\models;

use craft\base\Model;

class Settings extends Model
{
    public $apiKey = null;
    public $dataCenter = null;
    public $defaultListId = null;

    public function rules(): array
    {
        return [
            [['apiKey'], 'required'],
        ];
    }
}