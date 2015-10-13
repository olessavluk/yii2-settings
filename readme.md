# Settings Component
Simple yii2 component for persistent settings storage

## Installation:
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

`php composer.phar require olessavluk/yii2-settings "*@dev"`

or add

`"olessavluk/yii2-settings": "*@dev"`

to your `composer.json` file.

## Usage

Add migration to create table for settings:

```php
class m150929_124601_settings extends olessavluk\settings\m150929_122401_settings
{
}
```

Add the following code in your application configuration:
```php
'components' => [
  ...
  /**
   * required for advanced application template,
   * to share cache between frontend and backend
   */
  'frontCache' => [
     'class' => 'yii\caching\FileCache',
     'cachePath' => '@frontend/runtime/cache',
  ],
  'settings' => [
      'class' => '\olessavluk\settings\SettingsComponent',
      'cacheName' => 'frontCache',
      'defaults' => [ //optional default settings
          'app' => [
              'siteName' => 'MyApp',
              'adminEmail' => 'admin@exapmle.com',
              'fromEmail' => 'no-reply@example.com',
          ],
      ],
  ],
  ...
]
```

Now you can use this component:

```php
Yii->$app->settings->get('app', 'siteName');
Yii->$app->settings->delete('app', 'siteName');
Yii->$app->settings->set('app', 'siteName', 'NewSiteName');
```
