# Craft Mailchimp

This is a Craft CMS plugin to connect with the Mailchimp email marketing API.

## Install

You will want to install this plugin from the command line or the plugin page. 

## Config

The settings can be provided through the settings page `/admin/settings/plugins/craft-mailchimp`.

Or they can be provided in a php file `/config/craft-mailchimp.php`:

```php
<?php
return [
    'apiKey' => 'b3e485720d0fb51f01884d4289e62d15-us10',
    'dataCenter' => 'us10',
    'defaultListId' => '2345b79e5f',
];
```

These can also be set in the `.env` which will take highest priority in the load order.

```
MAILCHIMP_API_KEY=b3e485720d0fb51f01884d4289e62d15-us10
MAILCHIMP_API_PREFEX=us10
MAILCHIMP_LIST_ID=2345b79e5f
```

The API Key can be found in the Mailchimp Admin panel under your user settings. ds.admin.mailchimp.com/account/api where ds is your accounts data center (ie us10). 

This setting is required for the plugin to work correctly.

The data center would be the prefix or suffix for the API key, for example us10. This is optional and will try to be parsed from the API key that is provided. 

To use a specific audience on your account, use that list's Audience ID found on the settings page at ds.admin.mailchimp.com/lists/settings/default?id=web-id. By default the first audience on your account will be used, so this may not be required if you are using a single list on your account.

## Usage

### Mailchimp PHP API

If the plugin was installed and configured correctly, the entire Mailchimp PHP API should be exposed for use on the front-end. 

For example, if the PHP code in the API docs is as follows:

```php
$response = $client->lists->getAllLists();
```

To retrieve all of the audience / lists on the account. You would write the following in twig:

```twig
{% set response = craft.mailchimp.client.lists.getAllLists() %}
{% for list in response.lists %}
    <a href="{{ list.subscribe_url_short }}" target="_blank">{{ list.name }}</a>
{% endfor %}
```

Which will list each audience on your account, linked to it's sign up form. 

While all of the methods should be available please be very careful running queries. Keep in mind that twig should display data, not modify data, so avoid the POST / PUT / DELETE methods and concentrate on the functions that use GET methods.

The <a href="https://mailchimp.com/developer/marketing/api/lists/">Lists API</a> is a good starting point for figuring out what you want to do.

### Connected Sites (Pro)

This plugin supports connected sites for adding popups and similar elements to your Craft website through the Mailchimp interface.

It is easy to get started, simply add this script to the `<head>` of your document.

```twig
{{ craft.mailchimp.connectSite() | raw }}
```

This will output the correct `<script>` tag so your website can connect with Mailchimp. 

### Form Input Tags (Pro)

I have created a system to help sign up users to a Mailchimp list from any form using hidden input fields.

#### Required Tags

```twig
{{ hiddenInput('MAILCHIMP_SUBSCRIBE_CHECKBOX', 'mailchimpSubscribe') }}
{{ hiddenInput('MAILCHIMP_SUBSCRIBE_EMAIL', 'emailAddress') }}
```

The `MAILCHIMP_SUBSCRIBE_CHECKBOX` field determines whether to subscribe or unsubscribe a user from the list. This can be true or false, or a string in which case it will be asummed to be the `name` attribute of a checkbox field which will determine the truthiness. 

The `MAILCHIMP_SUBSCRIBE_EMAIL` field is the name attribute for the email address input field which will be subscribed to the list. For example:

```html
<input type="checkbox" name="mailchimpSubscribe" checked>
<input type="email" name="emailAddress" placeholder="email@domain.com">
```

This way you should be able to add the Mailchimp functionality to any existing form.

#### Optional Tags

```twig
{{ hiddenInput('MAILCHIMP_SUBSCRIBE_LIST_ID', '2345b79e4f') }}

{{ hiddenInput('MAILCHIMP_SUBSCRIBE_FNAME', 'firstName') }}
{{ hiddenInput('MAILCHIMP_SUBSCRIBE_LNAME', 'lastName') }}
{{ hiddenInput('MAILCHIMP_SUBSCRIBE_PHONE', 'phoneNumber') }}
```

The `MAILCHIMP_SUBSCRIBE_LIST_ID` input can be provided to add to a specific mailchimp list. Otherwise the first list in the account will be used.

The `MAILCHIMP_SUBSCRIBE_FNAME`, `MAILCHIMP_SUBSCRIBE_LNAME`, and `MAILCHIMP_SUBSCRIBE_PHONE` all refer to merge fields in the Mailchimp list. These are additional pieces of information associated with the user. Any `MAILCHIMP_SUBSCRIBE_` input provided that does not end with `CHECKBOX`, `EMAIL`, or `LIST_ID` is assumed to be a merge field.




