<?php
namespace clarknelson\mailchimp\services;

use yii\base\Component;

use clarknelson\mailchimp\Plugin as CraftMailchimp;
use craft\helpers\App;

class MailchimpLists {
    public function getLists(){
        return $this->mailchimp->lists->getAllLists();
    }
}


class MailchimpService extends Component
{
    public $client = null;
    public $defaultListId = null;

    public function connectSite($id=null){

        if (CraftMailchimp::getInstance()->is(CraftMailchimp::EDITION_PRO)){
            $listResponse = $this->client->connectedSites->list();
            if(count($listResponse->sites) == 0){
                // create a new list if none exists
                $siteUrl = \Craft::$app->request->hostName;
                $addResponse = $this->client->connectedSites->create([
                    "foreign_id" => md5($siteUrl),
                    "domain" => $siteUrl,
                ]);
                return $addResponse->site_script->fragment;
    
            } else if($id != null) {
                // if an id is provided lets try to find it in the existing connected sites
                foreach ($listResponse->sites as $site) {
                    if($site->foreign_id == $id){
                        return $site->site_script->fragment;
                    }
                }
            } else {
                // hopefully there is at least one that can be returned
                return $listResponse->sites[0]->site_script->fragment;
            }
        } else {
            // throw new \Exception('Please make sure that the pro version of the Craft Mailchimp plugin has been installed before using craft.mailchimp.connectSite().');
        }
    }

    public function init(): void{
        // first get the API key, the minimum for using the plugin.
        $apiKey = CraftMailchimp::getInstance()->settings->apiKey;
        $apiKey = App::env('MAILCHIMP_API_KEY') ?: $apiKey;
        if(!$apiKey){
            return;
        }

        $dataCenter = CraftMailchimp::getInstance()->settings->dataCenter;
        $dataCenter = App::env('MAILCHIMP_API_PREFEX') ?: $dataCenter;
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
        $this->defaultListId = App::env('MAILCHIMP_LIST_ID') ?: $this->defaultListId;
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