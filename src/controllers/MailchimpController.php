<?php 
namespace clarknelson\mailchimp\controllers;

use Yii;
use app\models\Post;
use craft\web\Controller;

class MailchimpController extends Controller
{
    public function actionSubscribe($id)
    {
        dump($id);
    }

}