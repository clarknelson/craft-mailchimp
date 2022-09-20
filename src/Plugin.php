<?php
namespace clarknelson\mailchimp;

use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use clarknelson\mailchimp\services\MailchimpService;
use clarknelson\mailchimp\services\MailchimpListsService;


class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;

    const EDITION_STANDARD = 'standard';
    const EDITION_PRO = 'pro';


    public static function editions(): array
    {
        return [
            self::EDITION_STANDARD,
            self::EDITION_PRO,
        ];
    }

    private function addFormDataListeners(){
        $shouldSubscribe = null;
        $subscriberEmail = null;
        $subscriberListId = $this->mailchimp->defaultListId;
        $mergeFields = [];

        # make sure that the request through Craft 
        # is from the front-end and not the console
        if($this->request instanceof \craft\web\Request){
            # look through all body params
            foreach ($this->request->getBodyParams() as $key => $value) {
                # search for this specific prefix
                preg_match('/MAILCHIMP_SUBSCRIBE_(.+)/', $key, $matches);
                # and only continue if found
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
                        
                        # otherwise it becomes a merge field
                        default:
                            if(isset($_POST[$value])){
                                $mergeFields[$matches[1]] = $_POST[$value];
                            }
                            break;
                    }
                }
            }
        }


        # only consider adding to the list if the email is set
        # and the checkbox is true/false not null.
        if($subscriberEmail != null && $shouldSubscribe != null){
            # only continue if the correct version is installed
            if (Plugin::getInstance()->is(Plugin::EDITION_PRO)){
                if($shouldSubscribe){
                    $response = $this->mailchimp->client->lists->setListMember($subscriberList, $subscriberEmail, [
                        "email_address" => $subscriberEmail,
                        "status_if_new" => "pending",
                        "merge_fields" => $mergeFields
                    ]);
                } else {
                    $response = $this->mailchimp->client->lists->deleteListMember($subscriberList, $subscriberEmail);
                }
            } else {
                throw new \Exception('Please make sure you have the PRO version of the plugin installed.');
            }
        }
    }

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'mailchimp' => MailchimpService::class,
        ]);

        

        $this->addFormDataListeners();

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