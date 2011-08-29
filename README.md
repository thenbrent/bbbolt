# Subscription Drive Support for WordPress Plugins & Themes

Technically, bbBolt is a PHP library, but at heart, bbBolt is a solution for the overwhelming support that comes with releasing [WordPress Plugins and Themes](http://wordpress.org/extend/) for free.

The bbBolt library can be shipped with a free plugin or theme to provide premium and convenient support. 

bbBolt manages subscriptions through [PayPal](https://merchant.paypal.com/cgi-bin/marketingweb?cmd=_render-content&amp;content_ID=merchant/digital_goods) and introduces a client-server model to the [bbPress plugin](http://wordpress.org/extend/plugins/bbpress/) to offer support convenient for both developers and users.

To use bbBolt you need both a bbBolt Server and a bbBolt Client. 


## Creating a bbBolt Server

There is no administration interface or default plugin for configuring a bbBolt server. Instead, you must create your own plugin to register the server. This ensures that your server has the required details for running your server.

Before you can create a bbBolt server, you must upload the bbBolt directory to your web server. You can either upload it into the base `/wp-content/plugins/` directory or place it within a sub-directory of the server plugin. For example `/wp-content/plugins/my-server-setup/bbbolt/`.

Once the bbBolt files are available, you can create a plugin for your bbBolt Server.

The plugin should have the usual WordPress [Plugin header](http://codex.wordpress.org/Writing_a_Plugin#File_Headers) and it must include the `bbbolt-server-class.php` file.

```php
	<?php
	 /*
	 Plugin Name: My bbBolt Server
	 Description: A plugin for running a bbBolt Server on my website.
	 Author: Your Name
	 Author URI: http://example.com
	 Version: 1.0
	 */

	require_once( PATH_TO_BBBOLT . '/bbbolt-server.class.php' );
```

Once we have a plugin header and have included the bbBolt server class, we can create our bbBolt server using the `register_bbbolt_server()` function. 

```php
	register_bbbolt_server( $name, $paypal_credentials, $args );
```


#### Parameters

To create a bbBolt Server, you must call `register_bbbolt_server()` function with at least two parameters.

```php
	$name (string)(required) – the name of your plugin or support system
	$paypal_credentials (array)(required) – an array containing your PayPal API credentials with the keys:
		'username' (string)(required) – your PayPal API Username
		'password' (string)(required) – your PayPal API Password
		'signature' (string)(required) – your PayPal API Signature
```

If you do not have your PayPal API credentials, refer to the [PayPal API Credentials](http://codex.bbbolt.org/for-plugin-developers/accepting-subscriptions-with-paypal/) section of the bbBolt Codex for instructions on where to access them.

You can call the register_bbbolt_server() function from any file in your plugin, but it must be called from within the WordPress init hook.

For example:

```php
	function eg_register_test_server(){

		$paypal_credentials = array(
			'username'  => EG_PAYPAL_USERNAME,
			'password'  => EG_PAYPAL_PASSWORD,
			'signature' => EG_PAYPAL_SIGNATURE
		);

		register_bbbolt_server( 'test-server', $paypal_credentials );
	}
	add_action( 'init', 'eg_register_test_server' );
```

That's all that is required. Once we activate this plugin via the WordPress Plugin administration page, it will create a bbBolt Server which runs in test mode (using the PayPal Sandbox) and charges a $20/month subscription fee for registration. If you want to change the subscription price, set the Server to be live or configure it in a myriad of other ways, read on.


You can learn more on how to integrate bbBolt into your plugin or theme in the <a title="bbBolt Documentation for Plugin Developers" href="http://codex.bbbolt.org/for-plugin-developers/">documentation for plugin developers</a>.


## Creating a bbBolt Client

There are two components to the bbBolt Client – the bbBolt Client Class and the bbBolt Client UI class. The first of these creates an object for storing the settings specific to your Support System, such as the server's URL. The second is a singleton class which is used to setup a single bbBolt interface for all bbBolt clients active on a website.

To provide a bbBolt Client with your plugin, you must do two things:

1. Include the bbBolt library in your plugin's folder
1. Call the register_bbbolt_client() function.

### Including the bbBolt Library in your Plugin

For best results, copy the entire bbbolt directory into your plugins folder. This will ensure you have the require images and class files for running a bbBolt Client. It will also easier to upgrade to future versions of bbBolt.

Once copied, remove the `example-client.php` and `example-server.php` files as these act only as a guide to developers.

Now the files are in your plugin's directory, you must include them:

```php
	require_once( 'bbbolt-client.class.php' );
```

### Register a bbBolt Client

The bbBolt Client class file includes a helper function for registering a new client.

```php
	register_bbbolt_client( $name, $args );
```

To create your bbBolt client, you must call this function and pass it two parameters: `$name` & `$args`.

#### Parameters

```php
	$name (string)(required) – the name of your plugin or the name of the
	$args (array) – an array of named arguments including:
		‘site_url’ (string)(required) – the URL which includesthe URL of your plugin’s website.
		‘labels’ (array)(optional) – associative array of labels for the client, currently only supports ‘name’ & ‘singular_name’ labels
```

You can call this function from any file in your plugin, but it must be called from within the WordPress init hook.

For example:

```php
	function eg_register_bbbolt_client(){
		$plugin_name = "My Plugin's Name";
		$args = array( 'site_url' => 'http://demo.bbbolt.org/' );
		register_bbbolt_client( $plugin_name, $args );
	}
	add_action( 'init', 'eg_register_yet_another_client' );
```

This code is similar to that in the example-clients.php file.

Once you have called this function, your client is setup and ready for interaction with your bbBolt Server.
