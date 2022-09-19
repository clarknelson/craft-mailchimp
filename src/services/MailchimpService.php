<?php
namespace clarknelson\mailchimp\services;

use yii\base\Component;

use clarknelson\mailchimp\Plugin as CraftMailchimp;


class MailchimpLists {
    public function getLists(){
        return $this->mailchimp->lists->getAllLists();
    }
}


class MailchimpService extends Component
{
    public $client = null;
    public $defaultListId = null;

    public function getList($id=null){
        return $this->client->lists->getList($id ?? $this->defaultListId);
    }

    public function init(){
        // first get the API key, the minimum for using the plugin.
        $apiKey = CraftMailchimp::getInstance()->settings->apiKey;
        if(!$apiKey){
            throw new \Exception('There is no API key set for the Mailchimp Plugin, please check the settings.');
        }

        $dataCenter = CraftMailchimp::getInstance()->settings->dataCenter;
        if(!$dataCenter){
            // find data center from api key
            $dataCenter = explode('-', $apiKey)[1];
        }
        
        // create a new mailchimp client
        $this->client = new \MailchimpMarketing\ApiClient();
        $this->client->setConfig([
            'apiKey' => $apiKey,
            'server' => $dataCenter
        ]);

        $this->defaultListId = CraftMailchimp::getInstance()->settings->defaultListId;
        if(!$this->defaultListId){
            $response = $this->client->lists->getAllLists();
            if(isset($response->lists[0])){
                $this->defaultListId = $response->lists[0]->id;
            } else {
                throw new \Exception('Could not find a default list/audience to use. Please check your account to make sure an audience is available.');
            }
        }

    }
}