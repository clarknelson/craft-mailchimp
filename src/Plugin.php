<?php
namespace clarknelson\mailchimp;

use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use clarknelson\mailchimp\services\MailchimpService;
use clarknelson\mailchimp\services\MailchimpListsService;


class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;

    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';


    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    private function addFormDataListeners(){
        $shouldSubscribe = null;
        $subscriberEmail = null;
        $subscriberListId = $this->mailchimp->defaultListId;
        $mergeFields = [];

        foreach ($this->request->getBodyParams() as $key => $value) {
            preg_match('/MAILCHIMP_SUBSCRIBE_(.+)/', $key, $matches);
            if(count($matches) && isset($matches[1])){
                switch ($matches[1]) {
                    # determines whether to subscribe 
                    # or unsubscribe email from list
                    case 'CHECKBOX':
                        if(gettype($value) == 'boolean'){
                            $shouldSubscribe = $value;
                        } else {
                            if(isset($_POST[$value])){
                                $shouldSubscribe = $_POST[$value];
                            }
                        }
                        break;

                    # determines what email to add
                    case 'EMAIL':
                        if(isset($_POST[$value])){
                            $subscriberEmail = $_POST[$value];
                        }
                        break;
                    
                    # determines what list to use
                    case 'LIST_ID':
                        $subscriberList = $value;
                        break;
                    
                    default:
                        if(isset($_POST[$value])){
                            $mergeFields[$matches[1]] = $_POST[$value];
                        }
                        break;
                }
            }
        }

        // only consider this 
        if($subscriberEmail != null && $shouldSubscribe != null){
            if($shouldSubscribe){
                $response = $this->mailchimp->client->lists->setListMember($subscriberList, $subscriberEmail, [
                    "email_address" => $subscriberEmail,
                    "status_if_new" => "pending",
                    "merge_fields" => $mergeFields
                ]);
            } else {
                $response = $this->mailchimp->client->lists->deleteListMember($subscriberList, $subscriberEmail);
            }
        }
    }

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'mailchimp' => MailchimpService::class,
        ]);

        if (Plugin::getInstance()->is(Plugin::EDITION_PRO)){
            $this->addFormDataListeners();
        }

        $this->view->hook('craft-mailchimp--subscribe-checkbox', function(array &$context) {
            return '<p>Hey!</p>';
        });

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;
    
                // Attach a behavior:
                // $variable->attachBehaviors([
                //     MyBehavior::class,
                // ]);
                // Attach a service:
                $variable->set('mailchimp', MailchimpService::class);
                // $variable->set('mailchimpLists', MailchimpListsService::class);
            }
        );
    }

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new \clarknelson\mailchimp\models\Settings();
    }
    protected function settingsHtml(): ?string
    {
        return \Craft::$app->getView()->renderTemplate(
            'craft-mailchimp/settings',
            [ 'settings' => $this->getSettings() ]
        );
    }
}